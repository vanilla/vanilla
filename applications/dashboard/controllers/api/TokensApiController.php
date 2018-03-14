<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/tokens` resource.
 */
class TokensApiController extends AbstractApiController {

    /** Default expiry for issued tokens. */
    const DEFAULT_EXPIRY = '10 years';

    /** The maximum number of tokens in a response. */
    const RESPONSE_LIMIT = 200;

    /** Default token type. */
    const TOKEN_TYPE = 'personal';

    /** @var AccessTokenModel */
    private $accessTokenModel;

    /** @var Schema */
    private $fullSchema;

    /** @var Schema */
    private $sensitiveSchema;

    /**
     * TokensApiController constructor.
     *
     * @param AccessTokenModel $accessTokenModel
     */
    public function __construct(AccessTokenModel $accessTokenModel) {
        $this->accessTokenModel = $accessTokenModel;
    }

    /**
     * Revoke an access token.
     *
     * @param int $id
     * @throws ClientException if current user isn't authorized to delete the token.
     */
    public function delete($id) {
        $this->idParamSchema()->setDescription('Revoke an access token.');
        $out = $this->schema([], 'out');

        $row = $this->token($id);
        if ($row['UserID'] != $this->getSession()->UserID) {
            $this->permission('Garden.Settings.Manage');
        }

        $this->accessTokenModel->revoke($id);
    }

    /**
     * Given a token row, determine if it is active.
     *
     * @param int|string|array $token Full token row.
     * @param bool $throw Should relevant exceptions be thrown on an error?
     * @throws ClientException if the token has been revoked.
     * @throws ClientException if the token has expired.
     * @return bool
     */
    public function isActiveToken($token, $throw = false) {
        if (is_array($token)) {
            $row = $token;
        } elseif (filter_var($token, FILTER_VALIDATE_INT)) {
            $row = $this->accessTokenModel->getID($token);
        } elseif (is_string($token)) {
            $row = $this->accessTokenModel->getToken($token);
        } else {
            $row = false;
        }

        if (!is_array($row)) {
            return false;
        }

        if (array_key_exists('Attributes', $row)) {
            $attributes = $row['Attributes'];
            if (is_array($attributes)) {
                // Skip if this token has been revoked.
                if (array_key_exists('revoked', $row['Attributes']) && $row['Attributes']['revoked']) {
                    if ($throw) {
                        throw new ClientException('Token revoked.', 410);
                    }
                    return false;
                }
            }
        } else {
            return false;
        }

        // Skip if this token is expired.
        $expiry = strtotime($row['DateExpires']);
        if (time() > $expiry) {
            if ($throw) {
                throw new ClientException('Token expired.', 410);
            }
            return false;
        }

        return true;
    }

    /**
     * Get a schema representing all available token fields.
     *
     * @return Schema
     */
    public function fullSchema() {
        if (!isset($this->fullSchema)) {
            $this->fullSchema = $this->schema([
                'accessTokenID:i' => 'The unique numeric ID.',
                'name:s|n' => 'A user-specified label.',
                'dateInserted:dt' => 'When the token was generated.'
            ], 'Token');
        }
        return $this->fullSchema;
    }

    /**
     * Get a signed copy of a token.
     *
     * @param int $id
     * @param array $query
     * @throws NotFoundException if this is not an active token.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission('Garden.Tokens.Add');

        $this->idParamSchema();
        $in = $this->schema([
            'id',
            'transientKey:s' => 'A valid CSRF token for the current user.'
        ], 'in')->setDescription('Reveal a usable access token.');
        $out = $this->schema($this->sensitiveSchema(), 'out');

        $query['id'] = $id;
        $query = $in->validate($query);
        $this->validateTransientKey($query['transientKey']);

        $row = $this->token($id);
        if ($row['UserID'] != $this->getSession()->UserID) {
            if ($this->getSession()->checkPermission('Garden.Settings.Manage') === false) {
                throw new NotFoundException('Access Token');
            }
        }
        $this->isActiveToken($row, true);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only schema for token records.
     *
     * @return Schema
     */
    public function idParamSchema() {
        return $this->schema(['id:i' => 'The numeric ID of a token.'], 'in');
    }

    /**
     * List active tokens for the current user.
     *
     * @return array
     */
    public function index() {
        $this->permission('Garden.Tokens.Add');

        $in = $this->schema([], 'in')->setDescription('Get a list of access token IDs for the current user.');
        // Full access token details are not available in the index. Use GET on a single ID for sensitive information.
        $out = $this->schema([
            ':a' => $this->schema([
                'accessTokenID',
                'name',
                'dateInserted'
            ])->add($this->fullSchema())
        ], 'out');

        $rows = $this->accessTokenModel->getWhere([
            'UserID' => $this->getSession()->UserID,
            'Type' => self::TOKEN_TYPE
        ], 'DateInserted', 'desc', self::RESPONSE_LIMIT)->resultArray();
        $activeTokens = [];
        foreach ($rows as $token) {
            if ($this->isActiveToken($token) === false) {
                continue;
            }
            $activeTokens[] = $token;
        }
        unset($token);
        $activeTokens = array_map([$this, 'normalizeOutput'], $activeTokens);

        $result = $out->validate($activeTokens);
        return $result;
    }

    /**
     * Issue a new access token for the current user.
     *
     * @param array $body
     * @return mixed
     */
    public function post(array $body) {
        $this->permission('Garden.Tokens.Add');

        $in = $this->schema([
            'name:s' => 'A name indicating what the access token will be used for.',
            'transientKey:s' => 'A valid CSRF token for the current user.'
        ], 'in')->setDescription('Issue a new access token for the current user.');
        $out = $this->schema($this->sensitiveSchema(), 'out');

        $body = $in->validate($body);
        $this->validateTransientKey($body['transientKey']);

        // Issue the new token.
        $accessToken = $this->accessTokenModel->issue(
            $this->getSession()->UserID,
            self::DEFAULT_EXPIRY,
            self::TOKEN_TYPE
        );
        $this->validateModel($this->accessTokenModel);
        $token = $this->accessTokenModel->trim($accessToken);
        $row = $this->accessTokenModel->getToken($token);
        $accessTokenID = $row['AccessTokenID'];
        $row = $this->accessTokenModel->setAttribute($accessTokenID, 'name', $body['name']);

        // Serve up the result.
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        $name = null;
        if (array_key_exists('Attributes', $dbRecord) && is_array($dbRecord['Attributes'])) {
            if (array_key_exists('name', $dbRecord['Attributes']) && is_string($dbRecord['Attributes']['name'])) {
                $name = $dbRecord['Attributes']['name'];
            }
        }
        $dbRecord['Name'] = $name ?: t('Personal Access Token');

        if (array_key_exists('Token', $dbRecord) && is_string($dbRecord['Token'])) {
            $dbRecord['AccessToken'] = $this->accessTokenModel->signTokenRow($dbRecord);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Get a schema for outputting sensitive token information.
     */
    public function sensitiveSchema() {
        if (!isset($this->sensitiveSchema)) {
            $this->sensitiveSchema = $this->schema([
                'accessTokenID',
                'name',
                'accessToken:s' => 'A signed version of the token.',
                'dateInserted'
            ])->add($this->fullSchema());
        }
        return $this->sensitiveSchema;
    }

    /**
     * Get an access token by its numeric ID.
     *
     * @param int $accessTokenID
     * @throws NotFoundException when the token cannot be located by its ID.
     * @return array
     */
    protected function token($accessTokenID) {
        $row = $this->accessTokenModel->getID($accessTokenID);
        if (!$row) {
            throw new NotFoundException('Access Token');
        }
        return $row;
    }

    /**
     * Validate the transient key for the current request.
     *
     * @param string $transientKey
     * @throws ClientException
     */
    public function validateTransientKey($transientKey) {
        if ($this->getSession()->transientKey() === false) {
            $this->getSession()->loadTransientKey();
        }

        if ($this->getSession()->transientKey() != $transientKey) {
            throw new ClientException('Invalid transient key.', 401);
        }
    }
}

<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;

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
    private $idSchema;

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
        $this->schema($this->idSchema(),'in')->setDescription('Revoke an authentication token.');
        $out = $this->schema([], 'out');

        $row = $this->token($id);
        if ($row['UserID'] != $this->session->UserID) {
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
            ], 'token');
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

        $in = $this->schema([
            'id',
            'transientKey:s' => 'A valid CSRF token for the current user.'
        ], 'in')->add($this->idSchema())->setDescription('Reveal a usable authentication token.');
        $out = $this->schema($this->sensitiveSchema(), 'out');

        $query['id'] = $id;
        $query = $in->validate($query);
        $this->validateTransientKey($query['transientKey']);

        $row = $this->token($id);
        if ($row['UserID'] != $this->session->UserID) {
            if ($this->session->checkPermission('Garden.Settings.Manage') === false) {
                throw new NotFoundException('Access Token');
            }
        }
        $this->isActiveToken($row, true);
        $this->prepareRow($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only schema for token records.
     *
     * @return Schema
     */
    public function idSchema() {
        if (!isset($this->idSchema)) {
            $this->idSchema = $this->schema(
                ['id:i' => 'The numeric ID of a token.'],
                'tokenID'
            );
        }
        return $this->idSchema;
    }

    /**
     * List active tokens for the current user.
     *
     * @return array
     */
    public function index() {
        $this->permission('Garden.Tokens.Add');

        $in = $this->schema([], 'in');
        // Full access token details are not available in the index. Use GET on a single ID for sensitive information.
        $out = $this->schema([
            ':a' => $this->schema([
                'accessTokenID',
                'name',
                'dateInserted'
            ])->add($this->fullSchema())
        ], 'out')->setDescription('Get a list of authentication token IDs for the current user.');

        $rows = $this->accessTokenModel->getWhere([
            'UserID' => $this->session->UserID,
            'Type' => self::TOKEN_TYPE
        ], '', 'asc', self::RESPONSE_LIMIT)->resultArray();
        $activeTokens = [];
        foreach ($rows as $token) {
            if ($this->isActiveToken($token) === false) {
                continue;
            }
            $activeTokens[] = $token;
        }
        unset($token);
        array_walk($activeTokens, [$this, 'prepareRow']);

        $result = $out->validate($activeTokens);
        return $result;
    }

    /**
     * Issue a new transient key for the current user.
     *
     * @param array $body
     * @return mixed
     */
    public function post(array $body) {
        $this->permission('Garden.Tokens.Add');

        $in = $this->schema([
            'name:s' => 'A name indicating what the access token will be used for.',
            'transientKey:s' => 'A valid CSRF token for the current user.'
        ], 'in')->setDescription('Issue a new authentication token for the current user.');
        $out = $this->schema($this->sensitiveSchema(), 'out');

        $body = $in->validate($body);
        $this->validateTransientKey($body['transientKey']);

        // Issue the new token.
        $accessToken = $this->accessTokenModel->issue(
            $this->session->UserID,
            self::DEFAULT_EXPIRY,
            self::TOKEN_TYPE
        );
        $this->validateModel($this->accessTokenModel);
        $token = $this->accessTokenModel->trim($accessToken);
        $row = $this->accessTokenModel->getToken($token);
        $accessTokenID = $row['AccessTokenID'];
        $row = $this->accessTokenModel->setAttribute($accessTokenID, 'name', $body['name']);

        // Serve up the result.
        $this->prepareRow($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Prepare the current row for output.
     *
     * @param array $row
     */
    public function prepareRow(array &$row) {
        $name = null;
        if (array_key_exists('Attributes', $row)) {
            if (array_key_exists('name', $row['Attributes']) && is_string($row['Attributes']['name'])) {
                $name = $row['Attributes']['name'];
            }
        }
        $row['Name'] = $name ?: t('Personal Access Token');

        if (array_key_exists('Token', $row) && is_string($row['Token'])) {
            $row['AccessToken'] = $this->accessTokenModel->signToken($row['Token']);
        }
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
        if ($this->session->transientKey() === false) {
            $this->session->loadTransientKey();
        }

        if ($this->session->transientKey() != $transientKey) {
            throw new ClientException('Invalid transient key.', 401);
        }
    }
}

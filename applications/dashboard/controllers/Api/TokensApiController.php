<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/tokens` resource.
 */
class TokensApiController extends AbstractApiController {

    /** Default expiry for issued tokens. */
    const DEFAULT_EXPIRY = '1 month';

    /** @var AccessTokenModel */
    private $accessTokenModel;

    /** @var Gdn_Session */
    private $session;

    /**
     * TokensApiController constructor.
     *
     * @param AccessTokenModel $accessTokenModel
     * @param Gdn_Session $session
     */
    public function __construct(AccessTokenModel $accessTokenModel, Gdn_Session $session) {
        $this->accessTokenModel = $accessTokenModel;
        $this->session = $session;
    }

    /**
     * Issue a new transient key for the current user.
     *
     * @param array $body
     * @return mixed
     */
    public function post(array $body) {
        $this->permission();

        $in = $this->schema([
            'name:s' => 'A name indicating what the access token will be used for.',
            'transientKey:s' => 'A valid CSRF token for the current user.'
        ], 'in');
        $out = $this->schema([
            'accessTokenID:i',
            'name:s',
            'accessToken:s',
            'dateInserted:dt'
        ], 'out');

        $body = $in->validate($body);
        $this->validateTK($body['transientKey']);

        // Issue the new token.
        $accessToken = $this->accessTokenModel->issue(
            $this->session->UserID,
            self::DEFAULT_EXPIRY,
            'personal'
        );
        $this->validateModel($this->accessTokenModel);
        $token = $this->accessTokenModel->trim($accessToken);
        $row = $this->accessTokenModel->getToken($token);
        $accessTokenID = $row['AccessTokenID'];
        $row = $this->accessTokenModel->setAttribute($accessTokenID, 'Name', $body['name']);

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
    public function prepareRow(&$row) {
        if (array_key_exists('Attributes', $row)) {
            if (array_key_exists('Name', $row['Attributes']) && is_string($row['Attributes']['Name'])) {
                $row['Name'] = $row['Attributes']['Name'];
            }
        }
        if (array_key_exists('Token', $row) && is_string($row['Token'])) {
            $row['AccessToken'] = $this->accessTokenModel->signToken($row['Token']);
        }
    }

    /**
     * Validate the transient key for the current request.
     *
     * @param $transientKey
     * @throws ClientException
     */
    public function validateTK($transientKey) {
        if ($this->session->transientKey() === false) {
            $this->session->loadTransientKey();
        }

        if ($this->session->transientKey() != $transientKey) {
            throw new ClientException('Invalid transient key.', 401);
        }
    }
}

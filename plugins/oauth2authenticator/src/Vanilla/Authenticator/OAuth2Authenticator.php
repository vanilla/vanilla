<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Authenticator;

use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Vanilla\Models\SSOData;

class OAuth2Authenticator extends SSOAuthenticator {

    /** @var array */
    public $providerData;

    /**
     * OAuth2Authenticator constructor.
     *
     * @param string $authenticatorID
     * @param \Gdn_AuthenticationProviderModel $authProviderModel
     */
    public function __construct(
        $authenticatorID,
        \Gdn_AuthenticationProviderModel $authProviderModel
    ) {
        if (empty($authenticatorID)) {
            $authenticatorID = 'oauth2';
        }

        parent::__construct($authenticatorID);

        $this->providerData = $authProviderModel->getProviderByKey($authenticatorID);

        if (empty($this->providerData)) {
            throw new Exception('Empty providerData in OAuth2Authenticator.');
        }
    }

    /**
     * @inheritDoc
     */
    public function registrationURL() {
        return $this->providerData['RegisterUrl'];
    }

    /**
     * @inheritdoc
     */
    public function signInURL() {
        return $this->providerData['SignInUrl'];
    }

    /**
     * @inheritDoc
     */
    public function signOutURL() {
        return $this->providerData['SignInOutUrl'];
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request) {
        try {
            $accessToken = $this->getAccessToken($request->getQuery()['code']);
        } catch (\Exception $e) {
            throw new ServerException('An error occurred during the request for the access token.', 500);
        }
        if (!$accessToken) {
            throw new ServerException('Access token could not be fetched.', 500);
        }

        try {
            $profileData = $this->getProfile($accessToken);
        } catch (\Exception $e) {
            throw new ServerException('An error occurred during the request for the profile.', 500);
        }
        if (!is_array($profileData)) {
            throw new ServerException('Invalid profile data.', 500);
        }


        $getNotEmpty = function($value, $default) {
            return !empty($value) ? $value : $default;
        };

        $uniqueID = $profileData[$getNotEmpty($this->providerData['ProfileKeyUniqueID'], 'user_id')] ?? null;
        $translatedProfileData = array_filter(
            [
                'email' => valr($getNotEmpty($this->providerData['ProfileKeyEmail'], 'email'), $profileData, null),
                'photo' => valr($getNotEmpty($this->providerData['ProfileKeyPhoto'], 'photo'), $profileData, null),
                'name' => valr($getNotEmpty($this->providerData['ProfileKeyName'], 'name'), $profileData, null),
                'roles' => valr($getNotEmpty($this->providerData['ProfileKeyRoles'], 'roles'), $profileData, null),
            ],
            function ($value) {
                return $value !== null;
            }
        );

        list($user, $extra) = SSOData::splitProviderData($translatedProfileData);

        return new SSOData(
            $this->getName(),
            $this->getID(),
            $this->isTrusted(),
            $uniqueID,
            $user,
            $extra
        );
    }

    /**
     * Exchange a code for an access token.
     *
     * @param string $code
     * @return string|bool Returns the access token or false.
     */
    private function getAccessToken($code) {
        $tokenURL = $this->providerData['TokenUrl'];

        $params = [
            'code' => $code,
            'client_id' => val('AssociationKey', $this->providerData),
            'redirect_uri' => url('/authenticate/'.strtolower($this->getName()), true),
            'grant_type' => 'authorization_code',
        ];

        $response = $this->proxyRequest($tokenURL, 'POST', $params);

        return $response['access_token'] ?? false;
    }

    /**
     * Retrieve profile data using the access token.
     *
     * @param string $accessToken
     * @return mixed
     */
    private function getProfile($accessToken) {
        $profileURL = $this->providerData['ProfileUrl'];

        $headers = [];
        $params = [];

        // Send the Access Token as an Authorization header, depending on the provider workflow.
        if (val('BearerToken', $this->providerData['BearerToken'])) {
            $headers['Authorization-Header-Message'] = 'Bearer '.$accessToken;
        } else {
            $params['access_token'] = $accessToken;
        }

        return $this->proxyRequest($profileURL, 'GET', $params, $headers);
    }

    /**
     * Execute a server to server request.
     *
     * @throws \Exception
     * @param string $url
     * @param string $method GET or POST
     * @param array $params If GET extra query parameter, otherwise POST body.
     * @param array $headers
     * @return mixed
     */
    private function proxyRequest($url, $method, $params = [], $headers = []) {
        $proxy = new \ProxyRequest();

        $proxyOptions = [
            'URL' => $url,
            'Method' => $method,
            'ConnectTimeout' => 10,
            'Timeout' => 10,
            'Log' => true,
        ];

        $response = $proxy->request(
            $proxyOptions,
            $params,
            null,
            $headers
        );

        // Return any errors
        if (!$proxy->responseClass('2xx')) {
            throw new \Exception('Request error: '.print_r($response, true), $proxy->ResponseStatus);
        }

        // Extract response only if it arrives as JSON
        if (stripos($proxy->ContentType, 'application/json') !== false) {
            $response = json_decode($proxy->ResponseBody, true);
        }

        return $response;
    }

}

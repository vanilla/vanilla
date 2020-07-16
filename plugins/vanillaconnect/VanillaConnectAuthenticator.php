<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\VanillaConnect;

use Exception;
use Firebase\JWT\JWT;
use Garden\Schema\Schema;
use Garden\Web\Cookie;
use Garden\Web\RequestInterface;
use Gdn_Configuration;
use SsoUtils;
use UserAuthenticationNonceModel;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;

/**
 * Class VanillaConnectAuthenticator
 */
class VanillaConnectAuthenticator extends SSOAuthenticator {

    /** Signing algorithm for JWT tokens. */
    const JWT_ALGORITHM = 'HS256';

    /** @var Cookie $cookie */
    private $cookie;

    /** @var string */
    private $cookieName;

    /** @var string */
    private $cookieSalt;

    /** @var RequestInterface */
    private $request;

    /** @var UserAuthenticationNonceModel */
    private $nonceModel;

    /** @var VanillaConnect */
    private $vanillaConnect;

    /**
     * VanillaConnectAuthenticator constructor.
     *
     * @throws Exception
     *
     * @param string $authenticatorID
     * @param AuthenticatorModel $authenticatorModel
     * @param Gdn_Configuration $config
     * @param Cookie $cookie
     * @param RequestInterface $request
     * @param UserAuthenticationNonceModel $nonceModel
     */
    public function __construct(
        $authenticatorID,
        AuthenticatorModel $authenticatorModel,
        Gdn_Configuration $config,
        Cookie $cookie,
        RequestInterface $request,
        UserAuthenticationNonceModel $nonceModel
    ) {
        $this->nonceModel = $nonceModel;
        $this->request = $request;

        $this->cookie = $cookie;
        $this->cookieName = '-vanillaconnectnonce';
        $this->cookieSalt = $config->get('Garden.Cookie.Salt');

        parent::__construct($authenticatorID, $authenticatorModel);
    }

    /**
     * @inheritdoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                // TODO fill these with proper value for the UI.
                'photoUrl' => null,
                'backgroundColor' => null,
                'foregroundColor' => null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAuthenticatorSchema(): Schema {
        $schema = parent::getAuthenticatorSchema()->merge(
            Schema::parse([
                'vanillaConnect:o' => Schema::parse([
                    'clientID:s' => [
                        'description' => 'VanillaConnect\'s client identifier.',
                        'x-instance-required' => true,
                        'x-instance-configurable' => true,
                    ],
                    'secret:s'=> [
                        'description' => 'VanillaConnect\'s client secret.',
                        'x-instance-required' => true,
                        'x-instance-configurable' => true,
                    ],
                ])
            ])
        );

        $removeConfigurable = [
            'properties.sso.properties.canLinkSession',
            'properties.sso.properties.canSignIn',
        ];

        foreach ($removeConfigurable as $fieldPath) {
            $field = $schema->getField($fieldPath);
            unset($field['x-instance-configurable']);
            $schema->setField($fieldPath, $field);
        }

        return $schema;
    }

    /**
     * @inheritdoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        $data = parent::getAuthenticatorInfoImpl();

        $data['vanillaConnect'] = [
            'clientID' => $this->vanillaConnect->getClientID(),
            'secret' => $this->vanillaConnect->getSecret(),
        ];

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function setAuthenticatorInfo(array $data) {
        parent::setAuthenticatorInfo($data);

        $this->vanillaConnect = new VanillaConnect($data['vanillaConnect']['clientID'], $data['vanillaConnect']['secret']);
    }

    /**
     * Getter of clientID;
     *
     * @return string
     */
    public function getClientID() {
        return $this->vanillaConnect->getClientID();
    }

    /**
     * Transform a claim to a SSOData object.
     *
     * @param $claim
     *
     * @return SSOData
     * @throws \Exception
     */
    private function claimToSSOData($claim) {
        $uniqueID = $claim['id'];

        foreach (array_keys(VanillaConnect::JWT_RESPONSE_CLAIM_TEMPLATE) as $key) {
            unset($claim[$key]);
        }

        list($userData, $extraData) = SSOData::splitProviderData($claim);

        $ssoData = new SSOData(
            $this::getType(),
            $this->getID(),
            $uniqueID,
            $userData,
            $extraData
        );
        $ssoData->validate();

        return $ssoData;
    }

    /**
     * Get URL where the authentication response needs to be sent to.
     *
     * @param string $target
     * @return string
     */
    private function getRedirectURL($target = null) {
        $url = $this->request->getScheme().'://'.$this->request->getHost().'/authenticate/'.$this::getType().'/'.rawurlencode($this->getID());

        if ($target) {
            $target = safeURL($target);
            $url .= '?target='.rawurlencode($target);
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public static function isUnique(): bool {
        return false;
    }

    /**
     * Extract `target=?` from the URL and return both the transformed URL and the target.
     *
     * @param $url
     * @return array [$url, $target]
     */
    private function extractTargetFromURL($url) {
        $query = [];
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $query = array_change_key_case($query, CASE_LOWER);

        $target = null;
        if (!empty($query['target'])) {
            $target = $query['target'];
            $url = str_ireplace('target='.$query['target'], '', $url);
            $url = str_replace('&&', '', $url);
            $url = rtrim($url, '?&');
        }

        if ($target === '{target}') {
            $query = $this->request->getQuery();
            $query = array_change_key_case($query, CASE_LOWER);
            if (!empty($query['target'])) {
                $target = $query['target'];
            }
        }

        return [$url, $target];
    }

    /**
     * Generate a nonce.
     *
     * @return string
     */
    private function generateNonce() {
        $nonce = uniqid(VanillaConnect::NAME.'_');
        $this->nonceModel->insert(['Nonce' => $nonce, 'Token' => VanillaConnect::NAME]);
        $iat = time();
        $expiration = $iat + VanillaConnect::TIMEOUT;
        $jwt = JWT::encode([
            'nonce' => $nonce,
            'exp' => $expiration,
            'iat' => $iat,
        ], $this->cookieSalt, self::JWT_ALGORITHM);
        $this->cookie->set($this->cookieName, $jwt, VanillaConnect::TIMEOUT);
        return $nonce;
    }

    /**
     * @inheritdoc
     */
    public function initiateAuthentication() {
        list($url, $target) = $this->extractTargetFromURL(parent::getSignInUrl());
        $url .= (strpos($url, '?') === false ? '?' : '&');
        $url .= 'jwt='.$this->vanillaConnect->createRequestAuthJWT(
            $this->generateNonce(),
            [
                'redirect' => $this->getRedirectURL($target),
                'authenticatorID' => $this->getID(),
            ]
        );

        return redirectTo($url, 302, false);
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request): SSOData {
        $query = $request->getQuery();
        if (empty($query['jwt'])) {
            throw new Exception('Missing parameter "jwt" from query string.');
        }

        if (!$this->vanillaConnect->validateResponse($query['jwt'], $claim)) {
            throw new Exception("An error occurred during the JWT validation.\n".print_r($this->vanillaConnect->getErrors(), true));
        }

        $this->validateNonce($claim['jti']);

        return $this->claimToSSOData($claim);
    }

    /**
     * Validate that the nonce exists and is not expired.
     *
     * @throws Exception
     *
     * @param string $nonce
     */
    private function validateNonce($nonce) {
        $nonceData = $this->nonceModel->getWhere(['Nonce' => $nonce])->firstRow(DATASET_TYPE_ARRAY);
        if (!$nonceData) {
            throw new Exception('Non-existent nonce supplied.');
        }

        // Consume the nonce.
        $this->nonceModel->delete(['Nonce' => $nonce, 'Token' => VanillaConnect::NAME]);

        if (strtotime($nonceData['Timestamp']) < time() - VanillaConnect::TIMEOUT) {
            throw new Exception('The nonce has expired.');
        }
        $jwt = $this->cookie->get($this->cookieName);
        if ($jwt) {
            try {
                $decoded = (array)JWT::decode($jwt, $this->cookieSalt, [self::JWT_ALGORITHM]);
                $cookiedNonce = $decoded['nonce'] ?? null;
            } catch (Exception $e) {}
        }
        if (!$cookiedNonce || $cookiedNonce !== $nonce) {
            throw new Exception('Nonce does not match cookied value.');
        }
    }


    /**
     * Validate a push sso JWT.
     *
     * @throws Exception
     *
     * @param string $jwt JSON Web Token
     * @return SSOData
     */
    public function validatePushSSOAuthentication($jwt) {
        if (!$this->vanillaConnect->validateResponse($jwt, $claim)) {
            throw new Exception("An error occurred during the JWT validation.\n".print_r($this->vanillaConnect->getErrors(), true));
        }
        if (!isset($claim['aud'])) {
            throw new Exception("Missing 'aud' item from JWT's claim.");
        }
        if ($claim['aud'] !== 'pushsso') {
            throw new Exception('Invalid JWT audience.');
        }
        $this->validateReverseNonce($claim['nonce']);
        return $this->claimToSSOData($claim);
    }
    /**
     * Validate and store the reverse nonce.
     *
     * @throws Exception If anything goes wrong.
     *
     * @param string $nonce
     */
    private function validateReverseNonce($nonce) {
        $noncePrefix = VanillaConnect::NAME.'_rn_';
        if (substr($nonce, 0, strlen($noncePrefix)) !== $noncePrefix) {
            throw new Exception('Invalid reverse nonce format.');
        }
        $nonceData = $this->nonceModel->getWhere(['Nonce' => $nonce])->firstRow(DATASET_TYPE_ARRAY);
        if ($nonceData) {
            throw new Exception('The reverse nonce has already been defined.');
        }
        $this->nonceModel->insert([
            'Nonce' => $nonce,
            'Token' => VanillaConnect::NAME,
        ]);
    }
}

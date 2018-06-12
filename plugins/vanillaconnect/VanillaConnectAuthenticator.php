<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\VanillaConnect;

use Exception;
use Garden\Schema\Schema;
use Garden\Web\RequestInterface;
use SsoUtils;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\AuthenticatorModel;
use Vanilla\Models\SSOData;

/**
 * Class VanillaConnectAuthenticator
 */
class VanillaConnectAuthenticator extends SSOAuthenticator {

    /** Signing algorithm for JWT tokens. */
    const JWT_ALGORITHM = 'HS256';

    /** @var RequestInterface */
    private $request;

    /** @var SsoUtils */
    private $ssoUtils;

    /** @var VanillaConnect */
    private $vanillaConnect;

    /**
     * VanillaConnectAuthenticator constructor.
     *
     * @throws Exception
     *
     * @param string $authenticatorID
     * @param AuthenticatorModel $authenticatorModel
     * @param RequestInterface $request
     * @param SSOUtils $ssoUtils
     */
    public function __construct(
        $authenticatorID,
        AuthenticatorModel $authenticatorModel,
        RequestInterface $request,
        SsoUtils $ssoUtils
    ) {
        $this->ssoUtils = $ssoUtils;
        $this->request = $request;

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
     * @inheritdoc
     */
    public function initSSOAuthentication() {
        list($url, $target) = $this->extractTargetFromURL(parent::getSignInUrl());
        $url .= (strpos($url, '?') === false ? '?' : '&');
        $url .= 'jwt='.$this->vanillaConnect->createRequestAuthJWT(
            $this->ssoUtils->getStateToken(),
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

        $this->ssoUtils->verifyStateToken(self::getType().'-'.$this->getID().'-'.'sso', $claim['jti']);

        return $this->claimToSSOData($claim);
    }
}

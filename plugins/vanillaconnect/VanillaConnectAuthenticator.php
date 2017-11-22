<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\VanillaConnect;

use Exception;
use Garden\Web\RequestInterface;
use Gdn_AuthenticationProviderModel;
use UserAuthenticationNonceModel;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;

/**
 * Class VanillaConnectAuthenticator
 *
 * @package Vanilla\VanillaConnect
 */
class VanillaConnectAuthenticator extends SSOAuthenticator {

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $providerData;

    /**
     * @var VanillaConnect
     */
    private $vanillaConnect;

    /**
     * @var UserAuthenticationNonceModel
     */
    private $nonceModel;

    /**
     * VanillaConnectAuthenticator constructor.
     *
     * @throws Exception
     *
     * @param string $providerID
     * @param Gdn_AuthenticationProviderModel $authProviderModel
     * @param RequestInterface $request
     * @param UserAuthenticationNonceModel $nonceModel
     */
    public function __construct(
        $providerID,
        Gdn_AuthenticationProviderModel $authProviderModel,
        RequestInterface $request,
        UserAuthenticationNonceModel $nonceModel
    ) {
        if (empty($providerID)) {
            throw new Exception("Empty providerID supplied to VanillaConnect.");
        }

        parent::__construct($providerID);

        $this->nonceModel = $nonceModel;
        $this->request = $request;

        $this->providerData = $authProviderModel->getProviderByKey($providerID);
        if (!$this->providerData) {
            throw new Exception("Provider '$providerID' was not found.'");
        }

        // TODO: these two should probably be moved some place where it can be reused.
        if (!$this->providerData['Active']) {
            throw new Exception("Provider \"$providerID\" is not not active.");
        }
        if ($this->providerData['AuthenticationSchemeAlias'] !== VanillaConnect::NAME) {
            throw new Exception("Provider '$providerID' is not of type ".VanillaConnect::NAME.".'");
        }

        $this->vanillaConnect = new VanillaConnect($providerID, $this->providerData['AssociationSecret']);
        $this->setTrusted(true);
    }

    /**
     * Transform a claim to a SSOData object.
     *
     * @param $claim
     * @return SSOData
     */
    private function claimToSSOData($claim) {
        $claim['uniqueID'] = $claim['id'];

        foreach (array_keys(VanillaConnect::JWT_RESPONSE_CLAIM_TEMPLATE) as $key) {
            unset($claim[$key]);
        }

        $claim['authenticatorID'] = $this->getID();
        $claim['authenticatorName'] = $this->getName();
        $claim['authenticatorIsTrusted'] = $this->isTrusted();

        $ssoData = new SSOData($claim);
        $ssoData->validate();

        return $ssoData;
    }

    /**
     * Consume a nonce.
     *
     * @param $nonce
     */
    private function consumeNonce($nonce) {
        $this->nonceModel->delete(['Nonce' => $nonce, 'Token' => VanillaConnect::NAME]);
    }

    /**
     * Generate a nonce.
     *
     * @return string
     */
    private function generateNonce() {
        $nonce = uniqid(VanillaConnect::NAME.'_');
        $this->nonceModel->insert(['Nonce' => $nonce, 'Token' => VanillaConnect::NAME]);
        return $nonce;
    }

    /**
     * Get URL where the authentication response needs to be sent to.
     *
     * @param string|null $target
     * @return string
     */
    private function getRedirectURL($target = null) {
        $url = $this->request->getScheme().'://'.$this->request->getHost().'/authenticate/'.$this->getName().'/'.rawurlencode($this->getID());

        if ($target) {
            $target = safeURL($target);
            $url .= '?target='.rawurlencode($target);
        }

        return $url;
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
     * @inheritDoc
     */
    public function registrationURL() {
        list($url, $target) = $this->extractTargetFromURL($this->providerData['RegisterUrl']);
        $url .= (strpos($url, '?') === false ? '?' : '&');
        $url .= 'jwt='.$this->vanillaConnect->createRequestAuthJWT($this->generateNonce(), ['redirect' => $this->getRedirectURL($target)]);
        return $url;
    }

    /**
     * @inheritdoc
     */
    public function signInURL() {
        list($url, $target) = $this->extractTargetFromURL($this->providerData['SignInUrl']);
        $url .= (strpos($url, '?') === false ? '?' : '&');
        $url .= 'jwt='.$this->vanillaConnect->createRequestAuthJWT($this->generateNonce(), ['redirect' => $this->getRedirectURL($target)]);
        return $url;
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
        $query = $request->getQuery();
        if (empty($query['jwt'])) {
            throw new Exception('Missing parameter "jwt" from query string.');
        }

        if (!$this->vanillaConnect->validateResponse($query['jwt'], $claim)) {
            throw new Exception("An error occurred during the JWT validation.\n".print_r($this->vanillaConnect->getErrors(), true));
        }

        $this->validateNonce($claim['nonce']);
        $this->consumeNonce($claim['nonce']);

        return $this->claimToSSOData($claim);
    }

    /**
     * Validate that the nonce exists and is not expired.
     *
     * @throws Exception
     *
     * @param string $nonce
     * @return bool
     */
    private function validateNonce($nonce) {
        $nonceData = $this->nonceModel->getWhere(['Nonce' => $nonce])->firstRow(DATASET_TYPE_ARRAY);
        if (!$nonceData) {
            throw new Exception('Non-existent nonce supplied.');
        }
        if (strtotime($nonceData['Timestamp']) < time() - VanillaConnect::TIMEOUT) {
            throw new Exception('The nonce has expired.');
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

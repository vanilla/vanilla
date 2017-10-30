<?php

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;

class TestSSOAuthenticator extends SSOAuthenticator {

    /**
     * @var array
     */
    private $ssoInfo;

    /**
     * TestSSOAuthenticator constructor.
     */
    public function __construct() {
        parent::__construct('TestSSO');
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request) {
        return new SSOData($this->ssoInfo);
    }

    /**
     * @return array
     */
    public function getSSOInfo() {
        return $this->ssoInfo;
    }

    /**
     * @param array $ssoInfo
     */
    public function setSSOInfo(array $ssoInfo) {
        $this->ssoInfo = $ssoInfo;
    }

    /**
     * @inheritDoc
     */
    public function setTrusted($isTrusted) {
        parent::setTrusted($isTrusted);
    }

    /**
     * @inheritDoc
     */
    public function registrationURL() {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function signInURL() {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function signOutURL() {
        return '';
    }
}

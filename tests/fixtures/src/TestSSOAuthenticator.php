<?php

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;

class TestSSOAuthenticator extends SSOAuthenticator {

    /** @var string */
    private $uniqueID;

    /** @var array */
    private $userData = [];

    /** @var array */
    private $extraData = [];

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
        return new SSOData(
            $this->getName(),
            $this->getID(),
            $this->isTrusted(),
            $this->uniqueID,
            $this->userData,
            $this->extraData
        );
    }

    /**
     * @return array
     */
    public function getUserData() {
        return $this->userData;
    }

    /**
     * @param array $userData
     */
    public function setUserData(array $userData) {
        $this->userData = $userData;
    }

    /**
     * @return string
     */
    public function getUniqueID() {
        return $this->uniqueID;
    }

    /**
     * @param string $uniqueID
     */
    public function setUniqueID($uniqueID) {
        $this->uniqueID = $uniqueID;
    }

    /**
     * @return array
     */
    public function getExtraData() {
        return $this->extraData;
    }

    /**
     * @param array $extraData
     */
    public function setExtraData(array $extraData) {
        $this->extraData = $extraData;
    }

    /**
     * @inheritDoc
     */
    public function setTrusted(bool $isTrusted): bool {
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

<?php

namespace VanillaTests\Fixtures;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\SSOAuthenticator;
use Vanilla\Models\SSOData;

class MockSSOAuthenticator extends SSOAuthenticator {

    /** @var SSOData */
    protected $data;

    /**
     * MockSSOAuthenticator constructor.
     *
     * @param $uniqueID
     * @param $userData
     * @param $extraData
     */
    public function __construct($uniqueID, $userData = [], $extraData = []) {
        parent::__construct('MockSSO');
        $this
            ->setData(new SSOData(
                self::getType(),
                $this->getID(),
                $uniqueID,
                $userData,
                $extraData
            ))
            ->setTrusted(true)
            ->setSignIn(true)
        ;
    }

    /**
     * @inheritDoc
     */
    protected function sso(RequestInterface $request): SSOData {
        return $this->getData();
    }

    /**
     * Getter of data.
     *
     * @return SSOData
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Setter of data.
     *
     * @param SSOData $data
     * @return self
     */
    public function setData(SSOData $data): self {
        $this->data = $data;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => 'http://www.example.com/image.jpg',
                'backgroundColor' => '#ffffff',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => 'Sign in with MockAuthenticator',
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRegisterUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignInUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignOutUrl() {
        return null;
    }


}

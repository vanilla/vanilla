<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Authenticator;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\Exception;

class MockAuthenticator extends Authenticator {

    /** @var bool */
    protected $active = true;

    /** @var array */
    protected $data = [];

    /**
     * MockAuthenticator constructor.
     */
    public function __construct() {
        parent::__construct('Mock');
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool {
        return $this->active;
    }

    /**
     * @inheritDoc
     */
    public function setActive(bool $active) {
        $this->active = $active;

        return $this;
    }


    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data) {
        $this->data = $data;
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
        return '/MockAuthenticatorSignIn';
    }

    /**
     * @inheritDoc
     */
    public function getSignOutUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => 'http://www.example.com/image.jpg',
                'backgroundColor' => '#ffffff',
                'foregroundColor' => '#000000',
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
    public function validateAuthenticationImpl(RequestInterface $request) {
        return $this->data;
    }
}

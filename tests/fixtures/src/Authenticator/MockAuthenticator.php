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

class MockAuthenticator extends Authenticator
{
    /** @var bool */
    protected $active = true;

    /** @var array */
    protected $data = [];

    /**
     * MockAuthenticator constructor.
     */
    public function __construct()
    {
        parent::__construct("Mock");
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @inheritdoc
     */
    public function setActive(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function getRegisterUrl()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSignInUrl()
    {
        return "/MockAuthenticatorSignIn";
    }

    /**
     * @inheritdoc
     */
    public function getSignOutUrl()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array
    {
        return [
            "ui" => [
                "photoUrl" => "http://www.example.com/image.jpg",
                "backgroundColor" => "#ffffff",
                "foregroundColor" => "#000000",
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function isUnique(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function getAuthenticatorInfoImpl(): array
    {
        return [
            "ui" => [
                "buttonName" => "Sign in with MockAuthenticator",
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function validateAuthenticationImpl(RequestInterface $request)
    {
        return $this->data;
    }
}

<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Authenticator;

use Garden\Web\RequestInterface;
use Gdn_Configuration;
use UserModel;

/**
 * Class PasswordAuthenticator
 */
class PasswordAuthenticator extends Authenticator {

    /** @var Gdn_Configuration */
    private $config;

    /** @var UserModel */
    private $userModel;

    /**
     * PasswordAuthenticator constructor.
     *
     * @param Gdn_Configuration $config
     * @param UserModel $userModel
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(Gdn_Configuration $config, UserModel $userModel) {
        $this->config = $config;
        $this->userModel = $userModel;

        parent::__construct('Password');
    }

    /**
     * @inheritdoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => null,
                'backgroundColor' => null,
                'foregroundColor' => null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => t('Sign in'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function setActive(bool $active) {
        $this->config->set('Garden.SignIn.DisablePassword', !$active);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool {
        return !$this->config->get('Garden.SignIn.DisablePassword', false);
    }

    /**
     * @inheritdoc
     */
    public function getRegisterUrl() {
        return '/entry/register';
    }

    /**
     * @inheritdoc
     */
    public function getSignInUrl() {
        return '/authenticate/password';
    }

    /**
     * @inheritdoc
     */
    public function getSignOutUrl() {
        return '/entry/signout';
    }

    /**
     * @inheritdoc
     */
    public function validateAuthenticationImpl(RequestInterface $request) {
        $body = $request->getBody();
        $user = $this->userModel->validateCredentials($body['username'] ?? null, 0, $body['password'] ?? null, true);
        if ($user) {
            $user = (array)$user;
        }
        return $user;
    }

}

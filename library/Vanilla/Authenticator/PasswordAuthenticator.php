<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Authenticator;

use Garden\Web\RequestInterface;
use UserModel;

/**
 * Class PasswordAuthenticator
 */
class PasswordAuthenticator extends Authenticator {

    /** @var UserModel */
    private $userModel;

    /**
     * PasswordAuthenticator constructor.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel) {
        parent::__construct('Password');

        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => '/applications/dashboard/design/images/password-authenticator.svg',
                'backgroundColor' => '#0291db',
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
    public function validateAuthentication(RequestInterface $request) {
        $body = $request->getBody();
        return (array)$this->userModel->validateCredentials($body['username'] ?? null, 0, $body['password'] ?? null, true);
    }

}

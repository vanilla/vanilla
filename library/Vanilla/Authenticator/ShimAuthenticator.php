<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Authenticator;

use Garden\Web\Exception\ServerException;


/**
 * Trait ShimAuthenticator.
 */
abstract class ShimAuthenticator extends Authenticator {

    public function __construct(string $authenticatorID) {
        parent::__construct($authenticatorID);
    }

    /**
     * {@link Authenticator::getRegisterUrl()}
     */
    public function getRegisterUrl() {
        return null;
    }

    /**
     * {@link Authenticator::getSignInUrl()}
     */
    public function getSignInUrl() {
        return null;
    }

    /**
     * {@link Authenticator::getSignOutUrl()}
     */
    public function getSignOutUrl() {
        return null;
    }

    /**
     * {@link Authenticator::validateAuthentication()}
     */
    public function validateAuthenticationImpl(\Garden\Web\RequestInterface $request) {
        throw new ServerException('Method not implemented', 501);
    }

    /**
     * {@link Authenticator::getAuthenticatorInfoImpl()}
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'url' => '/entry/'.strtolower(self::getType()),
                'buttonName' => sprintft('Sign In with %s', self::getType()),
            ],
        ];
    }
}

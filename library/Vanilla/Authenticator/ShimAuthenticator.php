<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Authenticator;

use Garden\Web\Exception\ServerException;
use \Garden\Web\RequestInterface;


/**
 * Trait ShimAuthenticator.
 */
abstract class ShimAuthenticator extends Authenticator {

    /**
     * ShimAuthenticator constructor.
     *
     * @param string $authenticatorID
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(string $authenticatorID) {
        parent::__construct($authenticatorID);
    }

    /**
     * @inheritdoc
     */
    public function getRegisterUrl() {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSignInUrl() {
        return url('/entry/signin');
    }

    /**
     * @inheritdoc
     */
    public function getSignOutUrl() {
        return null;
    }

    public function setActive(bool $active) {
        throw new ServerException('Method not implemented.', 501);
    }

    /**
     * @inheritdoc
     */
    public function validateAuthenticationImpl(RequestInterface $request) {
        throw new ServerException('Method not implemented.', 501);
    }

    /**
     * @inheritdoc
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

<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\Authenticator;
use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class OAuth2Authenticator.
 */
class OAuth2Authenticator extends ShimAuthenticator {

    /**
     * OAuth2Authenticator constructor.
     *
     * @param string $authenticatorID
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(string $authenticatorID) {
        parent::__construct('OAuth2');
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => null,
                'backgroundColor' => null,
                'foregroundColor' => null,
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }
}

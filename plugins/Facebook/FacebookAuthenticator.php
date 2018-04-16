<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class FacebookAuthenticator.
 */
class FacebookAuthenticator extends ShimAuthenticator {

    /**
     * FacebookAuthenticator constructor.
     *
     * @param string $authenticatorID
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(string $authenticatorID) {
        parent::__construct('Facebook');
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => '/applications/dashboard/design/images/authenticators/facebook.svg',
                'backgroundColor' => '#4A70BD',
                'foregroundColor' => '#fff',
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

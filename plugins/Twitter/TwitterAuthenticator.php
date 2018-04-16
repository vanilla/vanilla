<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class TwitterAuthenticator.
 */
class TwitterAuthenticator extends ShimAuthenticator {


    /**
     * TwitterAuthenticator constructor.
     *
     * @param string $authenticatorID
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(string $authenticatorID) {
        parent::__construct('Twitter');
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => '/applications/dashboard/design/images/authenticators/twitter.svg',
                'backgroundColor' => '#1DA1F2',
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

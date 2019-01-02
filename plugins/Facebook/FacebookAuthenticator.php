<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class FacebookAuthenticator.
 */
class FacebookAuthenticator extends ShimAuthenticator {

    /** @var FacebookPlugin */
    private $facebookPlugin;

    /**
     * FacebookAuthenticator constructor.
     *
     * @param FacebookPlugin $facebookPlugin
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(FacebookPlugin $facebookPlugin) {
        $this->facebookPlugin = $facebookPlugin;

        parent::__construct('Facebook');
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function isActive(): bool {
        return $this->facebookPlugin->socialSignIn();
    }

    /**
     * @inheritdoc
     */
    public static function isUnique(): bool {
        return true;
    }
}

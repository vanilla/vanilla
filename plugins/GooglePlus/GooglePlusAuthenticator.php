<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class GoogleAuthenticator.
 */
class GooglePlusAuthenticator extends ShimAuthenticator {

    /** @var GooglePlusPlugin */
    private $googlePlusPlugin;

    /**
     * GoogleAuthenticator constructor.
     *
     * @param GooglePlusPlugin $googlePlusPlugin
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(GooglePlusPlugin $googlePlusPlugin) {
        $this->googlePlusPlugin = $googlePlusPlugin;

        parent::__construct('GooglePlus');
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => '/applications/dashboard/design/images/authenticators/google.svg',
                'backgroundColor' => '#fff',
                'foregroundColor' => '#000',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool {
        return $this->googlePlusPlugin->isConfigured();
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * {@link Authenticator::getAuthenticatorInfoImpl()}
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'url' => '/entry/googlePlusAuthRedirect',
                'buttonName' => sprintft('Sign In with %s', 'Google'),
            ],
        ];
    }
}

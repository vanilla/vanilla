<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class TwitterAuthenticator.
 */
class TwitterAuthenticator extends ShimAuthenticator {

    /** @var TwitterPlugin */
    private $twitterPlugin;

    /**
     * TwitterAuthenticator constructor.
     *
     * @param TwitterPlugin $twitterPlugin
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(TwitterPlugin $twitterPlugin) {
        $this->twitterPlugin = $twitterPlugin;

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
     * @inheritdoc
     */
    public function isActive(): bool {
        return $this->twitterPlugin->socialSignIn();
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
                'url' => '/entry/twauthorize',
                'buttonName' => sprintft('Sign In with %s', self::getType()),
            ],
        ];
    }
}

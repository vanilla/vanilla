<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use \Vanilla\Authenticator\Authenticator;
use \Vanilla\Authenticator\ShimAuthenticator;

/**
 * Class OAuth2Authenticator.
 */
class OAuth2Authenticator extends ShimAuthenticator {

    /** @var OAuth2Plugin */
    private $oAuth2Plugin;

    /**
     * OAuth2Authenticator constructor.
     *
     * @param OAuth2Plugin $oAuth2Plugin
     *
     * @throws \Garden\Schema\ValidationException
     */
    public function __construct(OAuth2Plugin $oAuth2Plugin) {
        $this->oAuth2Plugin = $oAuth2Plugin;

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
     * @inheritdoc
     */
    public function isActive(): bool {
        return $this->oAuth2Plugin->isConfigured();
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }
}

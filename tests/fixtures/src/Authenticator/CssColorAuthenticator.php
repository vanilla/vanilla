<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\Authenticator;

use Garden\Web\RequestInterface;
use Vanilla\Authenticator\Authenticator;
use Vanilla\Authenticator\Exception;

/**
 * Class CssColorAuthenticator.
 */
class CssColorAuthenticator extends Authenticator {

    const DEFAULT_CSS_COLOR = '#FFFFFF';

    /** @var bool */
    protected $active = true;

    /** @var array */
    protected $data = [];

    protected static $cssColor = self::DEFAULT_CSS_COLOR;


    /**
     * MockAuthenticator constructor.
     */
    public function __construct() {
        parent::__construct('CssColor');
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool {
        return $this->active;
    }

    /**
     * @inheritDoc
     */
    public function setActive(bool $active) {
        $this->active = $active;

        return $this;
    }

    /**
     * Set the current cssColor
     *
     * @param $color
     */
    public static function setCssColor($color) {
        self::$cssColor = $color;
    }

    /**
     * Reset the current cssColor.
     */
    public static function resetColor() {
        self::$cssColor = self::DEFAULT_CSS_COLOR;
    }

    /**
     * @inheritDoc
     */
    protected static function getAuthenticatorTypeInfoImpl(): array {
        return [
            'ui' => [
                'photoUrl' => 'http://www.example.com/image.jpg',
                'backgroundColor' => self::$cssColor,
                'foregroundColor' => '#000000',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function isUnique(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getAuthenticatorInfoImpl(): array {
        return [
            'ui' => [
                'buttonName' => 'Sign in with MockAuthenticator',
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRegisterUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignInUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSignOutUrl() {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateAuthenticationImpl(RequestInterface $request) {
        throw new \Exception('Not implemented');
    }
}

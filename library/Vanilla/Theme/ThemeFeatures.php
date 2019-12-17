<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class to hold information about a theme and it's configuration options.
 */
class ThemeFeatures {

    /** @var Addon */
    private $theme;

    /** @var ConfigurationInterface */
    private $config;

    const FEATURE_DEFAULTS = [
        'NewFlyouts' => false,
        'SharedMasterView' => true,
        'ProfileHeader' => false,
    ];

    /**
     * Constuctor.
     *
     * @param Addon $theme
     * @param ConfigurationInterface $config
     */
    public function __construct(Addon $theme, ConfigurationInterface $config) {
        $this->theme = $theme;
        $this->config = $config;
    }

    /**
     * Get all of the current theme features.
     */
    public function allFeatures() {
        $configValues = [
            'NewFlyouts' => $this->config->get('Feature.NewFlyouts.Enabled'),
        ];
        $themeValues = $this->theme->getInfoValue('Features', []);
        return $themeValues + $configValues + self::FEATURE_DEFAULTS;
    }

    /**
     * @return bool
     */
    public function useNewFlyouts(): bool {
        return (bool) $this->allFeatures()['NewFlyouts'];
    }

    /**
     * @return bool
     */
    public function useSharedMasterView(): bool {
        return (bool) $this->allFeatures()['SharedMasterView'];
    }

    /**
     * @return bool
     */
    public function useProfileHeader(): bool {
        return (bool) $this->allFeatures()['ProfileHeader'];
    }
}

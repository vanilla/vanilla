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
        'SharedMasterView' => false,
        'ProfileHeader' => false,
        'DataDrivenTheme' => false,
    ];

    /**
     * Constuctor.
     *
     * @param ConfigurationInterface $config
     * @param Addon|null $theme
     */
    public function __construct(ConfigurationInterface $config, ?Addon $theme) {
        $this->config = $config;
        $this->theme = $theme;
    }

    /**
     * Get all of the current theme features.
     */
    public function allFeatures(): array {
        if ($this->theme === null) {
            return self::FEATURE_DEFAULTS;
        }
        $configValues = [
            'NewFlyouts' => $this->config->get('Feature.NewFlyouts.Enabled'),
        ];
        $themeValues = $this->theme->getInfoValue('Features', []);
        return array_merge(self::FEATURE_DEFAULTS, $configValues, $themeValues);
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

    /**
     * @return bool
     */
    public function useDataDrivenTheme(): bool {
        return (bool) $this->allFeatures()['DataDrivenTheme'];
    }
}

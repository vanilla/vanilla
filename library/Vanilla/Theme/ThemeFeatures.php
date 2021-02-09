<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Controllers\SearchRootController;
use Vanilla\FeatureFlagHelper;

/**
 * Class to hold information about a theme and it's configuration options.
 */
class ThemeFeatures implements \JsonSerializable {

    /** @var Addon */
    private $theme;

    /** @var ConfigurationInterface */
    private $config;

    private $forcedFeatures = [];

    const FEATURE_DEFAULTS = [
        // Used for keystone and newer to allow flyouts to convert to Modals o mobile.
        'NewFlyouts' => false,

        // Use twig master templates. You do not have access to the full master view.
        'SharedMasterView' => false,

        // Used foundation and some other themes, adds extra header information on top of the profile page.
        'ProfileHeader' => false,

        // Applies the Variabler driven CSS across the forum. (Eg. foundation based).
        'DataDrivenTheme' => false,

        // Turn on user cards.
        'UserCards' => false,

        // Disable legacy based variables.json.
        'DisableKludgedVars' => false,

        // Use the new event list page, and new event view page.
        'NewEventsPage' => false,

        // Enable the new search UI (member directory, places, new interface).
        SearchRootController::ENABLE_FLAG => false,

        // Make backwards-incompatbile view changes for better accessibility.
        'EnhancedAccessibility' => false,

        // Use the new themeable quicklinks.
        'NewQuickLinks' => false,
    ];

    /**
     * Constuctor.
     *
     * @param ConfigurationInterface $config
     * @param Addon $theme
     */
    public function __construct(ConfigurationInterface $config, Addon $theme) {
        $this->config = $config;
        $this->theme = $theme;
    }

    /**
     * Force some theme features to be active.
     *
     * @param array $forcedFeatures An array of Feature => boolean.
     */
    public function forceFeatures(array $forcedFeatures) {
        $this->forcedFeatures = $forcedFeatures;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize() {
        return $this->allFeatures();
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
        $rawThemeValues = $this->theme->getInfoValue('Features', []);
        $themeValues = $rawThemeValues;
        if ($themeValues['DataDrivenTheme'] ?? false) {
            // Data driven themes automatically enables other theme features.
            $themeValues['DisableKludgedVars'] = true;
            $themeValues['ProfileHeader'] = true;
            $themeValues['SharedMasterView'] = true;
            $themeValues['NewFlyouts'] = true;
            $themeValues['NewEventsPage'] = true;
            $themeValues['UserCards'] = true;
            $themeValues[SearchRootController::ENABLE_FLAG] = true;
            $themeValues['EnhancedAccessibility'] = true;
            $themeValues['NewQuickLinks'] = true;
        }

        // If someone has explicitly opted out with a false we want that to apply.
        $themeValues = array_merge($themeValues, $rawThemeValues);

        if (FeatureFlagHelper::featureEnabled('NewEventsPage')) {
            $themeValues['NewEventsPage'] = true;
        }

        if (FeatureFlagHelper::featureEnabled('NewQuickLinks')) {
            $themeValues['NewQuickLinks'] = true;
        }

        if (FeatureFlagHelper::featureEnabled(SearchRootController::ENABLE_FLAG)) {
            $themeValues[SearchRootController::ENABLE_FLAG] = true;
        }

        if (FeatureFlagHelper::featureEnabled('UserCards')) {
            $themeValues['UserCards'] = true;
        }

        if (FeatureFlagHelper::featureEnabled('EnhancedAccessibility')) {
            $themeValues['EnhancedAccessibility'] = true;
        }

        return array_merge(self::FEATURE_DEFAULTS, $configValues, $themeValues, $this->forcedFeatures);
    }

    /**
     * Get a theme feature.
     *
     * @param string $featureName The name of the feature.
     *
     * @return bool
     */
    public function get(string $featureName): bool {
        $result = $this->allFeatures()[$featureName] ?? false;
        return (bool) $result;
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
    public function useNewQuickLinks(): bool {
        return (bool) $this->allFeatures()['NewQuickLinks'];
    }

    /**
     * @return bool
     */
    public function useDataDrivenTheme(): bool {
        return (bool) $this->allFeatures()['DataDrivenTheme'];
    }

    /**
     * @return bool
     */
    public function disableKludgedVars(): bool {
        return (bool) $this->allFeatures()['DisableKludgedVars'];
    }
}

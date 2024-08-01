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
class ThemeFeatures implements \JsonSerializable
{
    /** @var Addon */
    private $theme;

    /** @var ConfigurationInterface */
    private $config;

    private $forcedFeatures = [];

    private const FEATURE_NAMES = [
        // Used for keystone and newer to allow flyouts to convert to Modals o mobile.
        "NewFlyouts",

        // Use twig master templates. You do not have access to the full master view.
        "SharedMasterView",

        // Used foundation and some other themes, adds extra header information on top of the profile page.
        "ProfileHeader",

        // Applies the Variabler driven CSS across the forum. (Eg. foundation based).
        "DataDrivenTheme",

        // Turn on user cards.
        "UserCards",

        // Disable legacy based variables.json.
        "DisableKludgedVars",

        // Use the new event list page, and new event view page.
        "NewEventsPage",

        // Enable the new search UI (member directory, places, new interface).
        SearchRootController::ENABLE_FLAG,

        // Make backwards-incompatbile view changes for better accessibility.
        "EnhancedAccessibility",

        // Use the new themeable quicklinks.
        "NewQuickLinks",

        // New button style dropdown
        "NewCategoryDropdown",

        // New badges module.
        "NewBadgesModule",

        // NewReactionsModule (icons and count) to replace writeProfileCounts()
        "NewReactionsModule",

        // NewGuestModule to replace the view with react component
        "NewGuestModule",

        // NewPostMenu for NewDiscussionModule
        "NewPostMenu",
    ];

    /**
     * Constuctor.
     *
     * @param ConfigurationInterface $config
     * @param Addon $theme
     */
    public function __construct(ConfigurationInterface $config, Addon $theme)
    {
        $this->config = $config;
        $this->theme = $theme;
    }

    /**
     * Force some theme features to be active.
     *
     * @param array $forcedFeatures An array of Feature => boolean.
     */
    public function forceFeatures(array $forcedFeatures)
    {
        $this->forcedFeatures = $forcedFeatures;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->allFeatures();
    }

    /**
     * Get theme features pulled directly from the addon.
     */
    public function allAddonFeatures(): array
    {
        $rawInfoFeatures = [];
        if ($this->theme !== null) {
            $rawInfoFeatures = $this->theme->getInfoValue("Features", []);
        }
        $defaultEnabled = (bool) ($rawInfoFeatures["DataDrivenTheme"] ?? false);

        $addonFeatures = [];
        foreach (self::FEATURE_NAMES as $featureName) {
            $addonFeatures[$featureName] = (bool) ($rawInfoFeatures[$featureName] ?? $defaultEnabled);
        }

        return $addonFeatures;
    }

    /**
     * Get all of the current theme features.
     */
    public function allFeatures(): array
    {
        $addonFeatures = $this->allAddonFeatures();

        $featureFlagEnabledFeatures = [];
        foreach (self::FEATURE_NAMES as $featureName) {
            if (FeatureFlagHelper::featureEnabled($featureName)) {
                $featureFlagEnabledFeatures[$featureName] = true;
            }
        }

        $features = array_merge(
            // Features from the theme first.
            $addonFeatures,
            // A feature flags that may have been turned on in the config or through Vanilla Labs.
            $featureFlagEnabledFeatures,
            // Feature flags that were dynamically forced at runtime.
            $this->forcedFeatures
        );

        return $features;
    }

    /**
     * Get a theme feature.
     *
     * @param string $featureName The name of the feature.
     *
     * @return bool
     */
    public function get(string $featureName): bool
    {
        $result = $this->allFeatures()[$featureName] ?? false;
        return (bool) $result;
    }

    /**
     * @return bool
     */
    public function useNewFlyouts(): bool
    {
        return (bool) $this->allFeatures()["NewFlyouts"];
    }

    /**
     * @return bool
     */
    public function useSharedMasterView(): bool
    {
        return (bool) $this->allFeatures()["SharedMasterView"];
    }

    /**
     * @return bool
     */
    public function useProfileHeader(): bool
    {
        return (bool) $this->allFeatures()["ProfileHeader"];
    }

    /**
     * @return bool
     */
    public function useNewQuickLinks(): bool
    {
        return (bool) $this->allFeatures()["NewQuickLinks"];
    }

    /**
     * @return bool
     */
    public function useDataDrivenTheme(): bool
    {
        return (bool) $this->allFeatures()["DataDrivenTheme"];
    }

    /**
     * @return bool
     */
    public function disableKludgedVars(): bool
    {
        return (bool) $this->allFeatures()["DisableKludgedVars"];
    }
}

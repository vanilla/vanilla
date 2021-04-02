<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Garden\Web\Exception\ServerException;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Site\SiteSectionModel;
use Garden\Web\Exception\ClientException;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use VanillaTests\Fixtures\QuickLinks\MockQuickLinksVariableProvider;

/**
 * Handle custom themes.
 */
class ThemeService {

    /**
     * When fetching the current theme, accurate assets will be prioritized. CurrentTheme > MobileTheme
     */
    const GET_THEME_MODE_PRIORITIZE_ASSETS = 'prioritizeAssets';

    /**
     * When fetching the current theme, an accurate addon will be prioritized. MobileTheme > CurrentTheme
     */
    const GET_THEME_MODE_PRIORITIZE_ADDON = 'prioritizeAddon';

    const FOUNDATION_THEME_KEY = "theme-foundation";
    const FALLBACK_THEME_KEY = self::FOUNDATION_THEME_KEY;

    /** @var ThemeProviderInterface[] */
    private $themeProviders = [];

    /** @var VariablesProviderInterface[] */
    private $variableProviders = [];

    /** @var VariableDefaultsProviderInterface[] */
    private $variableDefaultsProviders = [];

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var \Gdn_Session */
    private $session;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var AddonManager $addonManager */
    private $addonManager;

    /** @var ThemeServiceHelper $themeHelper */
    private $themeHelper;

    /** @var ThemeSectionModel */
    private $themeSections;

    /** @var string $themeManagePageUrl */
    private $themeManagePageUrl = '/dashboard/settings/themes';

    /** @var FsThemeProvider */
    private $fallbackThemeProvider;

    /** @var ThemeCache */
    private $cache;

    /**
     * ThemeService constructor.
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Session $session
     * @param AddonManager $addonManager
     * @param ThemeServiceHelper $themeHelper
     * @param ThemeSectionModel $themeSections
     * @param SiteSectionModel $siteSectionModel
     * @param FsThemeProvider $fallbackThemeProvider
     * @param ThemeCache $cache
     */
    public function __construct(
        ConfigurationInterface $config,
        \Gdn_Session $session,
        AddonManager $addonManager,
        ThemeServiceHelper $themeHelper,
        ThemeSectionModel $themeSections,
        SiteSectionModel $siteSectionModel,
        FsThemeProvider $fallbackThemeProvider,
        ThemeCache $cache
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->addonManager = $addonManager;
        $this->themeHelper = $themeHelper;
        $this->themeSections = $themeSections;
        $this->siteSectionModel = $siteSectionModel;
        $this->fallbackThemeProvider = $fallbackThemeProvider;
        $this->cache = $cache;
    }

    /**
     * Add a theme-variable provider.
     *
     * @param VariablesProviderInterface $provider
     */
    public function addVariableProvider(VariablesProviderInterface $provider) {
        $this->variableProviders[] = $provider;
        if ($provider instanceof VariableDefaultsProviderInterface) {
            $this->variableDefaultsProviders[] = $provider;
        }
    }

    /**
     * Clear all variable providers.
     */
    public function clearVariableProviders() {
        $this->variableProviders = [];
    }

    /**
     * Get all configured theme-variable providers.
     *
     * @return array
     */
    public function getVariableProviders(): array {
        return $this->variableProviders;
    }

    /**
     * Set custom theme provider.
     *
     * @param ThemeProviderInterface $provider
     */
    public function addThemeProvider(ThemeProviderInterface $provider) {
        $this->themeProviders[] = $provider;
    }

    /**
     * Get theme with all assets from provider detected
     *
     * @param string|int $themeKey Theme key or id
     * @param array $query Request query arguments
     * @return Theme
     */
    public function getTheme($themeKey, array $query = []): Theme {
        $cacheKey = $this->cache->cacheKey($themeKey, $query);
        $result = $this->cache->get($cacheKey);
        if ($result instanceof Theme) {
            return $this->normalizeTheme($result);
        }

        $provider = $this->getThemeProvider($themeKey);
        $theme = $provider->getTheme($themeKey, $query);
        $theme = $this->normalizeTheme($theme);
        $this->cache->set($cacheKey, $theme);
        return $theme;
    }

    /**
     * Get all available themes.
     *
     * @return Theme[]
     */
    public function getThemes(): array {
        $allThemes = [];
        foreach ($this->themeProviders as $themeProvider) {
            $themes = $themeProvider->getAllThemes();
            foreach ($themes as &$theme) {
                $theme = $this->normalizeTheme($theme);
                $allThemes[] = $theme;
            }
        }
        return $allThemes;
    }

    /**
     * Create new theme.
     *
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Theme
     */
    public function postTheme(array $body): Theme {
        $provider = $this->getWritableThemeProvider();
        $theme = $provider->postTheme($body);

        // Clear the cache.
        $this->cache->clear();

        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Update theme name by ID.
     *
     * @param string|int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return Theme
     */
    public function patchTheme($themeID, array $body): Theme {
        $provider = $this->getWritableThemeProvider($themeID);
        $theme = $provider->patchTheme($themeID, $body);

        // Clear the cache.
        $this->cache->clear();

        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Delete theme by ID.
     *
     * @param string|int $themeID Theme ID
     */
    public function deleteTheme($themeID) {
        $provider = $this->getWritableThemeProvider($themeID);
        $provider->deleteTheme($themeID);
        // Clear the cache.
        $this->cache->clear();
    }

    /**
     * Set current theme.
     *
     * @param int $themeID Theme ID to set current.
     * @return Theme
     */
    public function setCurrentTheme($themeID): Theme {
        $previousTheme = $this->getCurrentTheme();
        $previousProvider = $this->getThemeProvider($previousTheme->getThemeID());
        $newProvider = $this->getThemeProvider($themeID);
        $newTheme = $newProvider->setCurrentTheme($themeID);

        if ($previousProvider !== $newProvider && $previousProvider instanceof ThemeProviderCleanupInterface) {
            $previousProvider->afterCurrentProviderChange();
        }
        // Clear the cache.
        $this->cache->clear();

        $newTheme = $this->normalizeTheme($newTheme);
        return $newTheme;
    }

    /**
     * Set theme as preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @param int $revisionID Theme revision ID.
     * @return Theme
     */
    public function setPreviewTheme($themeID, ?int $revisionID = null): Theme {
        if (empty($themeID)) {
            $theme = $this->getCurrentTheme();
            $this->themeHelper->cancelSessionPreviewTheme();
        } else {
            $provider = $this->getThemeProvider($themeID);
            $theme = $provider->setPreviewTheme($themeID, $revisionID);
        }

        // No cache clearing required here. Themes are saved by their ID.

        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Get preview theme properties if exists.
     *
     * @return array
     */
    public function getPreviewTheme(): ?array {
        $previewTheme = null;
        if ($previewThemeKey = $this->session->getPreference('PreviewThemeKey')) {
            $previewTheme['themeID'] = $previewThemeKey;
            $theme = $this->getThemeProvider($previewThemeKey)->getTheme($previewThemeKey);
            $previewTheme['name'] = $theme->getName();
            $previewTheme['redirect'] = $this->getThemeManagePageUrl();
            $previewTheme['revisionID'] = $this->session->getPreference('PreviewThemeRevisionID');
        }
        return $previewTheme;
    }

    /**
     * Set theme manage page url
     *
     * @param string $url
     */
    public function setThemeManagePageUrl(string $url) {
        $this->themeManagePageUrl = $url;
    }

    /**
     * Get theme manage page url
     *
     * @return string
     */
    private function getThemeManagePageUrl() {
        return $this->themeManagePageUrl;
    }

    /**
     * Get master theme key.
     *
     * @param int|string $themeKey
     * @return string
     */
    public function getMasterThemeKey($themeKey): string {
        $provider = $this->getThemeProvider($themeKey);
        return $provider->getMasterThemeKey($themeKey);
    }

    /**
     * Get view theme addon
     *
     * @return Addon
     */
    public function getCurrentThemeAddon(): Addon {
        $currentTheme = $this->getCurrentTheme(self::GET_THEME_MODE_PRIORITIZE_ADDON);
        return $currentTheme->getAddon();
    }

    /**
     * Get view theme addon
     *
     * @param string $themeKey
     * @return Addon
     */
    public function getThemeAddon(string $themeKey = ''): Addon {
        return $this->addonManager->lookupTheme(
            $this->getMasterThemeKey($themeKey)
        );
    }

    /**
     * Verify if ThemeKey or ID is valid.
     *
     * @param string|int $themeKey
     * @return bool
     */
    public function verifyThemeIdentifierIsValid($themeKey) {
        $provider = $this->getThemeProvider($themeKey);
        return $provider->themeExists($themeKey);
    }

    /**
     * Get current theme.
     *
     * @param string $mode One of the GET_THEME_MODES.
     *
     * @return Theme The current theme or the fallback if it fails to load.
     */
    public function getCurrentTheme(string $mode = self::GET_THEME_MODE_PRIORITIZE_ASSETS): Theme {
        $current = null;

        try {
            // We absolutely cannot fail if a certain provider is not hooked up.
            // As a result we will fall back to our default theme if there is some error.
            $mobileKey = $this->config->get(ThemeServiceHelper::CONFIG_MOBILE_THEME, null);
            $desktopKey = $this->config->get(ThemeServiceHelper::CONFIG_DESKTOP_THEME, null);
            $currentKey = $this->config->get(ThemeServiceHelper::CONFIG_CURRENT_THEME, null);

            $needsMobileOverlay = false;
            $baseKey = $currentKey ?? $desktopKey;

            if (isMobile() && $mobileKey !== null && $mode === self::GET_THEME_MODE_PRIORITIZE_ADDON) {
                $baseKey = $mobileKey;
                $needsMobileOverlay = true;
            }

            // Try to get the base key.
            $baseTheme = $this->getTheme($baseKey);
            $current = $baseTheme;

            if ($needsMobileOverlay && $currentKey !== null) {
                $assetOverlayTheme = $this->getTheme($currentKey);
                $current->setAssets($assetOverlayTheme->getAssets());
            }

            $sectionThemeID =  $this->siteSectionModel->getCurrentSiteSection()->getSectionThemeID();
            if ($sectionThemeID !== null) {
                // Check if the theme actually exists.
                $sectionTheme = $this->getTheme($sectionThemeID);
                if ($sectionTheme !== null) {
                    $current = $sectionTheme;
                }
            }

            $previewThemeKey = $this->session->getPreference('PreviewThemeKey');
            if ($previewThemeKey) { // May be stuck to empty string so falsy check is required.
                $previewThemeRevisionID = $this->session->getPreference('PreviewThemeRevisionID');
                $args = [];

                if (!empty($previewThemeRevisionID)) {
                    $args['revisionID'] = $previewThemeRevisionID;
                }

                $previewTheme = $this->getTheme($previewThemeKey, $args);
                if ($previewTheme === null) {
                    // if we stored wrong preview key store in session, lets reset it.
                    $this->themeHelper->cancelSessionPreviewTheme();
                } else {
                    $current = $previewTheme;
                }
            }

            if ($current === null) {
                // If we're still null, fallback to our default.
                $this->getTheme(self::FALLBACK_THEME_KEY);
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            // If we had some exception during this, fallback to the default.
            $this->getTheme(self::FALLBACK_THEME_KEY);
        }

        $current = $this->normalizeTheme($current);
        return $current;
    }

    /**
     * Set theme asset (update existing or create new if asset does not exist).
     *
     * @param string|int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset to set
     *
     * @return ThemeAsset
     */
    public function setAsset($themeID, string $assetKey, string $data): ThemeAsset {
        $provider = $this->getWritableThemeProvider($themeID);
        $asset = $provider->setAsset($themeID, $assetKey, $data);
        $this->cache->clear();
        return $asset;
    }

    /**
     * Sparse theme asset (update existing or create new if asset does not exist).
     *
     * @param string|int $themeID The unique theme ID.
     * @param string $assetKey Asset key.
     *       Note: variables.json only allowed.
     * @param string $data Data content for asset to set
     *
     * @return ThemeAsset
     */
    public function sparseUpdateAsset($themeID, string $assetKey, string $data): ThemeAsset {
        $provider = $this->getWritableThemeProvider($themeID);
        $asset = $provider->sparseUpdateAsset($themeID, $assetKey, $data);
        // Clear the cache.
        $this->cache->clear();
        return $asset;
    }

    /**
     * Get theme provider.
     *
     * @param string|int $themeID Theme key or id
     * @return ThemeProviderInterface
     * @throws ClientException Throws an exception if no suitable theme provider found.
     */
    private function getThemeProvider($themeID): ThemeProviderInterface {
        foreach ($this->themeProviders as $provider) {
            if ($provider->handlesThemeID($themeID)) {
                return $provider;
            }
        }

        trigger_error('No custom theme provider found!', E_USER_WARNING);
        // It is never acceptable to throw an exception in the theming system.
        return $this->fallbackThemeProvider;
    }
    /**
     * Get theme provider.
     *
     * @param string|int|null $themeID Theme key or id
     * @return ThemeProviderWriteInterface
     * @throws ServerException Throws an exception if no writable theme provider found.
     */
    private function getWritableThemeProvider($themeID = null): ThemeProviderWriteInterface {
        foreach ($this->themeProviders as $provider) {
            if ($provider instanceof ThemeProviderWriteInterface && ($themeID === null || $provider->handlesThemeID($themeID))) {
                return $provider;
            }
        }

        throw new ServerException("No writable theme provider is configured to handle theme '$themeID'.");
    }

    /**
     * Get the raw data of an asset.
     *
     * @param string $themeID
     * @param string $assetKey
     *
     * @return ThemeAsset|null A theme asset.
     */
    public function getAsset(string $themeID, string $assetKey): ?ThemeAsset {
        $provider = $this->getThemeProvider($themeID);
        $theme = $provider->getTheme($themeID);
        // Clear the cache.
        $this->cache->clear();
        return $theme->getAssets()[$assetKey] ?? null;
    }

    /**
     * DELETE theme asset.
     *
     * @param string|int $themeKey The unique theme key or ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function deleteAsset($themeKey, string $assetKey) {
        $provider = $this->getWritableThemeProvider($themeKey);
        $provider->deleteAsset($themeKey, $assetKey);
        // Clear the cache.
        $this->cache->clear();
    }

    /**
     * Get theme revisions
     *
     * @param string $themeKey
     * @return array
     */
    public function getThemeRevisions(string $themeKey): array {
        $provider = $this->getThemeProvider($themeKey);
        $revisions = $provider->getThemeRevisions($themeKey);
        foreach ($revisions as &$revision) {
            $revision = $this->normalizeTheme($revision);
        }
        return $revisions;
    }

    /**
     * Normalize theme data.
     *
     * @param Theme $theme The theme data.
     * @return Theme Updated theme data.
     */
    private function normalizeTheme(Theme $theme): Theme {
        // Apply supported sections.
        $supportedSections = $this->themeSections->getModernSections();
        if ($theme->getFeatures()->useDataDrivenTheme()) {
            $supportedSections = array_merge($supportedSections, $this->themeSections->getLegacySections());
        }
        $theme->setSupportedSections($supportedSections);

        // Fix current theme.
        $currentThemeID =
            $this->config->get(ThemeServiceHelper::CONFIG_CURRENT_THEME)
            ?: $this->config->get(ThemeServiceHelper::CONFIG_DESKTOP_THEME);
        $isCurrent = $currentThemeID == $theme->getThemeID();
        $theme->setCurrent($isCurrent);

        $this->overlayAddonVariables($theme);
        return $theme;
    }

    /**
     * Add Addons variables to theme variables..
     * Addon provided variables will override the theme variables.
     *
     * @param Theme $theme Variables json theme asset string.
     */
    private function overlayAddonVariables(Theme $theme) {
        $features = new ThemeFeatures($this->config, $theme->getAddon());
        // Allow addons to add their own variable overrides. Should be moved into the model when the asset generation is refactored.
        $additionalVariables = [];
        foreach ($this->variableProviders as $variableProvider) {
            if ($features->disableKludgedVars() && $variableProvider instanceof KludgedVariablesProviderInterface) {
                continue;
            }
            $additionalVariables = array_replace_recursive($additionalVariables, $variableProvider->getVariables());
        }

        $defaults = [];
        foreach ($this->variableDefaultsProviders as $defaultsProvider) {
            $defaults = array_replace_recursive($defaults, $defaultsProvider->getVariableDefaults());
        }

        $theme->overlayVariables($additionalVariables, !empty($defaults) ? $defaults : null);
    }
}

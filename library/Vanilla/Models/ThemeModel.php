<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Exception\NotFoundException;
use Vanilla\Addon;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\KludgedVariablesProviderInterface;
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Theme\ThemeProviderCleanupInterface;
use Vanilla\Theme\VariablesProviderInterface;
use Garden\Web\Exception\ClientException;
use Vanilla\Theme\ThemeProviderInterface;
use Garden\Schema\ValidationField;

/**
 * Handle custom themes.
 */
class ThemeModel {

    const FOUNDATION_THEME_KEY = "theme-foundation";
    const FALLBACK_THEME_KEY = self::FOUNDATION_THEME_KEY;
    const ASSET_COMPAT_THEMES = [self::FOUNDATION_THEME_KEY];

    const HEADER = 'header';
    const FOOTER = 'footer';
    const VARIABLES = 'variables';
    const FONTS = 'fonts';
    const SCRIPTS = 'scripts';
    const STYLES = 'styles';
    const JAVASCRIPT = 'javascript';

    const ASSET_LIST = [
        self::HEADER => [
            "type" => "html",
            "file" => "header.html",
            "default" => "",
            "mime-type" => "text/html"
        ],
        self::FOOTER => [
            "type" => "html",
            "file" => "footer.html",
            "default" => "",
            "mime-type" => "text/html"
        ],
        self::VARIABLES => [
            "type" => "json",
            "file" => "variables.json",
            "default" => "{}",
            "mime-type" => "application/json"
        ],
        self::FONTS => [
            "type" => "json",
            "file" => "fonts.json",
            "default" => "[]",
            "mime-type" => "application/json"
        ],
        self::SCRIPTS => [
            "type" => "json",
            "file" => "scripts.json",
            "default" => "[]",
            "mime-type" => "application/json"
        ],
        self::STYLES => [
            "type" => "css",
            "file" => "styles.css",
            "default" => "",
            "mime-type" => "text/css"
        ],
        self::JAVASCRIPT => [
            "type" => "js",
            "file" => "javascript.js",
            "default" => "",
            "mime-type" => "application/javascript"
        ],
    ];

    const ASSET_KEY = "assets";

    /** @var ThemeProviderInterface[] */
    private $themeProviders = [];

    /** @var VariablesProviderInterface[] */
    private $variableProviders = [];

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var \Gdn_Session */
    private $session;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var AddonProviderInterface $addonManager */
    private $addonManager;

    /** @var ThemeModelHelper $themeHelper */
    private $themeHelper;

    /** @var ThemeSectionModel */
    private $themeSections;

    /** @var string $themeManagePageUrl */
    private $themeManagePageUrl = '/dashboard/settings/themes';

    /** @var FsThemeProvider */
    private $fallbackThemeProvider;

    /**
     * ThemeModel constructor.
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Session $session
     * @param AddonProviderInterface $addonManager
     * @param ThemeModelHelper $themeHelper
     * @param ThemeSectionModel $themeSections
     * @param SiteSectionModel $siteSectionModel
     * @param FsThemeProvider $fallbackThemeProvider
     */
    public function __construct(
        ConfigurationInterface $config,
        \Gdn_Session $session,
        AddonProviderInterface $addonManager,
        ThemeModelHelper $themeHelper,
        ThemeSectionModel $themeSections,
        SiteSectionModel $siteSectionModel,
        FsThemeProvider $fallbackThemeProvider
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->addonManager = $addonManager;
        $this->themeHelper = $themeHelper;
        $this->themeSections = $themeSections;
        $this->siteSectionModel = $siteSectionModel;
        $this->fallbackThemeProvider = $fallbackThemeProvider;
    }

    /**
     * Add a theme-variable provider.
     *
     * @param VariablesProviderInterface $provider
     */
    public function addVariableProvider(VariablesProviderInterface $provider) {
        $this->variableProviders[] = $provider;
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
     * @return array
     */
    public function getThemeWithAssets($themeKey, array $query = []): array {
        $provider = $this->getThemeProvider($themeKey);
        $theme = $provider->getThemeWithAssets($themeKey, $query);
        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Get all available themes.
     *
     * @return array
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
     * @return array
     */
    public function postTheme(array $body): array {
        $provider = $this->getThemeProvider('1');
        $theme = $provider->postTheme($body);
        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Update theme name by ID.
     *
     * @param int $themeID Theme ID
     * @param array $body Array of incoming params.
     *        fields: name (required)
     * @return array
     */
    public function patchTheme(int $themeID, array $body): array {
        $provider = $this->getThemeProvider($themeID);
        $theme = $provider->patchTheme($themeID, $body);
        $theme = $this->normalizeTheme($theme);
        return $theme;
    }

    /**
     * Delete theme by ID.
     *
     * @param int $themeID Theme ID
     */
    public function deleteTheme(int $themeID) {
        $provider = $this->getThemeProvider($themeID);
        $provider->deleteTheme($themeID);
    }

    /**
     * Set current theme.
     *
     * @param int $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrentTheme($themeID): array {
        $previousTheme = $this->getCurrentTheme();
        $previousProvider = $this->getThemeProvider($previousTheme['themeID']);
        $newProvider = $this->getThemeProvider($themeID);
        $newTheme = $newProvider->setCurrent($themeID);

        if ($previousProvider !== $newProvider && $previousProvider instanceof ThemeProviderCleanupInterface) {
            $previousProvider->afterCurrentProviderChange();
        }

        $newTheme = $this->normalizeTheme($newTheme);
        return $newTheme;
    }

    /**
     * Set theme as preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @param int $revisionID Theme revision ID.
     * @return array
     */
    public function setPreviewTheme($themeID, ?int $revisionID = null): array {
        if (empty($themeID)) {
            $theme = $this->getCurrentTheme();
            $this->themeHelper->cancelSessionPreviewTheme();
        } else {
            $provider = $this->getThemeProvider($themeID);
            $theme = $provider->setPreviewTheme($themeID, $revisionID);
        }
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
            $provider = $this->getThemeProvider($previewThemeKey);
            $previewTheme['name'] = $provider->getName($previewThemeKey);
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
        $currentTheme = $this->getCurrentTheme();
        $masterKey = $this->getMasterThemeKey($currentTheme['themeID']);
        return $this->getThemeAddon($masterKey);
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
     * @return array The current theme or the fallback if it fails to load.
     */
    public function getCurrentTheme(): array {
        $current = null;

        try {
            // We absolutely cannot fail if a certain provider is not hooked up.
            // As a result we will fall back to our default theme if there is some error.
            $mobileKey = $this->config->get(ThemeModelHelper::CONFIG_MOBILE_THEME, null);
            $desktopKey = $this->config->get(ThemeModelHelper::CONFIG_DESKTOP_THEME, null);
            $currentKey = $this->config->get(ThemeModelHelper::CONFIG_CURRENT_THEME, null);

            $baseKey = isMobile()
                ? $mobileKey ?? $desktopKey
                : $currentKey ?? $desktopKey;

            // Try to get the base key.
            $baseTheme = $this->getThemeProvider($baseKey)->getThemeWithAssets($baseKey);
            if ($baseTheme !== null) {
                $current = $baseTheme;
            }

            $sectionThemeID =  $this->siteSectionModel->getCurrentSiteSection()->getSectionThemeID();
            if ($sectionThemeID !== null) {
                // Check if the theme actually exists.
                $sectionTheme = $this->getThemeProvider($sectionThemeID)->getThemeWithAssets($sectionThemeID);
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

                $themeProvider = $this->getThemeProvider($previewThemeKey);
                $previewTheme = $themeProvider->getThemeWithAssets($previewThemeKey, $args);
                if ($previewTheme === null) {
                    // if we stored wrong preview key store in session, lets reset it.
                    $this->themeHelper->cancelSessionPreviewTheme();
                } else {
                    $current = $previewTheme;
                }
            }

            if ($current === null) {
                // If we're still null, fallback to our default.
                $provider = $this->getThemeProvider("FILE");
                $current = $provider->getThemeWithAssets(self::FALLBACK_THEME_KEY);
            }
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            // If we had some exception during this, fallback to the default.
            $provider = $this->getThemeProvider("FILE");
            $current = $provider->getThemeWithAssets(self::FALLBACK_THEME_KEY);
        }

        $current = $this->normalizeTheme($current);
        return $current;
    }

    /**
     * Set theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param int $revisionID Theme revision ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset to set
     *
     * @return array
     */
    public function setAsset(int $themeID, int $revisionID, string $assetKey, string $data): array {
        $provider = $this->getThemeProvider($themeID);
        $asset = $provider->setAsset($themeID, $revisionID, $assetKey, $data);
        return $this->normalizeAsset($assetKey, $asset, $this->getThemeAddon($themeID));
    }

    /**
     * Sparse theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Asset key.
     *       Note: variables.json only allowed.
     * @param string $data Data content for asset to set
     *
     * @return array
     */
    public function sparseAsset(int $themeID, string $assetKey, string $data): array {
        $provider = $this->getThemeProvider($themeID);
        $asset = $provider->sparseAsset($themeID, $assetKey, $data);
        return $this->normalizeAsset($assetKey, $asset, $this->getThemeAddon($themeID));
    }

    /**
     * Get theme provider.
     *
     * @param string|int $themeKey Theme key or id
     * @return ThemeProviderInterface
     * @throws ClientException Throws an exception if no suitable theme provider found.
     */
    private function getThemeProvider($themeKey): ThemeProviderInterface {
        $themeType = (is_int($themeKey) || ctype_digit($themeKey)) ? ThemeProviderInterface::TYPE_DB : ThemeProviderInterface::TYPE_FS;
        foreach ($this->themeProviders as $provider) {
            if ($themeType === $provider->themeKeyType()) {
                return $provider;
            }
        }

        trigger_error('No custom theme provider found!', E_USER_WARNING);
        // It is never acceptable to throw an exception in the theming system.
        return $this->fallbackThemeProvider;
    }

    /**
     * Get the raw data of an asset.
     *
     * @param string $themeID
     * @param string $assetKey
     *
     * @return string The asset contents.
     */
    public function getAssetData(string $themeID, string $assetKey): string {
        $provider = $this->getThemeProvider($themeID);
        $asset = $provider->getAssetData($themeID, $assetKey);
        return $this->normalizeAsset($assetKey, $asset, $this->getThemeAddon($themeID));
    }

    /**
     * DELETE theme asset.
     *
     * @param string $themeKey The unique theme key or ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     */
    public function deleteAsset(string $themeKey, string $assetKey) {
        $provider = $this->getThemeProvider($themeKey);
        return $provider->deleteAsset($themeKey, $assetKey);
    }

    /**
     * Get theme revisions
     *
     * @param int $themeKey
     * @return array
     */
    public function getThemeRevisions(int $themeKey): array {
        $provider = $this->getThemeProvider($themeKey);
        $revisions = $provider->getThemeRevisions($themeKey);
        foreach ($revisions as &$revision) {
            $revision = $this->normalizeTheme($revision);
        }
        return $revisions;
    }

    /**
     * Basic input string validation function for html and json assets
     *
     * @param array $data
     * @param ValidationField $field
     * @return bool
     */
    public static function validator(array $data, ValidationField $field) {
        $asset = self::ASSET_LIST[$field->getName()];
        $data = $data['data'];
        switch ($asset['type']) {
            case 'json':
                $valid = true;
                if ($asset['default'] === '[]') {
                    $valid = substr($data, 0, 1) === '[';
                    $valid = $valid && substr($data, -1) === ']';
                } elseif ($asset['default'] === '{}') {
                    $valid = substr($data, 0, 1) === '{';
                    $valid = $valid && substr($data, -1) === '}';
                }
                $json = json_decode($data, true);
                $valid = $valid && $json !== null;
                break;
            case 'html':
            case 'css':
            case 'js':
            default:
                $valid = true;
                break;
        }
        return $valid;
    }

    /**
     * Take in some some asset and normalize it.
     *
     * @param string $assetName
     * @param mixed $assetContents
     * @param Addon $themeAddon
     *
     * @return mixed The updated asset.
     */
    private function normalizeAsset(string $assetName, $assetContents, Addon $themeAddon) {
        // Mix in addon variables to the variables asset.
        if (preg_match('/^variables/', $assetName) &&
            $assetContents instanceof JsonAsset
        ) {
            $newJson = $this->mixAddonVariables($assetContents->getData(), $themeAddon);
            return new JsonAsset($newJson);
        } else {
            return $assetContents;
        }
    }

    /**
     * Normalize theme data.
     *
     * @param array $theme The theme data.
     * @return array Updated theme data.
     */
    private function normalizeTheme(array $theme): array {
        $themeID = $theme['themeID'];
        $addon = $this->getThemeAddon($themeID);
        $features = new ThemeFeatures($this->config, $addon);
        $theme['features'] = $features;

        // Apply supported sections.
        $supportedSections = $this->themeSections->getModernSections();
        if ($features->useDataDrivenTheme()) {
            $supportedSections = array_merge($supportedSections, $this->themeSections->getLegacySections());
        }
        $theme['supportedSections'] = $supportedSections;

        // Normalize all assets.
        foreach ($theme['assets'] as $assetName => $assetContents) {
            $theme['assets'][$assetName] = $this->normalizeAsset($assetName, $assetContents, $addon);
        }

        // Generate a preview.
        $theme['preview'] = $this->generateThemePreview($theme);

        // A little fixup to ensure current variables are always applied to asset compat themes.
        $currentID = $this->config->get(ThemeModelHelper::CONFIG_CURRENT_THEME, null);
        $currentID = $this->verifyThemeIdentifierIsValid($currentID) ? $currentID : ThemeModel::FALLBACK_THEME_KEY;

        if (in_array($themeID, self::ASSET_COMPAT_THEMES, true) &&
            $currentID !== null &&
            !in_array($currentID, self::ASSET_COMPAT_THEMES, true) // To prevent infinite loops.
        ) {
            try {
                // Apply the current themes assets over foundation.
                $currentTheme = $this->getThemeWithAssets($currentID);
                $theme['assets'] = $currentTheme['assets'];
            } catch (\Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                // If we had some exception during this, fallback to the default.
                $provider = $this->getThemeProvider("FILE");
                return $provider->getThemeWithAssets(self::FALLBACK_THEME_KEY);
            }
        }
        return $theme;
    }

    /**
     * Add Addons variables to theme variables..
     * Addon provided variables will override the theme variables.
     *
     * @param string $baseAssetContent Variables json theme asset string.
     * @param Addon $themeAddon
     * @return string The updated asset content.
     */
    private function mixAddonVariables(string $baseAssetContent, Addon $themeAddon): string {
        $features = new ThemeFeatures($this->config, $themeAddon);
        // Allow addons to add their own variable overrides. Should be moved into the model when the asset generation is refactored.
        $additionalVariables = [];
        foreach ($this->variableProviders as $variableProvider) {
            if ($features->disableKludgedVars() && $variableProvider instanceof KludgedVariablesProviderInterface) {
                continue;
            }
            $additionalVariables = array_replace_recursive($additionalVariables, $variableProvider->getVariables());
        }

        if ($additionalVariables) {
            $variables = json_decode($baseAssetContent, true) ?? [];

            $variables = array_replace_recursive($variables, $additionalVariables);
            $baseAssetContent = json_encode($variables);
        }
        return $baseAssetContent;
    }


    /**
     * Generate a theme preview from the variables.
     *
     * @param array $theme
     * @return array
     */
    private function generateThemePreview(array $theme): array {
        $preview = $theme['preview'] ?? [];

        if (!($theme["assets"]["variables"] instanceof JsonAsset)) {
            return $preview;
        }

        $variables = $theme["assets"]["variables"]->getDataArray();
        if ($variables) {
            $preset = $variables['global']['options']['preset'] ?? null;
            $bg = $variables['global']['mainColors']['bg'] ?? $preset === 'dark' ? "#323639" : "#fff";
            $fg = $variables['global']['mainColors']['fg'] ?? $preset === 'dark' ? '#fff' : '#555a62';
            $primary = $variables['global']['mainColors']['primary'] ?? null;
            $preview['global.mainColors.primary'] = $primary;
            $preview['global.mainColors.bg'] = $bg ?? null;
            $preview['global.mainColors.fg'] = $fg ?? null;
            $preview['titleBar.colors.bg'] = $variables['titleBar']['colors']['bg'] ?? $primary ?? null;
            $preview['titleBar.colors.fg'] = $variables['titleBar']['colors']['fg'] ?? null;
            $preview['banner.outerBackground.image'] = $variables['splash']['outerBackground']['image']
                ?? $variables['banner']['outerBackground']['image']
                ?? null;
        }
        return $preview;
    }
}

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
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\VariablesProviderInterface;
use Garden\Web\Exception\ClientException;
use Vanilla\Theme\ThemeProviderInterface;
use Garden\Schema\ValidationField;

/**
 * Handle custom themes.
 */
class ThemeModel {
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

    /** @var AddonProviderInterface $addonManager */
    private $addonManager;

    /** @var ThemeModelHelper $themeHelper */
    private $themeHelper;

    /** @var string $themeManagePageUrl */
    private $themeManagePageUrl = '/dashboard/settings/themes';

    /**
     * ThemeModel constructor.
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Session $session
     * @param AddonProviderInterface $addonManager
     * @param ThemeModelHelper $themeHelper
     */
    public function __construct(
        ConfigurationInterface $config,
        \Gdn_Session $session,
        AddonProviderInterface $addonManager,
        ThemeModelHelper $themeHelper
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->addonManager = $addonManager;
        $this->themeHelper = $themeHelper;
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
     * @return array
     */
    public function getThemeWithAssets($themeKey): array {
        $provider = $this->getThemeProvider($themeKey);
        $theme = $provider->getThemeWithAssets($themeKey);
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
                $theme['preview'] = $this->generateThemePreview($theme) ?? null;
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
     * Get master theme key
     *
     * @param int|string $themeKey
     * @return string
     */
    public function getMasterThemeKey($themeKey): string {
        $provider = $this->getThemeProvider($themeKey);
        return $provider->getMasterThemeKey($themeKey);
    }

    /**
     * Set current theme.
     *
     * @param int $themeID Theme ID to set current.
     * @return array
     */
    public function setCurrentTheme($themeID): array {
        $provider = $this->getThemeProvider($themeID);

        if ($theme = $provider->setCurrent($themeID)) {
            if ($provider->themeKeyType() === 0) {
                try {
                    $dbThemeProvider = $this->getThemeProvider(1);
                    $dbThemeProvider->resetCurrent();
                } catch (ClientException $e) {
                    if ($e->getMessage() !== 'No custom theme provider found!') {
                        throw $e;
                    }
                    //do nothing if db provider does not exist
                }
            }
        }

        $theme['preview'] = $this->generateThemePreview($theme) ?? null;
        return $theme;
    }

    /**
     * Set theme as preview theme.
     * (pseudo current theme for current session user only)
     *
     * @param int|string $themeID Theme ID to set current.
     * @return array
     */
    public function setPreviewTheme($themeID): array {
        if (empty($themeID)) {
            $theme = $this->getCurrentTheme();
            $this->themeHelper->cancelSessionPreviewTheme();
        } else {
            $provider = $this->getThemeProvider($themeID);
            $theme = $provider->setPreviewTheme($themeID);
        }
        return $theme;
    }

    /**
     * Get view theme key
     *
     * @return string
     */
    public function getViewThemeKey(): string {
        $themeKey = $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));

        if ($previewTheme = $this->session->getPreference('PreviewThemeKey')) {
            $themeKey = $previewTheme;
        }
        return $themeKey;
    }

    /**
     * Get preview theme properties if exists.
     *
     * @return array
     */
    public function getPreviewTheme(): array {
        $previewTheme = [];
        if ($previewThemeKey = $this->session->getPreference('PreviewThemeKey')) {
            $previewTheme['themeID'] = $previewThemeKey;
            $provider = $this->getThemeProvider($previewThemeKey);
            $previewTheme['name'] = $provider->getName($previewThemeKey);
            $previewTheme['redirect']= $this->getThemeManagePageUrl();
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
     * Get view theme addon
     *
     * @return string
     */
    public function getThemeAddon(): Addon {
        $themeKey = $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));
        $provider = $this->getThemeProvider($themeKey);
        $addonThemeKey = $provider->getMasterThemeKey($themeKey);
        if ($previewTheme = $this->session->getPreference('PreviewThemeKey')) {
            try {
                $provider = $this->getThemeProvider($previewTheme);
                $addonThemeKey = $provider->getMasterThemeKey($previewTheme);
            } catch (NotFoundException $e) {
                // if we store wrong preview key store in session, lets reset it
                $this->themeHelper->cancelSessionPreviewTheme();
            }
        }
        $addon = $this->addonManager->lookupTheme($addonThemeKey ?? $themeKey);
        return $addon;
    }

    /**
     * Get current theme.
     *
     * @return array|void If no currnt theme set returns null
     */
    public function getCurrentTheme(): ?array {
        $current = null;
        try {
            $provider = $this->getThemeProvider(1);
            $current = $provider->getCurrent();
        } catch (ClientException $e) {
            if ($e->getMessage() !== 'No custom theme provider found!') {
                throw $e;
            }
            //do nothing if db provider does not exist
        }

        if (is_null($current)) {
            $provider = $this->getThemeProvider("FILE");
            $current = $provider->getCurrent();
        }
        $current['preview'] = $this->generateThemePreview($current) ?? null;
        return $current;
    }

    /**
     * Get theme view folder path
     *
     * @param string|int $themeKey Theme key or id
     * @return string
     */
    public function getThemeViewPath($themeKey): string {
        $provider = $this->getThemeProvider($themeKey);
        $path = $provider->getThemeViewPath($themeKey);
        return $path;
    }

    /**
     * Set theme asset (update existing or create new if asset does not exist).
     *
     * @param int $themeID The unique theme ID.
     * @param string $assetKey Unique asset key (ex: header.html, footer.html, fonts.json, styles.css)
     * @param string $data Data content for asset to set
     *
     * @return array
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array {
        $provider = $this->getThemeProvider($themeID);
        return $provider->setAsset($themeID, $assetKey, $data);
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
        return $provider->sparseAsset($themeID, $assetKey, $data);
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
            $provider->setVariableProviders($this->getVariableProviders());
            if ($themeType === $provider->themeKeyType()) {
                return $provider;
            }
        }
        throw new ClientException('No custom theme provider found!', 501);
    }

    /**
     * Get the raw data of an asset.
     *
     * @param string $themeKey
     * @param string $assetKey
     */
    public function getAssetData(string $themeKey, string $assetKey): string {
        $provider = $this->getThemeProvider($themeKey);
        return $provider->getAssetData($themeKey, $assetKey);
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
     * Generate a theme preview from the variables.
     *
     * @param array $theme
     * @return array
     */
    public function generateThemePreview(array $theme): array {
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

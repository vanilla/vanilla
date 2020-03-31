<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Addon;
use Vanilla\Contracts\AddonInterface;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\ImageAsset;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Gdn_Upload;
use Vanilla\Theme\TwigAsset;

/**
 * Handle custom themes.
 */
class FsThemeProvider implements ThemeProviderInterface {

    use FsThemeMissingTrait;

    /** @var AddonProviderInterface $addonManager */
    private $addonManager;

    /** @var ThemeModelHelper */
    private $themeHelper;

    /** @var RequestInterface $request */
    private $request;

    /** @var ConfigurationInterface */
    private $config;

    /** @var string|null A theme option value if set in the form of '%s_optionName' */
    private $themeOptionValue;

    /**
     * FsThemeProvider constructor.
     *
     * @param AddonProviderInterface $addonManager
     * @param RequestInterface $request
     * @param ConfigurationInterface $config
     * @param ThemeModelHelper $themeHelper
     */
    public function __construct(
        AddonProviderInterface $addonManager,
        RequestInterface $request,
        ConfigurationInterface $config,
        ThemeModelHelper $themeHelper
    ) {
        $this->addonManager = $addonManager;
        $this->request = $request;
        $this->config = $config;
        $this->themeOptionValue = $this->config->get('Garden.ThemeOptions.Styles.Value', '');
        $this->themeHelper = $themeHelper;
    }

    /**
     * @inheritdoc
     */
    public function themeKeyType(): int {
        return self::TYPE_FS;
    }

    /**
     * @inheritdoc
     */
    public function getThemeWithAssets($themeKey): array {
        $theme = $this->normalizeTheme(
            $this->getThemeByName($themeKey),
            $this->getAssets($themeKey)
        );
        return $theme;
    }

    /**
     * Get theme by name
     *
     * @param string $themeKey
     * @return Addon Returns theme addon
     *
     * @throws NotFoundException Throws an exception when themeName not found.
     */
    public function getThemeByName($themeKey): Addon {
        $theme = $this->getThemeAddon($themeKey);
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeKey): string {
        $theme = $this->getThemeAddon($themeKey);
        return $theme->getKey();
    }

    /**
     * @inheritdoc
     */
    public function getName($themeKey): string {
        $theme = $this->getThemeAddon($themeKey);
        return $theme->getInfoValue('name');
    }

    /**
     * Get the current theme, or fallback to the default one.
     *
     * @param int|string $themeKey
     *
     * @return Addon
     */
    public function getThemeAddon($themeKey): AddonInterface {
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof AddonInterface)) {
            $theme = $this->addonManager->lookupTheme(ThemeModel::FALLBACK_THEME_KEY);
            if (!($theme instanceof AddonInterface)) {
                // Uh-oh, even the default theme doesn't exist.
                throw new NotFoundException("Theme");
            }
        }

        return $theme;
    }

    /**
     * @param $themeKey
     * @return bool
     */
    public function themeExists($themeKey): bool {
        $themeExists = true;
        $theme = $this->addonManager->lookupTheme($themeKey);

        if (!($theme instanceof AddonInterface) || is_null($theme)) {
            $themeExists = false;
        }

        return $themeExists;
    }

    /**
     * Get all theme assets
     *
     * @param Addon $theme
     * @param array $assets
     * @return array
     */
    private function normalizeTheme(Addon $theme, array $assets): array {
        $res = [
            "assets" => $assets,
            'name' => $theme->getInfoValue('name'),
            'themeID' => $theme->getInfoValue('key'),
            'type' => 'themeFile',
            'version' => $theme->getInfoValue('version'),
            'current' => $theme->getInfoValue('key') === $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme')),
        ];

        $res["assets"] = [];
        $generatedAssetTypes = ["fonts", "footer", "header", "scripts", "variables"];

        foreach ($assets as $assetKey => $asset) {
            $finalAssetKey = $assetKey;
            // We have some slightly special handling if we have theme options.
            if (!empty($this->themeOptionValue)) {
                $themeOptionEnding = sprintf($this->themeOptionValue, '');
                $isInThemeOption = preg_match("/$themeOptionEnding$/", $assetKey);

                if ($isInThemeOption) {
                    $finalAssetKey = str_replace($themeOptionEnding, '', $assetKey);
                }
            }

            if (!in_array($finalAssetKey, $generatedAssetTypes)) {
                continue;
            }

            $res["assets"][$finalAssetKey] = $this->generateAsset($assetKey, $asset, $theme);
        }

        $secondaryAssets = array_intersect_key(
            $assets,
            array_flip(["javascript", "styles"])
        );
        foreach ($secondaryAssets as $assetKey => $asset) {
            $path = $theme->path("/assets/{$asset['file']}", Addon::PATH_ADDON);
            $res["assets"][$assetKey] = $this->request->url($path, true);
        }

        $logos = [
            "logo" => "Garden.Logo",
            "mobileLogo" => "Garden.MobileLogo",
        ];
        $foundLogo = false;
        foreach ($logos as $logoName => $logoConfig) {
            if ($logo = $this->config->get($logoConfig)) {
                $logoUrl = Gdn_Upload::url($logo);
                $res["assets"][$logoName] = new ImageAsset($logoUrl);
                $foundLogo = true;
            }
        }


        // Check theme for default.
        if (!$foundLogo) {
            if (valr("assets.variables", $res)) {
                $themeVars = json_decode($res['assets']['variables']->getData(), true);
                $desktopLogo = valr("titleBar.logo.desktop.url", $themeVars);
                $mobileLogo = valr("titleBar.logo.mobile.url", $themeVars);
                $noDesktopLogo = empty($desktopLogo);
                $noMobileLogo = empty($mobileLogo);

                if (!$noDesktopLogo) {
                    $res["assets"]["logo"] = new ImageAsset($desktopLogo);
                }
                if (!$noMobileLogo || !$noDesktopLogo) {
                    if (!$noMobileLogo) {
                        $res["assets"]["mobileLogo"] = new ImageAsset($mobileLogo);
                    } else {
                        // Use same logo if mobile is not set.
                        $res["assets"]["mobileLogo"] = new ImageAsset($desktopLogo);
                    }
                }
            }
        }


        $themeInfo = \Gdn::themeManager()->getThemeInfo($theme->getInfoValue('key'));
        $res['preview']['previewImage'] = $themeInfo['IconUrl'] ?? null;
        $res['preview']['info']['Description'] = ['type'=>'string', 'info' => $theme->getInfoValue('description', '')];

        $themeAuthors = $theme->getInfoValue('authors', false);
        if (is_array($themeAuthors)) {
            $authors = '';
            foreach ($themeAuthors as $author) {
                $authors .= empty($authors) ? '' : ', ';
                $authors .= $author['name'] ?? '';
            }

            $res['preview']['info']['Authors'] = ['type'=>'string', 'info' => $authors];
        }
        return $res;
    }

    /**
     * Generate an asset object, given an asset array.
     *
     * @param string $key
     * @param array $asset
     * @param Addon $theme
     * @return Asset
     */
    private function generateAsset(string $key, array $asset, Addon $theme): Asset {
        $type = $asset["type"] ?? null;
        if ($type === null) {
            throw new ServerException("Missing theme asset type.");
        }
        $type = strtolower($type);

        $filename = $asset["file"] ?? null;
        if ($filename === null) {
            throw new ServerException("File key missing for theme asset.");
        }

        $data = $this->getFileAsset($theme, $key, $asset);

        switch ($type) {
            case "data":
                return $this->dataAsset($key, $data);
            case "html":
                return new HtmlAsset($data);
            case "twig":
                return new TwigAsset($data);
            case "json":
                return new JsonAsset($data);
            default:
                throw new ServerException("Unrecognized type: {$type}");
        }
    }

    /**
     * Generate a new data theme asset.
     *
     * @param string $key
     * @param string $content
     * @return Asset
     */
    private function dataAsset(string $key, string $content): Asset {
        $key = strtolower($key);
        switch ($key) {
            case "fonts":
                return new FontsAsset(json_decode($content, true));
            case "scripts":
                return new ScriptsAsset(json_decode($content, true));
            default:
                throw new ServerException("Unrecognized data asset: {$key}");
        }
    }

    /**
     * Cast themeAssetModel data to out schema data by calculating and casting required fields.
     *
     * @param Addon $theme
     * @param string $assetKey
     * @param array $asset
     *
     * @return string
     */
    private function getFileAsset(Addon $theme, string $assetKey, array $asset): string {
        $filename = basename($asset['file']);
        if ($filename) {
            $fullFilename = $theme->path("/assets/{$filename}");
            if (!file_exists($fullFilename) || !is_readable($fullFilename)) {
                trigger_error("Theme asset file does not exist or is not readable: {$fullFilename}", E_USER_WARNING);
            } else {
                return file_get_contents($fullFilename);
            }
        }

        $defaultAsset = ThemeModel::ASSET_LIST[$assetKey];
        return $defaultAsset['default'];
    }

    /**
     * Get the raw data of an asset.
     *
     * @param string $themeKey
     * @param string $assetKey
     * @return string
     * @throws NotFoundException If no asset found throw an exception.
     */
    public function getAssetData($themeKey, string $assetKey): string {
        $theme = $this->getThemeByName($themeKey);
        $assets = $this->getAssets($themeKey);

        if (array_key_exists($assetKey, $assets)) {
            return $assets[$assetKey]['data'] ?? $this->getFileAsset($theme, $assetKey, $assets[$assetKey]);
        } else {
            throw new NotFoundException("Asset");
        }
    }

    /**
     * Get theme assets by by themeID.
     *
     * @param string $themeID
     *
     * @return mixed
     * @throws NotFoundException Throws an exception if asset not found.
     */
    private function getAssets(string $themeID): array {
        $theme = $this->getThemeByName($themeID);
        $assets  = $theme->getInfoValue(ThemeModel::ASSET_KEY, $this->getDefaultAssets());
        return $assets;
    }

    /**
     * @inheritDoc
     */
    public function getAllThemes(): array {
        /** @var Addon[] $allThemes */
        $allThemes = $this->addonManager->lookupAllByType(Addon::TYPE_THEME);
        $allAvailableThemes = [];

        foreach ($allThemes as $theme) {
            if ($this->themeHelper->isThemeVisible($theme)) {
                $allAvailableThemes[] = $this->getThemeWithAssets($theme->getKey());
            }
        }
        return $allAvailableThemes;
    }

    /**
     * In case theme does not have any asset defined yet
     * we still need some of them to exist (ex: variables.json)
     *
     * @return array
     */
    private function getDefaultAssets(): array {
        return [
            "variables" => [
                "type" => "json",
                "file" => "variables.json",
                "placeholder" => '{}',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function setCurrent($themeKey): array {
        $this->config->set('Garden.Theme', $themeKey);
        $this->config->set('Garden.MobileTheme', $themeKey);
        $this->config->set('Garden.CurrentTheme', $themeKey);
        $theme = $this->getThemeWithAssets($themeKey);
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function setPreviewTheme($themeKey): array {
        if (!empty($themeKey)) {
            $theme = $this->getThemeWithAssets($themeKey);
        } else {
            $theme = $this->getCurrent();
        }

        $this->themeHelper->setSessionPreviewTheme($themeKey, $this);
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?array {
        $themeKey = $this->themeHelper->getConfigThemeKey();
        return $this->getThemeWithAssets($themeKey);
    }
}

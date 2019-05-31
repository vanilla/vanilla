<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Addon;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Theme\StyleAsset;
use Vanilla\Theme\ScriptsAsset;
use Vanilla\Theme\ImageAsset;
use Vanilla\Theme\ThemeProviderInterface;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Garden\Web\Exception\NotFoundException;
use Gdn_Request;
use Gdn_Upload;

/**
 * Handle custom themes.
 */
class FsThemeProvider implements ThemeProviderInterface {
    use FsThemeMissingTrait;
    use ThemeVarialblesTrait;
    /**
     * @var AddonManager
     */
    private $addonManager;

    /** @var Gdn_Request */
    private $request;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * FsThemeProvider constructor.
     *
     * @param AddonManager $addonManager
     * @param Gdn_Request $request
     * @param ConfigurationInterface $config
     */
    public function __construct(
        AddonManager $addonManager,
        Gdn_Request $request,
        ConfigurationInterface $config
    ) {
        $this->addonManager = $addonManager;
        $this->request = $request;
        $this->config = $config;
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
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        return $theme;
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
            'themeID' => $theme->getInfoValue('key'),
            'type' => 'themeFile',
            'version' => $theme->getInfoValue('version'),
        ];

        $res["assets"] = [];

        $primaryAssets = array_intersect_key(
            $assets,
            array_flip(["fonts", "footer", "header", "scripts", "variables"])
        );
        foreach ($primaryAssets as $assetKey => $asset) {
            $res["assets"][$assetKey] = $this->generateAsset($assetKey, $asset, $theme);
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
        foreach ($logos as $logoName => $logoConfig) {
            if ($logo = $this->config->get($logoConfig)) {
                $logoUrl = Gdn_Upload::url($logo);
                $res["assets"][$logoName] = new ImageAsset($logoUrl);
            }
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
        }        $filename = $asset["file"] ?? null;
        $type = strtolower($type);

        $filename = $asset["file"] ?? null;
        if ($filename === null) {
            throw new ServerException("File key missing for theme asset.");
        }
        $data = $this->getFileAsset($theme, $filename);

        switch ($type) {
            case "data":
                return $this->dataAsset($key, $data);
            case "html":
                return new HtmlAsset($data);
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
     * @param string $filename
     */
    private function getFileAsset(Addon $theme, string $filename): string {
        $filename = basename($filename);
        $fullFilename = $theme->path("/assets/{$filename}");
        if (!file_exists($fullFilename)) {
            throw new ServerException("Theme asset file does not exist: {$fullFilename}");
        }
        if (!is_readable($fullFilename)) {
            throw new ServerException("Unable to read theme asset file: {$fullFilename}");
        }
        $assetContent = file_get_contents($fullFilename);
        if ($filename === 'variables.json') {
            $assetContent = $this->addAddonVariables($assetContent);
        }

        return $assetContent;
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
            return $assets[$assetKey]['data'] ?? $this->getFileAsset($theme, $assets[$assetKey]['file']);
        } else {
            throw new NotFoundException("Asset");
        }
    }

    /**
     * Get theme asset by assetKey.
     *
     * @param string $id
     * @return mixed
     * @throws NotFoundException Throws an exception if asset not found.
     */
    private function getAssets(string $id): array {
        $theme = $this->getThemeByName($id);
        $assets  = $theme->getInfoValue(ThemeModel::ASSET_KEY, []);
        return $assets;
    }
}

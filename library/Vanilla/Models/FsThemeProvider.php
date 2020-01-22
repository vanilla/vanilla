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
use Garden\Web\Exception\ServerException;
use Gdn_Request;
use Gdn_Upload;
use Vanilla\Theme\TwigAsset;

/**
 * Handle custom themes.
 */
class FsThemeProvider implements ThemeProviderInterface {

    use FsThemeMissingTrait;
    use ThemeVariablesTrait;

    /** @var AddonManager */
    private $addonManager;

    /** @var ThemeModelHelper */
    private $themeHelper;

    /** @var Gdn_Request */
    private $request;

    /** @var ConfigurationInterface */
    private $config;

    /** @var string|null A theme option value if set in the form of '%s_optionName' */
    private $themeOptionValue;

    /**
     * FsThemeProvider constructor.
     *
     * @param AddonManager $addonManager
     * @param Gdn_Request $request
     * @param ConfigurationInterface $config
     * @param ThemeModelHelper $themeHelper
     */
    public function __construct(
        AddonManager $addonManager,
        Gdn_Request $request,
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
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        return $theme;
    }

    /**
     * @inheritdoc
     */
    public function getThemeViewPath($themeKey): string {
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        $path = PATH_ROOT . $theme->getSubdir() . '/views/';
        return $path;
    }

    /**
     * @inheritdoc
     */
    public function getMasterThemeKey($themeKey): string {
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        return $themeKey;
    }

    /**
     * @inheritdoc
     */
    public function getName($themeKey): string {
        $theme = $this->addonManager->lookupTheme($themeKey);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        return $theme->getInfoValue('name');
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
            'name' => $theme->getInfoValue('name'),
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
        foreach ($logos as $logoName => $logoConfig) {
            if ($logo = $this->config->get($logoConfig)) {
                $logoUrl = Gdn_Upload::url($logo);
                $res["assets"][$logoName] = new ImageAsset($logoUrl);
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

        $data = $this->getFileAsset($theme, $asset);

        // Mix in addon variables to the variables asset.
        if (preg_match('/^variables/', $key)) {
            $data = $this->addAddonVariables($data);
        }

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
     * @param array $asset
     *
     * @return string
     */
    private function getFileAsset(Addon $theme, array $asset): string {
        $filename = basename($asset['file']);
        if (!isset($asset['placeholder'])) {
            $fullFilename = $theme->path("/assets/{$filename}");
            if (!file_exists($fullFilename)) {
                throw new ServerException("Theme asset file does not exist: {$fullFilename}");
            }
            if (!is_readable($fullFilename)) {
                throw new ServerException("Unable to read theme asset file: {$fullFilename}");
            }
            $assetContent = file_get_contents($fullFilename);
        } else {
            $assetContent = $asset['placeholder'];
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
            return $assets[$assetKey]['data'] ?? $this->getFileAsset($theme, $assets[$assetKey]);
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
        $allThemes = $this->addonManager->lookupAllByType(Addon::TYPE_THEME);
        $allAvailableThemes = [];

        foreach ($allThemes as $theme) {
            $themeInfo = $theme->getInfo();
            $filteredTheme = $this->filterTheme($themeInfo);
            if ($filteredTheme) {
                $allAvailableThemes[] = $this->getThemeWithAssets($filteredTheme["key"]);
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
     * Filter theme based on it's info.
     *
     * @param array $themeInfo
     * @return array
     */
    protected function filterTheme($themeInfo): array {
        $clientName = defined('CLIENT_NAME') ? CLIENT_NAME : '';
        $alwaysVisibleThemes = c('Garden.Themes.Visible', '');

        if ($alwaysVisibleThemes === 'all') {
            return $themeInfo;
        }

        $alwaysVisibleThemes = explode(',', $alwaysVisibleThemes);

        // Check if theme visibility is explicitly set
        $hidden = $themeInfo['hidden'] ?? null;
        if (is_null($hidden)) {
            $hidden = true;
            $sites = $themeInfo['sites'] ?? [];
            $site = $themeInfo['site'] ?? '';

            if ($site) {
                array_push($sites, $site);
            }
            foreach ($sites as $s) {
                if ($s === $clientName || fnmatch($s, $clientName)) {
                    $hidden = false;
                    break;
                }
            }
        }

        if ($hidden && !in_array($themeInfo['key'], $alwaysVisibleThemes)) {
            return [];
        }

        return $themeInfo;
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
        $themeKey = $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));
        return $this->getThemeWithAssets($themeKey);
    }
}

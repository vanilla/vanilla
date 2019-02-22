<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Gdn_Request;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\AddonManager;
use Vanilla\Addon;
use Vanilla\Models\ThemeModel;
use Vanilla\Theme\Asset;
use Vanilla\Theme\FontsAsset;
use Vanilla\Theme\HtmlAsset;
use Vanilla\Theme\JsonAsset;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Theme\ScriptsAsset;

/**
 * API Controller for the `/themes` resource.
 */
class ThemesApiController extends AbstractApiController {
    const ASSET_TYPES = ['html', 'js', 'json', 'css'];

    const ASSET_LIST = [
        "header" => [
            "type" => "html",
            "file" => "header.html"
        ],
        "footer" => [
            "type" => "html",
            "file" => "footer.html"
        ],
        "variables" => [
            "type" => "json",
            "file" => "variables.json"
        ],
        "fonts" => [
            "type" => "json",
            "file" => "fonts.json"
        ],
        "scripts" => [
            "type" => "json",
            "file" => "scripts.json"
        ],
        "styles" => [
            "type" => "css",
            "file" => "styles.css"
        ],
        "javascript" => [
            "type" => "js",
            "file" => "javascript.js"
        ]

    ];

    const ASSET_KEY = "assets";

    /* @var AddonManager */
    private $addonManager;

    /* @var ThemeModel */
    private $themeModel;

    /** @var Gdn_Request */
    private $request;

    /**
     * @inheritdoc
     */
    public function __construct(AddonManager $addonManager, ThemeModel $themeModel, Gdn_Request $request) {
        $this->addonManager = $addonManager;
        $this->request = $request;
        $this->themeModel = $themeModel;
    }

    /**
     * Get a theme assets.
     *
     * @param string $themeKey The unique theme key or theme ID.
     * @return array
     */
    public function get(string $themeKey): array {
        $this->permission();

        $out = $this->themeResultSchema('out');

        $theme = $this->getThemeByName($themeKey);
        $normalizedTheme = $this->normalizeTheme($theme);
        $result = $out->validate($normalizedTheme);
        return $result;
    }

    /**
     * Get theme asset.
     *
     * @param string $id The unique theme key or theme ID (ex: keystone).
     * @param string $assetKey Unique asset key (ex: header, footer, fonts, styles)
     *        Note: assetKey can be filename (ex: header.html, styles.css)
     *              in that case file content returned instaed of json structure
     * @link https://github.com/vanilla/roadmap/blob/master/theming/theming-data.md#api
     *
     * @return array|Data
     */
    public function get_assets(string $id, string $assetKey) {
        $this->permission();

        $theme = $this->getThemeByName($id);
        $assets = $theme->getInfoValue(self::ASSET_KEY, []);
        $files = array_column($assets, "file");
        if (!in_array($assetKey, $files)) {
            throw NotFoundException("Asset");
        }

        $content = $this->getFileAsset($theme, $assetKey);
        $result = new Data($content);
        return $result->setHeader("Content-Type", "application/javascript");
    }

    /**
     * Result theme schema
     *
     * @param string $type
     * @return Schema
     */
    private function themeResultSchema(string $type = 'out'): Schema {
        $schema = $this->schema(
            Schema::parse([
                'type:s',
                'themeID:s',
                'logos:s?',
                'mobileLogo:s?',
                'assets?' => $this->assetsSchema()
            ]),
            $type
        );
        return $schema;
    }

    /**
     * Get 'assets' schema
     *
     * @return Schema
     */
    private function assetsSchema(): Schema {
        $schema = Schema::parse([
            "header?" => new InstanceValidatorSchema(HtmlAsset::class),
            "footer?" => new InstanceValidatorSchema(HtmlAsset::class),
            "variables?" => new InstanceValidatorSchema(JsonAsset::class),
            "fonts?" => new InstanceValidatorSchema(FontsAsset::class),
            "scripts?" => new InstanceValidatorSchema(ScriptsAsset::class),
            "styles:s?",
            "javascript:s?",
        ])->setID('themeAssetsSchema');
        return $schema;
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
     * Get theme by name
     *
     * @param string $themeName
     * @return Addon Returns theme addon
     *
     * @throws NotFoundException Throws an exception when themeName not found.
     */
    public function getThemeByName(string $themeName): Addon {
        $theme = $this->addonManager->lookupTheme($themeName);
        if (!($theme instanceof Addon)) {
            throw new NotFoundException("Theme");
        }
        return $theme;
    }

    /**
     * Get list of all available themes
     *
     * @return array List of all available themes
     */
    public function getAllThemes(): array {
        $themes = $this->addonManager->lookupAllByType(Addon::TYPE_THEME);
        $result = [];
        foreach ($themes as $theme) {
            $result[] = $theme->getKey();
        }
        return $result;
    }

    /**
     * Get all theme assets
     *
     * @param Addon $theme
     * @return array
     */
    private function normalizeTheme(Addon $theme): array {
        $res = [
            'type' => 'themeFile',
            'themeID' => $theme->getInfoValue('key'),
            'version' => $theme->getInfoValue('version'),
            'logos' => $theme->getInfoValue('logos'),
            'mobileLogo' => $theme->getInfoValue('mobileLogo'),
        ];

        $res["assets"] = [];
        $themeAssets = $theme->getInfoValue("assets", []);

        $primaryAssets = array_intersect_key(
            $themeAssets,
            array_flip(["fonts", "footer", "header", "scripts", "variables"])
        );
        foreach ($primaryAssets as $assetKey => $asset) {
            $res["assets"][$assetKey] = $this->generateAsset($assetKey, $asset, $theme);
        }

        $secondaryAssets = array_intersect_key(
            $themeAssets,
            array_flip(["javascript", "styles"])
        );
        foreach ($secondaryAssets as $assetKey => $asset) {
            $path = $theme->path("/assets/{$asset['file']}", Addon::PATH_ADDON);
            $res["assets"][$assetKey] = $this->request->url($path, true);
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
        return file_get_contents($fullFilename);
    }

    /**
     * Get theme asset by assetKey.
     *
     * @param string $id
     * @param string $assetKey
     * @return mixed
     * @throws NotFoundException Throws an exception if asset not found.
     */
    private function getThemeAsset(string $id, string $assetKey): array {
        $theme = $this->getThemeByName($id);
        $assets  = $theme->getInfoValue("assets", []);
        $asset = $assets[$assetKey] ?? null;
        if ($asset === null) {
            throw new NotFoundException('Asset "' . $assetKey . '" not found for "' . $theme->getInfoValue('key') . '"');
        }

        $result = $this->getFileAsset($theme, "");
        return [];
    }
}

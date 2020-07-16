<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Garden\JsonFilterTrait;
use Garden\Schema\Schema;
use Vanilla\Addon;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\SchemaFactory;
use Vanilla\Theme\Asset\CssThemeAsset;
use Vanilla\Theme\Asset\HtmlThemeAsset;
use Vanilla\Theme\Asset\JavascriptThemeAsset;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Asset\NeonThemeAsset;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Asset\TwigThemeAsset;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Data class for themes.
 */
class Theme implements \JsonSerializable {

    use JsonFilterTrait;

    /** @var string */
    private $themeID;

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /**
     * The assets of the theme.
     *
     * Map of:
     * $assetKey => ThemeAsset
     * @var array
     */
    private $assets = [];

    /** @var string|null */
    private $version;

    /** @var int|null */
    private $revisionID;

    /** @var string|null */
    private $revisionName;

    /** @var string|null */
    private $parentTheme;

    /** @var \DateTimeInterface|null */
    private $dateInserted;

    /** @var array|null */
    private $insertUser;

    /** @var bool */
    private $current;

    /** @var bool */
    private $active;

    /** @var Addon|null */
    private $addon = null;

    /** @var ThemePreview */
    private $preview;

    /** @var string[] */
    private $supportedSections = [];

    /** @var bool Set if the theme was pulled from the cache. */
    private $isCacheHit = false;

    /**
     * @return Schema
     */
    public static function getSchema(): Schema {
        return SchemaFactory::parse([
            'themeID:s',
            'type:s?',
            'name:s?',
            'version:s?',
            'revisionID:i?',
            'revisionName:s?',
            'insertUser:o?',
            'dateInserted:dt?',
            'current:b?',
            'active:b?',
            'parentTheme:s?',
            'assets:o?',
            'addon?' => new InstanceValidatorSchema(Addon::class),
        ], 'Theme');
    }

    /**
     * Create a theme from an addon.
     *
     * @param Addon $addon
     * @return Theme
     */
    public static function fromAddon(Addon $addon): Theme {
        $key = $addon->getKey();
        $currentOptionKey = \Gdn::config('Garden.ThemeOptions.Styles.Value', '');

        $assets = [];
        $addonAssets = $addon->getInfoValue('assets', []);
        $optionAssetRegex = "/${currentOptionKey}_(.*)/";
        // Trim off the active theme option key.
        foreach ($addonAssets as $addonAssetKey => $addonAsset) {
            preg_match($optionAssetRegex, $addonAssetKey, $matches);
            if (isset($matches[1])) {
                $addonAssets[$matches[1]] = $addonAsset;
            }
        }

        // Merge with the default assets.
        $mixedAssets = array_merge(ThemeAssetFactory::DEFAULT_ASSETS, $addonAssets);
        foreach ($mixedAssets as $mixedAssetKey => $mixedAsset) {
            $file = $mixedAsset['file'] ?? null;
            $type = $mixedAsset['type'] ?? 'unknown';
            if (!$file || !$type) {
                continue;
            }

            $addonFile = $addon->path("/assets/$file");
            if (!file_exists($addonFile)) {
                if (isset($addonAssets[$mixedAssetKey])) {
                    // If someone had explicitly set this path and it doesn't exist, it's a warning.
                    trigger_error("Addon asset $file is specified in $key/addon.json but doesn't exist.", E_USER_WARNING);
                }
                continue;
            }

            $fileUrl = \Gdn::request()->getSimpleUrl($addon->path("/assets/$file", Addon::PATH_ADDON));

            // Fetch the data
            $assets[$mixedAssetKey] = [
                'type' => $type,
                'data' => file_get_contents($addonFile),
                'url' => $fileUrl,
            ];
        }

        $theme = new Theme([
            'themeID' => $addon->getKey(),
            'type' => 'themeFile',
            'name' => $addon->getName(),
            'version' => $addon->getVersion(),
            'assets' => $assets,
        ]);

        $theme->setAddon($addon);

        $icon = $addon->getIcon(Addon::PATH_ADDON);
        if ($icon) {
            $theme->getPreview()->setImageUrl(asset($icon));
        }

        $description = $addon->getInfoValue('description', false);
        if ($description) {
            $theme->getPreview()->addInfo('description', 'Description', $description);
        }

        $authors = $addon->getInfoValue('authors', false);
        if ($authors) {
            $authorString = '';
            foreach ($authors as $author) {
                $authorString .= empty($authorString) ? '' : ', ';
                $authorString .= $author['name'] ?? '';
            }

            $theme->getPreview()->addInfo('string', 'Authors', $authorString);
        }

        return $theme;
    }

    /**
     * Create a theme from a data array.
     *
     * @param array $data
     */
    public function __construct(array $data) {
        $data = self::getSchema()->validate($data);

        $this->themeID = $data['themeID'];
        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->version = $data['version'] ?? null;
        $this->revisionID = $data['revisionID'] ?? null;
        $this->revisionName = $data['revisionName'] ?? null;
        $this->insertUser = $data['insertUser'] ?? null;
        $this->dateInserted = $data['dateInserted'] ?? null;
        $this->current = $data['current'] ?? false;
        $this->parentTheme = $data['parentTheme'] ?? null;
        $this->active = $data['active'] ?? true;
        $this->initializeAssets($data['assets']);
        $this->preview = new ThemePreview();
        $this->preview->addVariablePreview($this->assets['variables']);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->jsonFilter([
            'themeID' => $this->themeID,
            'type' => $this->type,
            'name' => $this->name,
            'version' => $this->version,
            'revisionID' => $this->revisionID,
            'revisionName' => $this->revisionName,
            'insertUser' => $this->insertUser,
            'dateInserted' => $this->dateInserted,
            'current' => $this->current ? true : false,
            'active' => $this->active ? true : false,
            'parentTheme' => $this->parentTheme,
            'assets' => $this->assets,
            'preview' => $this->preview,
            'features' => $this->getFeatures(),
            'supportedSections' => $this->getSupportedSections(),
        ]);
    }

    /**
     * @return ThemeFeatures
     */
    public function getFeatures(): ThemeFeatures {
        $features = new ThemeFeatures(\Gdn::config(), $this->getAddon());
        return $features;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void {
        $this->type = $type;
    }

    /**
     * @return string[]
     */
    public function getSupportedSections(): array {
        return $this->supportedSections;
    }

    /**
     * @param string[] $supportedSections
     */
    public function setSupportedSections(array $supportedSections): void {
        $this->supportedSections = $supportedSections;
    }

    /**
     * Overlay a set of variables on the theme.
     *
     * @param array $variables The variables to overlay.
     */
    public function overlayVariables(array $variables) {
        if (empty($variables)) {
            return;
        }

        // Get the base variables asset.
        $variablesAsset = $this->getAssets()[ThemeAssetFactory::ASSET_VARIABLES] ?? null;
        if ($variablesAsset instanceof JsonThemeAsset) {
            // We want to fully replace arrays instead of merging them.
            // This mirrors our frontend variable handling.
            $merged = ArrayUtils::mergeRecursive($variablesAsset->getValue(), $variables, function ($arr1, $arr2) {
                return $arr2;
            });
            $newAsset = new JsonThemeAsset(json_encode($merged, JSON_UNESCAPED_UNICODE), $variablesAsset->getUrl());
            $this->assets[ThemeAssetFactory::ASSET_VARIABLES] = $newAsset;
        }
    }

    /**
     * Initialize the assets of the theme.
     *
     * @param array $assets
     */
    private function initializeAssets(array $assets) {
        $factory = ThemeAssetFactory::instance();

        foreach ($assets as $assetName => $asset) {
            if (!array_key_exists($assetName, ThemeAssetFactory::DEFAULT_ASSETS)) {
                // Ignored asset.
                continue;
            }

            if ($asset instanceof ThemeAsset) {
                $this->assets[$assetName] = $asset;
                continue;
            }
            $type = $asset['type'] ?? ThemeAssetFactory::DEFAULT_ASSETS[$assetName]['type'] ?? null;
            $data = $asset['data'] ?? null;

            if ($type === null || $data === null) {
                continue;
            }
            $asset = $factory->createAsset($this, $type, $assetName, $data);

            if ($asset) {
                $this->assets[$assetName] = $asset;
            }
        }

        foreach (ThemeAssetFactory::DEFAULT_ASSETS as $assetName => $defaultAsset) {
            if (isset($this->assets[$assetName])) {
                continue;
            }

            // Otherwise create the default.
            $type = $defaultAsset['type'];
            $data = $defaultAsset['default'];
            $asset = $factory->createAsset($this, $type, $assetName, $data);
            $this->assets[$assetName] = $asset;
        }

        // Mix in logo assets.
        foreach ($factory->getLogoAssets($this->getAssets()[ThemeAssetFactory::ASSET_VARIABLES] ?? null) as $assetName => $logoAsset) {
            $this->assets[$assetName] = $logoAsset;
        }
    }

    /**
     * @return ThemePreview
     */
    public function getPreview(): ThemePreview {
        return $this->preview;
    }

    /**
     * @return Addon|null
     */
    public function getAddon(): ?Addon {
        return $this->addon;
    }

    /**
     * @param Addon|null $addon
     */
    public function setAddon(?Addon $addon): void {
        $this->addon = $addon;
    }

    /**
     * @return string
     */
    public function getThemeID(): string {
        return $this->themeID;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string {
        return $this->version;
    }

    /**
     * @return int|null
     */
    public function getRevisionID(): ?int {
        return $this->revisionID;
    }

    /**
     * @return string|null
     */
    public function getRevisionName(): ?string {
        return $this->revisionName;
    }

    /**
     * @return string|null
     */
    public function getParentTheme(): ?string {
        return $this->parentTheme;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateInserted(): ?\DateTimeInterface {
        return $this->dateInserted;
    }

    /**
     * @return array|null
     */
    public function getInsertUser(): ?array {
        return $this->insertUser;
    }

    /**
     * @return bool
     */
    public function isCurrent(): bool {
        return $this->current;
    }

    /**
     * @param bool $current
     */
    public function setCurrent(bool $current): void {
        $this->current = $current;
    }

    /**
     * @return ThemeAsset[]
     */
    public function getAssets(): array {
        return $this->assets;
    }

    /**
     * @param array $assets
     */
    public function setAssets(array $assets): void {
        $this->assets = $assets;
    }

    /**
     * Get a single theme asset.
     *
     * @param string $assetName
     *
     * @return ThemeAsset|null
     */
    public function getAsset(string $assetName): ?ThemeAsset {
        return $this->assets[$assetName] ?? null;
    }

    /**
     * Set a single theme asset.
     *
     * @param string $assetName
     * @param ThemeAsset $asset
     */
    public function setAsset(string $assetName, ThemeAsset $asset): void {
        $this->assets[$assetName] = $asset;
    }

    /**
     * @return bool
     */
    public function isCacheHit(): bool {
        return $this->isCacheHit;
    }

    /**
     * @param bool $isCacheHit
     */
    public function setIsCacheHit(bool $isCacheHit): void {
        $this->isCacheHit = $isCacheHit;
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Exception;
use Garden\Web\RequestInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\FileUtils;
use Webmozart\PathUtil\Path;

/**
 * Collection of asset definitions.
 */
final class WebpackAssetDefinitionCollection
{
    const MANIFEST_JSON = "manifest.json";
    const MANIFEST_PHP = "manifest.php";
    const MANIFESTASYNC_PHP = "manifestAsync.php";

    /** @var string */
    private $section;

    /** @var WebpackAssetDefinition[][] */
    private $jsAssetsByAddonKey;

    /** @var WebpackAssetDefinition[][] */
    private $cssAssetsByAddonKey;

    /** @var WebpackAssetDefinition[] */
    private $jsAssetsGlobal;

    /** @var WebpackAssetDefinition[] */
    private $cssAssetsGlobal;

    /** @var WebpackAssetDefinition */
    private $runtimeJsAsset;

    /** @var WebpackAssetDefinition */
    private $bootstrapJsAsset;

    /** @var array */
    private $allAssetUrls = [];

    /**
     * Constructor.
     *
     * @param string $section
     */
    public function __construct(string $section)
    {
        $this->section = $section;
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return WebpackAssetDefinitionCollection Returns a new definition with the properties from {@link $array}.
     */
    public static function __set_state(array $array): WebpackAssetDefinitionCollection
    {
        $section = $array["section"];
        $collection = new WebpackAssetDefinitionCollection($section);
        foreach ($array as $key => $val) {
            $collection->{$key} = $val;
        }
        return $collection;
    }

    /**
     * Get webpack script assets based on the definitions.
     *
     * @param RequestInterface $request
     * @param string[] $forAddonKeys Addon keys to filter the assets by.
     * @param string $type "css" or "js"
     *
     * @return WebpackAsset[]
     */
    public function createAssets(RequestInterface $request, array $forAddonKeys, string $type): array
    {
        $definitions = [];
        if ($type === "css") {
            $definitions = array_merge(
                $this->cssAssetsGlobal ?? [],
                $this->getDefinitionsForEnabledAddons($forAddonKeys, $this->cssAssetsByAddonKey ?? [])
            );
        } elseif ($type === "js") {
            $definitions = array_merge(
                [$this->runtimeJsAsset],
                $this->jsAssetsGlobal ?? [],
                $this->getDefinitionsForEnabledAddons($forAddonKeys, $this->jsAssetsByAddonKey ?? []),
                [$this->bootstrapJsAsset]
            );
        }

        $assets = [];
        foreach ($definitions as $definition) {
            if ($definition instanceof WebpackAssetDefinition) {
                $assets[] = $definition->asAsset($request);
            }
        }

        return $assets;
    }

    /**
     * Filter assets by their addon.
     *
     * @param string[] $enabledKeys
     * @param WebpackAssetDefinition[][] $addonAssets Assosc array of addon to assets.
     *
     * @return WebpackAssetDefinition[]
     */
    private function getDefinitionsForEnabledAddons(array $enabledKeys, array $addonAssets): array
    {
        // Webpack assets are always lowercased.
        $enabledKeys = array_map("strtolower", $enabledKeys);

        $definitions = [];
        foreach ($enabledKeys as $enabledKey) {
            $found = $addonAssets[$enabledKey] ?? null;
            if (is_array($found)) {
                $definitions = array_merge($definitions, $found);
            }
        }
        return $definitions;
    }

    /**
     * Add an asset.
     *
     * @param WebpackAssetDefinition $assetDefinition
     */
    private function addAsset(WebpackAssetDefinition $assetDefinition)
    {
        if ($this->hasAsset($assetDefinition)) {
            return;
        }
        $this->allAssetUrls[$assetDefinition->getAssetPath()] = true;
        $addonKey = $assetDefinition->getAddonKey();
        $assetType = $assetDefinition->getAssetType();

        switch ($assetType) {
            case "css":
                if ($addonKey !== null) {
                    $this->cssAssetsByAddonKey[$addonKey][] = $assetDefinition;
                } else {
                    $this->cssAssetsGlobal[] = $assetDefinition;
                }
                break;
            case "js":
                if ($addonKey !== null) {
                    $this->jsAssetsByAddonKey[$addonKey][] = $assetDefinition;
                } else {
                    $this->jsAssetsGlobal[] = $assetDefinition;
                }
                break;
            default:
            // Not currently supported, but wepback does generate outputs like this
            // Such as SVGs and images.
        }
    }

    public function hasAsset(WebpackAssetDefinition $assetDefinition): bool
    {
        return isset($this->allAssetUrls[$assetDefinition->getAssetPath()]);
    }

    /**
     * Check that a section is built on-disk.
     *
     * @param string $section
     * @param string $distPath The path to the dist directory.
     *
     * @return bool
     */
    public static function sectionExists(string $section, string $distPath = PATH_DIST): bool
    {
        $sectionDir = Path::join([$distPath, $section]);
        return file_exists($sectionDir);
    }

    /**
     * Load a full collection from the fileystem or cache.
     *
     * @param string $section
     * @param string $distPath The path to the dist directory.
     * @param bool $includeAsync include async assets.
     *
     * @return WebpackAssetDefinitionCollection
     */
    public static function loadFromDist(
        string $section,
        string $distPath = PATH_DIST,
        bool $includeAsync = false
    ): WebpackAssetDefinitionCollection {
        $sectionDir = Path::join([$distPath, $section]);
        $manifestPath = Path::join([$sectionDir, self::MANIFEST_JSON]);
        $cachePath = Path::join([$sectionDir, $includeAsync ? self::MANIFESTASYNC_PHP : self::MANIFEST_PHP]);

        $definition = FileUtils::getCached($cachePath, function () use ($manifestPath, $section, $includeAsync) {
            $collection = new WebpackAssetDefinitionCollection($section);
            if (!file_exists($manifestPath)) {
                trigger_error(
                    "Failed to load webpack manifest for section '$section'. Could not locate them on disk.",
                    E_USER_NOTICE
                );
                return $collection;
            }

            // Load the manifest.
            try {
                $manifest = FileUtils::getArray($manifestPath);
                self::applyManifestToCollection($collection, $manifest, $includeAsync);
            } catch (Exception $e) {
                trigger_error(
                    "Could not decode webpack manifest for section '$section'." . $e->getMessage(),
                    E_USER_NOTICE
                );
            } finally {
                return $collection;
            }
        });

        return $definition;
    }

    /**
     * Apply a manifest to an collection.
     *
     * @param WebpackAssetDefinitionCollection $collection
     * @param array $manifest
     * @param bool $includeAsync
     */
    private static function applyManifestToCollection(
        WebpackAssetDefinitionCollection $collection,
        array $manifest,
        bool $includeAsync = false
    ) {
        foreach ($manifest as $chunkName => $assetInfo) {
            self::addChunkAssetByChunkName($collection, $manifest, $chunkName, $includeAsync);
        }
    }

    private static function addChunkAssetByChunkName(
        WebpackAssetDefinitionCollection $collection,
        array &$manifest,
        string $chunkName,
        bool $includeAsync = false
    ) {
        $assetInfo = $manifest[$chunkName] ?? null;
        if ($assetInfo === null) {
            return;
        }
        $assetPath = $assetInfo["filePath"];
        $isAddon = str_starts_with($chunkName, "addons/");
        $extension = pathinfo($chunkName, PATHINFO_EXTENSION);
        $name = pathinfo($chunkName, PATHINFO_FILENAME);
        $addonKey = null;

        if ($isAddon) {
            // Trim the "common" from the name.
            $addonKey = str_replace([".js", "min.js", ".css", ".min.css"], "", $name);
            $addonKey = trim(str_replace("-common", "", $addonKey));
            // Many assets have hash of what chunk they are split from here.
            $addonKey = preg_replace("/-[a-f0-9]{8}$/", "", $addonKey);
        } else {
            if (str_contains($assetPath, "/async/") && !$includeAsync) {
                // Non-addon async chunks are loaded by webpack, not by the frontend.
                return;
            }
        }

        $asset = new WebpackAssetDefinition($assetPath, $extension, $collection->section, $addonKey);
        switch ($name) {
            case "runtime":
                $collection->runtimeJsAsset = $asset;
                break;
            case "bootstrap":
                $collection->bootstrapJsAsset = $asset;
                break;
            default:
                $collection->addAsset($asset);
                break;
        }

        // Currently we don't actually load the dependenents because they result loading more javascript than we need.
        // In order to trust them we'll need to have a better mechanism for determining them at build time.
        // When that happens we may want to recurse into 'dependsOnAsyncChunks'.
    }
}

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
final class WebpackAssetDefinitionCollection {

    const MANIFEST_JSON = "manifest.json";
    const MANIFEST_PHP = "manifest.php";

    /** @var string */
    private $section;

    /** @var WebpackAssetDefinition[][] */
    private $jsAssetsByAddonKey;

    /** @var WebpackAssetDefinition[][] */
    private $cssAssetsByAddonKey;

    /** @var WebpackAssetDefinitionCollection[] */
    private $jsAssetsGlobal;

    /** @var WebpackAssetDefinitionCollection[] */
    private $cssAssetsGlobal;

    /** @var WebpackAssetDefinition */
    private $runtimeJsAsset;

    /** @var WebpackAssetDefinition */
    private $bootstrapJsAsset;

    /**
     * Constructor.
     *
     * @param string $section
     */
    public function __construct(string $section) {
        $this->section = $section;
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return WebpackAssetDefinitionCollection Returns a new definition with the properties from {@link $array}.
     */
    public static function __set_state(array $array): WebpackAssetDefinitionCollection {
        $section = $array['section'];
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
    public function createAssets(RequestInterface $request, array $forAddonKeys, string $type): array {
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
    private function getDefinitionsForEnabledAddons(array $enabledKeys, array $addonAssets): array {
        // Webpack assets are always lowercased.
        $enabledKeys = array_map('strtolower', $enabledKeys);

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
    private function addAsset(WebpackAssetDefinition $assetDefinition) {
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

    /**
     * Check that a section is built on-disk.
     *
     * @param string $section
     * @param string $distPath The path to the dist directory.
     *
     * @return bool
     */
    public static function sectionExists(string $section, string $distPath = PATH_DIST): bool {
        $sectionDir = Path::join([$distPath, $section]);
        return file_exists($sectionDir);
    }

    /**
     * Load a full collection from the fileystem or cache.
     *
     * @param string $section
     * @param string $distPath The path to the dist directory.
     *
     * @return WebpackAssetDefinitionCollection
     */
    public static function loadFromDist(string $section, string $distPath = PATH_DIST): WebpackAssetDefinitionCollection {
        $sectionDir = Path::join([$distPath, $section]);
        $manifestPath = Path::join([$sectionDir, self::MANIFEST_JSON]);
        $cachePath = Path::join([$sectionDir, self::MANIFEST_PHP]);

        $definition = FileUtils::getCached($cachePath, function () use ($manifestPath, $section) {
            $collection = new WebpackAssetDefinitionCollection($section);
            if (!file_exists($manifestPath)) {
                trigger_error("Failed to load webpack manifest for section '$section'. Could not locate them on disk.", E_USER_NOTICE);
                return $collection;
            }

            // Load the manifest.
            try {
                $manifest = FileUtils::getArray($manifestPath);
                self::applyManifestToCollection($collection, $manifest);
            } catch (Exception $e) {
                trigger_error("Could not decode webpack manifest for section '$section'." . $e->getMessage(), E_USER_NOTICE);
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
     */
    private static function applyManifestToCollection(WebpackAssetDefinitionCollection $collection, array $manifest) {
        foreach ($manifest as $entryPath => $assetPath) {
            $isAddon = str_starts_with($entryPath, 'addons/');
            $extension = pathinfo($entryPath, PATHINFO_EXTENSION);
            $name = pathinfo($entryPath, PATHINFO_FILENAME);
            $addonKey = null;

            if (str_contains($assetPath, '/async/')) {
                // Async plugins are loaded by webpack, not by the frontend.
                continue;
            }

            if ($isAddon) {
                // Trim the "common" from the name.
                $addonKey = trim(str_replace("-common", "", $name));
                // Many assets have hash of what chunk they are split from here.
                $addonKey = preg_replace("/-[a-f0-9]{8}$/", "", $addonKey);
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
        }
    }
}

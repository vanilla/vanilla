<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Addon;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\Contracts\Web\AssetTrait;
use Webmozart\PathUtil\Path;

/**
 * Class the represents a build artifact from vite.

 */
class ViteBuildArtifact implements AssetInterface
{
    use AssetTrait;

    /** @var string What build section we belong to. */
    public string $buildSection;

    /** @var string The assetID of the asset. */
    public string $assetID;

    /** @var string|mixed The file path of the asset relative to the project. */
    public string $file;

    /** @var string[] IDs of assets that can be dynamically imported by this asset. */
    public array $dynamicImportAssetIDs;
    /** @var string[] IDs of assets that will be imported directly by this asset. */
    public array $importAssetIDs;

    /** @var string|mixed Name of the asset. */
    public string $name;

    /**
     * Constructor.
     *
     * @param string $buildSection
     * @param string $assetID
     * @param array $jsonBlob
     */
    public function __construct(string $buildSection, string $assetID, array $jsonBlob)
    {
        $this->buildSection = $buildSection;
        $this->assetID = $assetID;
        $this->file = $jsonBlob["file"];
        $this->name = $jsonBlob["name"] ?? "unknown";
        $this->dynamicImportAssetIDs = $jsonBlob["dynamicImports"] ?? ($jsonBlob["dynamicImportAssetIDs"] ?? []);
        $this->importAssetIDs = $jsonBlob["imports"] ?? ($jsonBlob["importAssetIDs"] ?? []);
    }

    /**
     * Support {@link var_export()} for caching.
     *
     * @param array $array The array to load.
     * @return ViteBuildArtifact Returns a new addon with the properties from {@link $array}.
     */
    public static function __set_state(array $array)
    {
        return new ViteBuildArtifact($array["buildSection"], $array["assetID"], $array);
    }

    /**
     * All vite scripts are modules.
     *
     * @return string
     */
    public function isScriptModule(): string
    {
        return $this->isScript();
    }

    /**
     * @inheritdoc
     */
    public function getWebPath(): string
    {
        return \Gdn::request()->getSimpleUrl(Path::join(["/", PATH_DIST_NAME, $this->buildSection, $this->file]));
    }

    /**
     * Given a list of all assets, resolve all direct dependencies of this asset.
     *
     * @param array<string, ViteBuildArtifact> $allAssetsByID
     *
     * @return ViteBuildArtifact[]
     */
    public function resolveDependencies(array $allAssetsByID): array
    {
        $resolvedAssets = [];

        $assetIDsToResolve = $this->importAssetIDs;
        while (!empty($assetIDsToResolve)) {
            $assetID = array_shift($assetIDsToResolve);

            if (isset($resolvedAssets[$assetID])) {
                // Already did it, prevent an infinite recursion.
                continue;
            }

            $asset = $allAssetsByID[$assetID] ?? null;
            if ($asset !== null) {
                // Definite a dependency
                $resolvedAssets[$assetID] = $asset;

                $assetIDsToResolve = array_unique(array_merge($assetIDsToResolve, $asset->importAssetIDs));
            } else {
                $resolvedAssets[$assetID] = null;
            }
        }

        return array_values(array_filter($resolvedAssets));
    }

    /**
     * Determine if this is required for initial page load.
     *
     * @param string $section
     *
     * @return bool
     */
    public function isEntry(string $section): bool
    {
        $isSectionEntry = preg_match("/^assets\/{$section}/", $this->file);
        $isAddonEntry = preg_match("/entries\/addons/", $this->file);

        return $isSectionEntry || $isAddonEntry;
    }

    /**
     * Determine if the asset requires a particular addon.
     *
     * @param array $addonKeys
     * @return bool
     */
    public function belongsToAddon(array $addonKeys)
    {
        foreach ($addonKeys as $addonKey) {
            $isAddon = str_contains($this->file, "addons/{$addonKey}");
            if ($isAddon) {
                return true;
            }
        }
        return false;
    }
}

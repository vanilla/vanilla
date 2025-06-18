<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Asset;

use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Web\AssetInterface;
use Vanilla\FileUtils;
use Vanilla\Theme\ThemeService;
use Vanilla\Web\TwigRenderTrait;
use Webmozart\PathUtil\Path;

/**
 * Class to determine which vite assets to load based on:
 *
 * - Which build section we need.
 * - Which addons are enabled.
 * - If we are in hot build mode.
 * - The built vite manifest for the build section.
 */
final class ViteAssetProvider
{
    use TwigRenderTrait;

    private \Gdn_Request $request;
    private AddonManager $addonManager;
    private ThemeService $themeService;
    private \Gdn_Session $session;
    private ConfigurationInterface $config;
    private DeploymentCacheBuster $deploymentCacheBuster;

    /**
     * DI.
     */
    public function __construct(
        \Gdn_Request $request,
        AddonManager $addonManager,
        ThemeService $themeService,
        \Gdn_Session $session,
        ConfigurationInterface $config,
        DeploymentCacheBuster $deploymentCacheBuster
    ) {
        $this->request = $request;
        $this->addonManager = $addonManager;
        $this->themeService = $themeService;
        $this->session = $session;
        $this->config = $config;
        $this->deploymentCacheBuster = $deploymentCacheBuster;
    }

    /**
     * @return LocaleAsset
     */
    public function getLocaleAsset(): LocaleAsset
    {
        return new LocaleAsset($this->request, \Gdn::locale()->current(), $this->deploymentCacheBuster->value());
    }

    /**
     * @return bool
     */
    public function isHotBuild(): bool
    {
        return $this->config->get("HotReload.Enabled");
    }

    /**
     * Get all assets for a section that are needed for initial page load.
     * Filtered to only return assets matching currently enabled addons.
     *
     * @param string $buildSection
     *
     * @return AssetInterface[]
     */
    public function getEnabledEntryAssets(string $buildSection): array
    {
        $assetsByID = $this->getAssetsByID($buildSection);
        $entryAssets = ["locales" => $this->getLocaleAsset()];
        $enabledAddonKeys = $this->getEnabledAddonKeys();
        foreach ($assetsByID as $asset) {
            if (
                ($asset->isAddonEntry() && $asset->belongsToAddon($enabledAddonKeys)) ||
                $asset->isAsset() ||
                $asset->isPrimaryEntry()
            ) {
                $entryAssets[$asset->assetID] = $asset;
                // Push in the dependencies
                $entryAssets = array_merge($entryAssets, $asset->resolveDependencies($assetsByID));
            }
        }
        $entryAssets = array_values($entryAssets);

        return $entryAssets;
    }

    /**
     * Get content for an inline bootstrapping script.
     *
     * Register vanilla globals.
     *
     * @param string $buildSection
     *
     * @return string The contents of the script.
     */
    public function getBootstrapInlineScript(string $buildSection): string
    {
        return $this->renderTwig("library/Vanilla/Web/Asset/InlineBootstrapContent.js.twig", [
            "debugModeLiteral" => debug() ? "true" : "false",
            "enabledAddonKeys" => json_encode($this->getEnabledAddonKeys()),
            "buildSection" => $buildSection,
        ]);
    }

    /**
     * Get an asset to load from a remote site indicating that we are in a hot build.
     *
     * @return ViteBuildArtifact|null
     */
    public function getEmbedAsset(): ?ViteBuildArtifact
    {
        $assets = $this->getAssetsByNames("embed", ["modernEmbed.remote"]);
        $asset = array_values($assets)[0] ?? null;
        return $asset;
    }

    /**
     * Get an inline script to inject into the page to enable hot reloading.
     *
     * @return string
     */
    function getHotBuildInlineScript(): string
    {
        return <<<JS
            import RefreshRuntime from "http://127.0.0.1:3030/@react-refresh"
            RefreshRuntime.injectIntoGlobalHook(window)
            window.\$RefreshReg\$ = () => {}
            window.\$RefreshSig\$ = () => (type) => type
            window.__vite_plugin_react_preamble_installed__ = true
JS;
    }

    /**
     * Get all assets needed for initial page load when we are running a hot build.
     *
     * @param string $section
     * @return WebAsset[]
     */
    function getHotBuildScriptAssets(string $section): array
    {
        return [
            new WebAsset("http://127.0.0.1:3030/@vite/client", true),
            new WebAsset("http://127.0.0.1:3030/build/.vite/{$section}.js", true),
            $this->getLocaleAsset(),
        ];
    }

    /**
     * Get assets in a particular build section matching specific names.
     *
     * All assets that are direct dependnecies will be included as well.
     *
     * @param string $section The build section.
     * @param array $names The file name.
     * @return array<string, ViteBuildArtifact>
     */
    public function getAssetsByNames(string $section, array $names): array
    {
        $assets = [];
        $allAssets = $this->getAssetsByID($section);
        foreach ($allAssets as $asset) {
            if (in_array($asset->name, $names)) {
                $assets[$asset->assetID] = $asset;
            }
        }

        foreach ($assets as $asset) {
            $dependencies = $asset->resolveDependencies($allAssets);
            foreach ($dependencies as $dependency) {
                $assets[$dependency->assetID] = $dependency;
            }
        }

        return $assets;
    }

    /**
     * Get a list of all assets in a build section by their assetID.
     *
     * @return array<string, ViteBuildArtifact>
     */
    private function getAssetsByID(string $section): array
    {
        $manifestPhpPath = Path::join(PATH_DIST, $section, ".vite/manifest.php");
        $manifestJsonPath = Path::join(PATH_DIST, $section, ".vite/manifest.json");

        if (!file_exists($manifestJsonPath)) {
            return [];
        }
        $assetsByID = FileUtils::getCached($manifestPhpPath, function () use ($section, $manifestJsonPath) {
            $json = file_get_contents($manifestJsonPath);
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                // This is totally expected in the hot build mode.
                return [];
            }

            $assetsByID = [];
            foreach ($decoded as $id => $data) {
                if (!empty($data["css"])) {
                    // Kludge in css files as assets.

                    foreach ($data["css"] as $cssFile) {
                        $data["imports"][] = $cssFile;
                        $assetsByID[$cssFile] = new ViteBuildArtifact($section, $cssFile, [
                            "file" => $cssFile,
                            "name" => $data["name"] ?? null,
                        ]);
                    }
                }

                $assetsByID[$id] = new ViteBuildArtifact($section, $id, $data);
            }

            return $assetsByID;
        });

        return $assetsByID;
    }

    /**
     * Check if current theme need to be replaced by some preview theme
     *
     * @param Addon $addon
     * @return Addon
     */
    private function checkReplacePreview(Addon $addon): Addon
    {
        if ($addon->getType() !== "theme") {
            return $addon;
        }
        if ($previewThemeKey = $this->session->getPreference("PreviewThemeKey")) {
            $addonKey = $this->themeService->getMasterThemeKey($previewThemeKey);
            if ($previewTheme = $this->addonManager->lookupTheme($addonKey)) {
                $addon = $previewTheme;
            }
        }
        return $addon;
    }

    /**
     * @return string[]
     */
    private function getEnabledAddonKeys(): array
    {
        $enabledKeys = ["library"];
        foreach ($this->addonManager->getEnabled() as $addon) {
            $addon = $this->checkReplacePreview($addon);
            $enabledKeys[] = strtolower($addon->getKey());
        }
        return $enabledKeys;
    }
}

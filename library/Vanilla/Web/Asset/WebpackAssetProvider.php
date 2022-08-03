<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Contracts;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Theme\ThemeService;
use Webmozart\PathUtil\Path;

/**
 * Class to provide assets from the webpack build process.
 */
class WebpackAssetProvider
{
    use TwigRenderTrait;

    const COLLECTION_SECTION = "async";

    /** @var RequestInterface */
    private $request;

    /** @var AddonManager */
    private $addonManager;

    /** @var \Gdn_Session */
    private $session;

    /** @var ConfigurationInterface */
    private $config;

    /** @var ThemeService */
    private $themeService;

    /** @var string */
    private $cacheBustingKey = "";

    /** @var string */
    private $localeKey = "";

    /** @var bool */
    private $hotReloadEnabled = false;

    /** @var string */
    private $fsRoot = PATH_ROOT;

    /** @var WebpackAssetDefinitionCollection[] */
    private $collectionForSection = [];

    /** @var string[] */
    private $enabledAddonKeys = null;

    /**
     * WebpackAssetProvider constructor.
     *
     * @param RequestInterface $request
     * @param AddonManager $addonManager
     * @param \Gdn_Session $session
     * @param ConfigurationInterface $config
     * @param ThemeService $themeService
     */
    public function __construct(
        RequestInterface $request,
        AddonManager $addonManager,
        \Gdn_Session $session,
        ConfigurationInterface $config,
        ThemeService $themeService
    ) {
        $this->request = $request;
        $this->addonManager = $addonManager;
        $this->session = $session;
        $this->config = $config;
        $this->themeService = $themeService;
    }

    /**
     * Clear the collections in memory.
     */
    public function clearCollections()
    {
        $this->collectionForSection = [];
    }

    /**
     * Enable loading of hot reloading assets in place of the normal ones.
     *
     * @param bool $enabled The enable value.
     */
    public function setHotReloadEnabled(bool $enabled)
    {
        $this->hotReloadEnabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isHotReloadEnabled(): bool
    {
        return $this->hotReloadEnabled;
    }

    /**
     * Set the key of the active locale.
     *
     * @param string $key
     */
    public function setLocaleKey(string $key)
    {
        $this->localeKey = $key;
    }

    /**
     * Set a key to be used to bust the caching of assets.
     *
     * @param string $key
     */
    public function setCacheBusterKey(string $key)
    {
        $this->cacheBustingKey = $key;
    }

    /**
     * Get script assets built from webpack using the in-repo build process.
     *
     * These follow a pretty strict pattern of:
     *
     * - webpack runtime
     * - vendor chunk
     * - library chunk
     * - addon chunks
     * - bootstrap
     *
     * @param string $section - The section of the site to lookup.
     * @return WebpackAsset[] The assets files for all webpack scripts.
     */
    public function getScripts(string $section, bool $includeAsync = false): array
    {
        $scripts = [];

        // Locale asset is always included if we have a locale set.
        if ($this->localeKey) {
            $localeAsset = new LocaleAsset($this->request, $this->localeKey, $this->cacheBustingKey);
            $scripts[] = $localeAsset;
        }

        // Return early with the hot build if that flag is enabled.
        if ($this->hotReloadEnabled) {
            $scripts[] = new HotBuildAsset($section);
            return $scripts;
        }

        $collection = $this->getCollectionForSection($section, $includeAsync);
        $scripts = array_merge($scripts, $collection->createAssets($this->request, $this->getEnabledAddonKeys(), "js"));
        return $scripts;
    }

    /**
     * Get a colleciton for the current section.
     *
     * @param string $section
     * @param bool $includeAsync Include async assets.
     *
     * @return WebpackAssetDefinitionCollection
     */
    private function getCollectionForSection(
        string $section,
        bool $includeAsync = false
    ): WebpackAssetDefinitionCollection {
        $key = $section . ($includeAsync ? self::COLLECTION_SECTION : "");
        if (!isset($this->collectionForSection[$key])) {
            $distPath = Path::join($this->fsRoot, PATH_DIST_NAME);
            if (WebpackAssetDefinitionCollection::sectionExists($section, $distPath)) {
                $this->collectionForSection[$key] = WebpackAssetDefinitionCollection::loadFromDist(
                    $section,
                    $distPath,
                    $includeAsync
                );
            } else {
                $this->collectionForSection[$key] = new WebpackAssetDefinitionCollection($section);
            }
        }
        return $this->collectionForSection[$key];
    }

    /**
     * Get the embed asset.
     *
     * @return WebpackAsset
     */
    public function getEmbedAsset(): WebpackAsset
    {
        $collection = $this->getCollectionForSection("embed");
        $assets = $collection->createAssets($this->request, [], "js");
        return $assets[0];
    }

    /**
     * Get the enabled addon keys.
     *
     * @return string[]
     */
    private function getEnabledAddonKeys(): array
    {
        if ($this->enabledAddonKeys === null) {
            $this->enabledAddonKeys = ["library"];
            foreach ($this->addonManager->getEnabled() as $addon) {
                $addon = $this->checkReplacePreview($addon);
                $this->enabledAddonKeys[] = strtolower($addon->getKey());
            }
        }
        return $this->enabledAddonKeys;
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
     * Get all stylesheets for a particular site section.
     *
     * @param string $section
     * @param bool $includeAsync Include async assets.
     *
     * @return WebpackAsset[]
     */
    public function getStylesheets(string $section, bool $includeAsync = false): array
    {
        if ($this->hotReloadEnabled) {
            // All style sheets are managed by the hot javascript bundle.
            return [];
        }

        $collection = $this->getCollectionForSection($section, $includeAsync);
        $styles = $collection->createAssets($this->request, $this->getEnabledAddonKeys(), "css");
        return $styles;
    }

    /**
     * Set the root direct this class should use for it's file system.
     *
     * @internal
     *
     * @param string $fsRoot
     */
    public function setFsRoot(string $fsRoot)
    {
        $this->fsRoot = $fsRoot;
    }

    /**
     * Get content for an inline polyfill script.
     *
     * It checks for support for the following:
     * - Promise,
     * - fetch,
     * - Symbol,
     * - Various new Element/NodeList methods.
     *
     * If a single one is missing we will block the page load to add all polyfills.
     * This allows to us to
     * - keep the polyfill simple.
     * - Ship 0 polyfills to users modern browsers (basically after 2016 release).
     *
     * @return string The contents of the script.
     */
    public function getInlinePolyfillContents(): string
    {
        return $this->renderTwig("library/Vanilla/Web/Asset/InlinePolyfillContent.js.twig", [
            "debugModeLiteral" => debug() ? "true" : "false",
            "polyfillAsset" => new PolyfillAsset($this->request, $this->cacheBustingKey),
            "enabledAddonKeys" => json_encode($this->getEnabledAddonKeys()),
        ]);
    }
}

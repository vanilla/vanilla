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

/**
 * Class to provide assets from the webpack build process.
 */
class WebpackAssetProvider {

    use TwigRenderTrait;

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
    private $cacheBustingKey = '';

    /** @var string */
    private $localeKey = "";

    /** @var bool */
    private $hotReloadEnabled = false;

    /** @var string */
    private $fsRoot = PATH_ROOT;

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
     * Enable loading of hot reloading assets in place of the normal ones.
     *
     * @param bool $enabled The enable value.
     */
    public function setHotReloadEnabled(bool $enabled) {
        $this->hotReloadEnabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isHotReloadEnabled(): bool {
        return $this->hotReloadEnabled;
    }

    /**
     * Set the key of the active locale.
     *
     * @param string $key
     */
    public function setLocaleKey(string $key) {
        $this->localeKey = $key;
    }

    /**
     * Set a key to be used to bust the caching of assets.
     *
     * @param string $key
     */
    public function setCacheBusterKey(string $key) {
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
    public function getScripts(string $section): array {
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

        // A couple of required assets.
        $scripts[] = $this->makeScript($section, 'runtime');
        $scripts[] = $this->makeScript($section, 'vendors');

        // The library chunk is not always created if there is nothing shared between entry-points.
        $shared = $this->makeScript($section, 'shared');
        if ($shared->existsOnFs()) {
            $scripts[] = $shared;
        }

        // Grab all of the addon based assets.
        foreach ($this->addonManager->getEnabled() as $addon) {
            $addon = $this->checkReplacePreview($addon);
            // See if we have a common bundle
            $commonAsset = new WebpackAddonAsset(
                $this->request,
                WebpackAsset::SCRIPT_EXTENSION,
                $section,
                $addon,
                $this->cacheBustingKey,
                true
            );
            $commonAsset->setFsRoot($this->fsRoot);

            if ($commonAsset->existsOnFs()) {
                $scripts[] = $commonAsset;
            }

            $asset = new WebpackAddonAsset(
                $this->request,
                WebpackAsset::SCRIPT_EXTENSION,
                $section,
                $addon,
                $this->cacheBustingKey
            );
            $asset->setFsRoot($this->fsRoot);

            if ($asset->existsOnFs()) {
                $scripts[] = $asset;
            }
        }

        // The bootstrap asset ties everything together.
        $scripts[] = $this->makeScript($section, 'bootstrap');

        return $scripts;
    }

    /**
     * Check if current theme need to be replaced by some preview theme
     *
     * @param Addon $addon
     * @return Addon
     */
    private function checkReplacePreview(Addon $addon): Addon {
        $currentConfigThemeKey = $this->config->get('Garden.CurrentTheme', $this->config->get('Garden.Theme'));
        $currentThemeKey = $this->themeService->getMasterThemeKey($currentConfigThemeKey);
        if ($previewThemeKey = $this->session->getPreference('PreviewThemeKey')) {
            if ($addon->getKey() === $currentThemeKey) {
                $addonKey = $this->themeService->getMasterThemeKey($previewThemeKey);
                if ($previewTheme = $this->addonManager->lookupTheme($addonKey)) {
                    $addon = $previewTheme;
                }
            }
        }
        return $addon;
    }

    /**
     * Get all stylesheets for a particular site section.
     *
     * @param string $section
     *
     * @return WebpackAsset[]
     */
    public function getStylesheets(string $section): array {
        if ($this->hotReloadEnabled) {
            // All style sheets are managed by the hot javascript bundle.
            return [];
        }

        $styles = [];

        $sharedStyles = new WebpackAsset(
            $this->request,
            WebpackAsset::STYLE_EXTENSION,
            $section,
            'shared',
            $this->cacheBustingKey
        );

        $vendorStyles = new WebpackAsset(
            $this->request,
            WebpackAsset::STYLE_EXTENSION,
            $section,
            'vendors',
            $this->cacheBustingKey
        );

        if ($sharedStyles->existsOnFs()) {
            $styles[] = $sharedStyles;
        }

        if ($vendorStyles->existsOnFs()) {
            $styles[] = $vendorStyles;
        }

        // Grab all of the addon based assets.
        foreach ($this->addonManager->getEnabled() as $addon) {
            $addon = $this->checkReplacePreview($addon);
            $asset = new WebpackAddonAsset(
                $this->request,
                WebpackAsset::STYLE_EXTENSION,
                $section,
                $addon,
                $this->cacheBustingKey
            );
            $asset->setFsRoot($this->fsRoot);

            if ($asset->existsOnFs()) {
                $styles[] = $asset;
            }
        }

        return $styles;
    }

    /**
     * Set the root direct this class should use for it's file system.
     *
     * @internal
     *
     * @param string $fsRoot
     */
    public function setFsRoot(string $fsRoot) {
        $this->fsRoot = $fsRoot;
    }

    /**
     * Make a script asset.
     *
     * @param string $section The section of the script.
     * @param string $name The name of the script.
     *
     * @return WebpackAsset A webpack script asset.
     */
    private function makeScript(string $section, string $name): WebpackAsset {
        $asset = new WebpackAsset(
            $this->request,
            WebpackAsset::SCRIPT_EXTENSION,
            $section,
            $name,
            $this->cacheBustingKey
        );
        $asset->setFsRoot($this->fsRoot);
        return $asset;
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
    public function getInlinePolyfillContents(): string {
        return $this->renderTwig("library/Vanilla/Web/Asset/InlinePolyfillContent.js.twig", [
            'debugModeLiteral' => debug() ? "true" : "false",
            'polyfillAsset' => new PolyfillAsset($this->request, $this->cacheBustingKey),
        ]);
    }
}

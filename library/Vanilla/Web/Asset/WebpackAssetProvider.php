<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

use Garden\Web\RequestInterface;
use Vanilla\Contracts;

/**
 * Class to provide assets from the webpack build process.
 */
class WebpackAssetProvider {

    /** @var RequestInterface */
    private $request;

    /** @var Contracts\AddonProviderInterface */
    private $addonProvider;

    /** @var string */
    private $cacheBustingKey = '';

    /** @var string */
    private $localeKey = "";

    /** @var bool */
    private $hotReloadEnabled = false;

    /** @var string */
    private $hotReloadIP;

    /** @var string */
    private $fsRoot = PATH_ROOT;

    /**
     * WebpackAssetProvider constructor.
     *
     * @param RequestInterface $request
     * @param Contracts\AddonProviderInterface $addonProvider
     */
    public function __construct(
        RequestInterface $request,
        Contracts\AddonProviderInterface $addonProvider
    ) {
        $this->request = $request;
        $this->addonProvider = $addonProvider;
    }

    /**
     * Enable loading of hot reloading assets in place of the normal ones.
     *
     * @param bool $enabled The enable value.
     * @param string $ip Optionally override the ip address the hot bundle is served from.
     */
    public function setHotReloadEnabled(bool $enabled, string $ip = "") {
        $this->hotReloadEnabled = $enabled;
        $this->hotReloadIP = $ip ?: "127.0.0.1";
    }

    /**
     * @return bool
     */
    public function isHotReloadEnabled(): bool {
        return $this->hotReloadEnabled;
    }

    /**
     * @return string
     */
    public function getHotReloadSocketAddress(): string {
        return 'http://'.$this->hotReloadIP.':3030';
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
            return [new HotBuildAsset($section, $this->hotReloadIP)];
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
        foreach ($this->addonProvider->getEnabled() as $addon) {

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
        // Grab all of the addon based assets.
        foreach ($this->addonProvider->getEnabled() as $addon) {
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
        $polyfillAsset = new PolyfillAsset($this->request, $this->cacheBustingKey);
        $debug = debug();
        $logAdding = $debug ? 'console.log("Older browser detected. Initiating polyfills.");' : '';
        $logNotAdding = $debug ? 'console.log("Modern browser detected. No polyfills necessary");' : '';

        // Add the polyfill loader.
        $scriptContent =
            "var supportsAllFeatures = window.Promise && window.fetch && window.Symbol"
            ."&& window.CustomEvent && Element.prototype.remove && Element.prototype.closest"
            ."&& window.NodeList && NodeList.prototype.forEach;"
            ."if (!supportsAllFeatures) {"
            .$logAdding
            ."var head = document.getElementsByTagName('head')[0];"
            ."var script = document.createElement('script');"
            ."script.src = '".$polyfillAsset->getWebPath()."';"
            ."head.appendChild(script);"
            ."} else { $logNotAdding }";

        return $scriptContent;
    }
}

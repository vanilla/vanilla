<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    /** @var Contracts\Web\CacheBusterInterface */
    private $cacheBuster;

    /** @var Contracts\AddonProviderInterface */
    private $addonProvider;

    /** @var Contracts\ConfigurationInterface */
    private $config;

    /** @var \Gdn_Locale */
    private $locale;

    /**
     * WebpackAssetProvider constructor.
     *
     * @param RequestInterface $request
     * @param Contracts\Web\CacheBusterInterface $cacheBuster
     * @param Contracts\AddonProviderInterface $addonProvider
     * @param Contracts\ConfigurationInterface $config
     * @param \Gdn_Locale $locale
     */
    public function __construct(
        RequestInterface $request,
        Contracts\Web\CacheBusterInterface $cacheBuster,
        Contracts\AddonProviderInterface $addonProvider,
        Contracts\ConfigurationInterface $config,
        \Gdn_Locale $locale
    ) {
        $this->request = $request;
        $this->cacheBuster = $cacheBuster;
        $this->addonProvider = $addonProvider;
        $this->config = $config;
        $this->locale = $locale;
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
        // Return early with the hot build if that flag is enabled.
        if ($this->config->get('HotReload.Enabled')) {
            return [new HotBuildAsset(
                $section,
                $this->config->get('HotReload.IP', null)
            )];
        }

        // A couple of required assets.
        $scripts = [
            $this->makeScript($section, 'runtime'),
            $this->makeScript($section, 'vendors'),
        ];

        // The library chunk is not always created if there is nothing shared between entry-points.
        $shared = $this->makeScript($section, 'shared');
        if ($shared->existsOnFs()) {
            $scripts[] = $shared;
        }

        // Grab all of the addon based assets.
        foreach ($this->addonProvider->getEnabled() as $addon) {
            $asset = new WebpackAddonAsset(
                $this->request,
                $this->cacheBuster,
                WebpackAsset::SCRIPT_EXTENSION,
                $section,
                $addon
            );

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
        if ($this->config->get('HotReload.Enabled')) {
            return [];
        }

        $styles = [];
        // Grab all of the addon based assets.
        foreach ($this->addonProvider->getEnabled() as $addon) {
            $asset = new WebpackAddonAsset(
                $this->request,
                $this->cacheBuster,
                WebpackAsset::STYLE_EXTENSION,
                $section,
                $addon
            );

            if ($asset->existsOnFs()) {
                $styles[] = $asset;
            }
        }

        return $styles;
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
        return new WebpackAsset(
            $this->request,
            $this->cacheBuster,
            WebpackAsset::SCRIPT_EXTENSION,
            $section,
            $name
        );
    }

    /**
     * Get a local asset for the current locale.
     *
     * @return LocaleAsset
     */
    public function getLocaleAsset(): LocaleAsset {
        return new LocaleAsset($this->request, $this->cacheBuster, $this->locale->current());
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
        $polyfillAsset = new PolyfillAsset($this->request, $this->cacheBuster);
        $debug = $this->config->get('Debug', false);
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

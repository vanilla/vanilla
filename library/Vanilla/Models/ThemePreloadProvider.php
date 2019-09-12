<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Web\Asset\ThemeScriptAsset;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;

/**
 * Class for preloading theme data into the frontend.
 */
class ThemePreloadProvider implements ReduxActionProviderInterface {

    /** @var SiteMeta */
    private $siteMeta;

    /** @var \ThemesApiController */
    private $themesApi;

    /** @var RequestInterface */
    private $request;

    /** @var DeploymentCacheBuster */
    private $cacheBuster;

    /** @var array */
    private $themeData;

    /**
     * DI.
     *
     * @param SiteMeta $siteMeta
     * @param \ThemesApiController $themesApi
     * @param RequestInterface $request
     * @param DeploymentCacheBuster $cacheBuster
     */
    public function __construct(
        SiteMeta $siteMeta,
        \ThemesApiController $themesApi,
        RequestInterface $request,
        DeploymentCacheBuster $cacheBuster
    ) {
        $this->siteMeta = $siteMeta;
        $this->themesApi = $themesApi;
        $this->request = $request;
        $this->cacheBuster = $cacheBuster;
    }

    /**
     * Get a script asset for the theme.
     * If the theme doesn't define a script asset, return null.
     *
     * @return ThemeScriptAsset|null
     */
    public function getThemeScript(): ?ThemeScriptAsset {
        if (!$this->getThemeData()['assets']['javascript']) {
            return null;
        }

        return new ThemeScriptAsset(
            $this->request,
            $this->siteMeta->getActiveTheme()->getKey(),
            // Use both the theme version and the deployment to make a more robust cache buster.
            // People often forget to increment their theme version in file based themes
            // so adding the deployment cache buster to the theme version handles this case.
            $this->getThemeData()['version'] . '-' . $this->cacheBuster->value()
        );
    }

    /**
     * Get the theme data (with some local caching so it isn't requested twice).
     *
     * This data follows the format described in the ThemesApiController.
     */
    public function getThemeData(): array {
        if (!$this->themeData) {
            $themeKey = $this->siteMeta->getActiveTheme()->getKey();
            $this->themeData = $this->themesApi->get($themeKey);
        }

        return $this->themeData;
    }

    /**
     * @return array
     */
    public function createActions(): array {
        $themeData = $this->getThemeData();

        // Preload the theme variables for the frontend.
        return [new ReduxAction(
            \ThemesApiController::GET_THEME_ACTION,
            Data::box($themeData),
            [ 'key' => $themeData ]
        )];
    }

    /** @var string */
    private $inlineStyles = '';

    /**
     * Get an inline style tag for the header and footer.
     */
    private function getThemeInlineCss() {
        if (!$this->inlineStyles) {
            $themeKey = $this->siteMeta->getActiveTheme()->getKey();
            $styleSheet = $themeData['assets']['styles'] ?? null;
            if ($styleSheet) {
                $style = $this->themesApi->get_assets($themeKey, 'styles.css');
                $this->inlineStyles = '<style>' . $style->getData() . '</style>';
            }
        }

        return $this->inlineStyles;
    }

    /**
     * Get the final HTML of the theme's footer.
     *
     * @return string
     */
    public function getThemeFooterHtml() {
        $themeData = $this->getThemeData();
        return $this->getThemeInlineCss() . ($themeData['assets']['header'] ?? '');
    }

    /**
     * Get the final HTML of the theme's header.
     *
     * @return string
     */
    public function getThemeHeaderHtml() {
        $themeData = $this->getThemeData();
        return $this->getThemeInlineCss() . ($themeData['assets']['footer'] ?? '');
    }
}

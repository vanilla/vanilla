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
use Vanilla\Web\JsInterpop\ReduxErrorAction;

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

    /** @var array|null */
    private $themeData;

    /** @var \Throwable */
    private $themeFetchError;

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
        $data = $this->getThemeData();
        if (!$data || !isset($data['assets']['javascript'])) {
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
    public function getThemeData(): ?array {
        if (!$this->themeData) {
            $theme = $this->siteMeta->getActiveTheme();
            if ($theme === null) {
                $this->themeData = null;
                return null;
            }
            $themeKey = $this->siteMeta->getActiveTheme()->getKey();
            try {
                $this->themeData = $this->themesApi->get($themeKey);
            } catch (\Throwable $e) {
                // Prevent infinite loops.
                // Our error handling page uses the theme when possible.
                // As a result we absolutely CANNOT ever allow the this function to bubble up an error.
                // If it did then we we get cascading OOM errors.
                trigger_error("Could not load data for theme key $themeKey.");
                $this->themeFetchError = $e;
                $this->themeData = null;
            }
        }

        return $this->themeData;
    }

    /**
     * @return array
     */
    public function createActions(): array {
        $themeData = $this->getThemeData();
        if (!$themeData) {
            if ($this->themeFetchError) {
                return [
                    new ReduxErrorAction($this->themeFetchError),
                ];
            } else {
                return [];
            }
        }

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
    private function getThemeInlineCss(): string {
        if (!$this->inlineStyles) {
            $themeData = $this->getThemeData();
            if (!$themeData) {
                return '';
            }
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
    public function getThemeFooterHtml(): string {
        $themeData = $this->getThemeData();
        if (!$themeData) {
            return '';
        }
        return $this->getThemeInlineCss() . ($themeData['assets']['footer'] ?? '');
    }

    /**
     * Get the final HTML of the theme's header.
     *
     * @return string
     */
    public function getThemeHeaderHtml(): string {
        $themeData = $this->getThemeData();
        if (!$themeData) {
            return '';
        }
        return $this->getThemeInlineCss() . ($themeData['assets']['header'] ?? '');
    }
}

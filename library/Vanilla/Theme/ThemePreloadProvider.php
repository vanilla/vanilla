<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Models\SiteMeta;
use Vanilla\Theme\Asset\HtmlThemeAsset;
use Vanilla\Theme\Asset\JavascriptThemeAsset;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Asset\TwigThemeAsset;
use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Web\Asset\ExternalAsset;
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

    /** @var ThemeService */
    private $themeService;

    /** @var AssetPreloadModel */
    private $assetPreloader;

    /** @var Theme */
    private $theme;

    /** @var \Throwable */
    private $themeFetchError;

    /** @var string|int */
    private $forcedThemeKey;

    /** @var string|null */
    private $revisionID;

    /**
     * DI.
     *
     * @param SiteMeta $siteMeta
     * @param \ThemesApiController $themesApi
     * @param ThemeService $themeService
     * @param AssetPreloadModel $assetPreloader
     */
    public function __construct(
        SiteMeta $siteMeta,
        \ThemesApiController $themesApi,
        ThemeService $themeService,
        AssetPreloadModel $assetPreloader
    ) {
        $this->siteMeta = $siteMeta;
        $this->themesApi = $themesApi;
        $this->assetPreloader = $assetPreloader;
        $this->themeService = $themeService;
    }

    /**
     * @param int|string $forcedThemeKey
     */
    public function setForcedThemeKey($forcedThemeKey): void {
        $this->forcedThemeKey = $forcedThemeKey;
    }

    /**
     * @param int|null $revisionID
     */
    public function setForcedRevisionID(?int $revisionID = null): void {
        $this->revisionID = $revisionID;
    }

    /**
     * @return string|int
     */
    private function getThemeKeyToPreload() {
        return $this->forcedThemeKey ?: $this->siteMeta->getActiveThemeKey();
    }

    /**
     * @return int
     */
    private function getThemeRevisionID(): ?int {
        return $this->revisionID ?? $this->siteMeta->getActiveThemeRevisionID();
    }

    /**
     * Get a script asset for the theme.
     * If the theme doesn't define a script asset, return null.
     *
     * @return ThemeScriptAsset|null
     */
    public function getThemeScript(): ?ThemeScriptAsset {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return null;
        }

        $script = $theme->getAsset(ThemeAssetFactory::ASSET_JAVASCRIPT);
        if (!($script instanceof JavascriptThemeAsset) || !$script->__toString()) {
            return null;
        }

        return new ThemeScriptAsset($script);
    }

    /**
     * Get the theme (with some local caching so it isn't requested twice).
     */
    public function getPreloadTheme(): ?Theme {
        if (!$this->theme) {
            $this->loadData();
        }

        return $this->theme;
    }

    /**
     * Load data from the theme API.
     */
    private function loadData() {
        $themeKey = $this->getThemeKeyToPreload();

        // Forced theme keys disable addon variables.
        $args = [
            'allowAddonVariables' => !$this->forcedThemeKey,
            'expand' => ['fonts.data', 'variables.data']
        ];
        if (!empty($this->revisionID)) {
            // when theme-settings/{id}/revisions preview
            $args['revisionID'] = $this->revisionID;
        } elseif (!$this->forcedThemeKey && !empty($revisionID = $this->siteMeta->getActiveThemeRevisionID())) {
            $args['revisionID'] = $revisionID;
        }

        try {
            $response = $this->themesApi->get(
                $themeKey,
                $args
            );
            $this->theme = $response->getMeta('theme');
        } catch (\Throwable $e) {
            // Prevent infinite loops.
            // Our error handling page uses the theme when possible.
            // As a result we absolutely CANNOT ever allow the this function to bubble up an error.
            // If it did then we we get cascading OOM errors.
            trigger_error("Could not load data for theme key $themeKey.");
            $this->themeFetchError = $e;
            $this->theme = null;
        }
    }

    /**
     * @return array
     */
    public function createActions(): array {
        $data = $this->getPreloadTheme();
        if (!$data) {
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
            new Data($data),
            [ 'key' => $data ]
        )];
    }

    /** @var string */
    private $inlineStyles = '';

    /**
     * Get an inline style tag for the header and footer.
     */
    private function getThemeInlineCss(): string {
        if (!$this->inlineStyles) {
            $theme = $this->getPreloadTheme();
            if (!$theme) {
                return '';
            }
            $styles = $theme->getAssets()[ThemeAssetFactory::ASSET_STYLES] ?? null;
            if ($styles) {
                $this->inlineStyles = '<style>' . $styles->__toString() . '</style>';
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
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return '';
        }

        $footer = $theme->getAssets()[ThemeAssetFactory::ASSET_FOOTER] ?? null;
        return $this->renderAsset($footer);
    }

    /**
     * Get the final HTML of the theme's header.
     *
     * @return string
     */
    public function getThemeHeaderHtml(): string {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return '';
        }
        $header = $theme->getAssets()[ThemeAssetFactory::ASSET_HEADER] ?? null;
        $variables = $theme->getAssets()[ThemeAssetFactory::ASSET_VARIABLES] ?? null;
        if ($variables instanceof JsonThemeAsset) {
            $bgImage = $variables->get('titleBar.colors.bgImage', null);
            if ($bgImage !== null) {
                $asset = new ExternalAsset($bgImage);
                $preloader = new AssetPreloader($asset, AssetPreloader::REL_PRELOAD, AssetPreloader::AS_IMAGE);
                $this->assetPreloader->addPreload($preloader);
            }
        }

        return $this->renderAsset($header);
    }


    /**
     * Render a theme asset for the header or footer.
     *
     * @param ThemeAsset|null $themeAsset
     *
     * @return string
     */
    private function renderAsset(?ThemeAsset $themeAsset): string {
        $styles = $this->getThemeInlineCss();
        if ($themeAsset instanceof HtmlThemeAsset) {
            return $styles . $themeAsset->renderHtml();
        } elseif ($themeAsset instanceof TwigThemeAsset) {
            return $styles . $themeAsset->renderHtml([]);
        } else {
            return '';
        }
    }
}

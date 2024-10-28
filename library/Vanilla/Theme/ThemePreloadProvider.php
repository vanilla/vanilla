<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\Error\Warning;
use Vanilla\Addon;
use Vanilla\Models\SiteMeta;
use Vanilla\Theme\Asset\HtmlThemeAsset;
use Vanilla\Theme\Asset\JavascriptThemeAsset;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Asset\ThemeAsset;
use Vanilla\Theme\Asset\TwigThemeAsset;
use Vanilla\Web\Asset\AssetPreloader;
use Vanilla\Web\Asset\AssetPreloadModel;
use Vanilla\Web\Asset\DeploymentCacheBuster;
use Vanilla\Web\Asset\WebAsset;
use Vanilla\Web\Asset\ThemeScriptAsset;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Web\JsInterpop\ReduxActionProviderInterface;
use Vanilla\Web\JsInterpop\ReduxErrorAction;

/**
 * Class for preloading theme data into the frontend.
 */
class ThemePreloadProvider implements ReduxActionProviderInterface
{
    /** @var Theme */
    private $theme;

    /** @var Addon */
    private $themeAddon;

    /** @var \Throwable */
    private $themeFetchError;

    /** @var string|int */
    private $forcedThemeKey;

    /** @var string|null */
    private $revisionID;

    /**
     * DI.
     */
    public function __construct(
        private \ThemesApiController $themesApi,
        private ThemeService $themeService,
        private AssetPreloadModel $assetPreloader
    ) {
    }

    private function clearLocaleCaches(): void
    {
        $this->theme = null;
        $this->inlineStyles = "";
    }

    /**
     * @param int|string $forcedThemeKey
     */
    public function setForcedThemeKey($forcedThemeKey): void
    {
        $this->clearLocaleCaches();
        $this->forcedThemeKey = $forcedThemeKey;
    }

    /**
     * @param int|null $revisionID
     */
    public function setForcedRevisionID(?int $revisionID = null): void
    {
        $this->clearLocaleCaches();
        $this->revisionID = $revisionID;
    }

    /**
     * Get a script asset for the theme.
     * If the theme doesn't define a script asset, return null.
     *
     * @return ThemeScriptAsset|null
     */
    public function getThemeScript(): ?ThemeScriptAsset
    {
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
    public function getPreloadTheme(): ?Theme
    {
        if (!$this->theme) {
            $this->loadData();
        }

        return $this->theme;
    }

    /**
     * Get the theme variables.
     *
     * @return array
     */
    public function getVariables(): array
    {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return [];
        }

        $variables = $theme->getVariables()->getValue();
        return $variables;
    }

    /**
     * Get the font variables (deprecated).
     *
     * @return array
     */
    public function getFontsJson(): array
    {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return [];
        }

        $fonts = $theme->getAsset("fonts");
        if (empty($fonts)) {
            return [];
        }

        return $fonts->getValue();
    }

    /**
     * Load data from the theme API.
     */
    private function loadData()
    {
        $currentTheme = $this->themeService->getCurrentTheme();
        $themeKey = $this->forcedThemeKey ?: $currentTheme->getThemeID();

        // Forced theme keys disable addon variables.
        $args = [
            "allowAddonVariables" => !$this->forcedThemeKey,
            "expand" => ["fonts.data", "variables.data"],
        ];
        if (!empty($this->revisionID)) {
            // when theme-settings/{id}/revisions preview
            $args["revisionID"] = $this->revisionID;
        } elseif (!$this->forcedThemeKey && !empty(($revisionID = $currentTheme->getRevisionID()))) {
            $args["revisionID"] = $revisionID;
        }

        try {
            $response = $this->themesApi->get($themeKey, $args);
            $this->theme = $response->getMeta("theme");
        } catch (\Throwable $e) {
            if ($e instanceof Warning || $e instanceof Notice) {
                // Throw PHPUnit warnings and notices back up.
                throw $e;
            }
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
    public function createActions(): array
    {
        $data = $this->getPreloadTheme();
        if (!$data) {
            if ($this->themeFetchError) {
                return [new ReduxErrorAction($this->themeFetchError)];
            } else {
                return [];
            }
        }

        // Preload the theme variables for the frontend.
        return [new ReduxAction(\ThemesApiController::GET_THEME_ACTION, new Data($data), ["key" => $data])];
    }

    /** @var string */
    private $inlineStyles = "";

    /**
     * Get an inline style tag for the header and footer.
     */
    private function getThemeInlineCss(): string
    {
        if (!$this->inlineStyles) {
            $theme = $this->getPreloadTheme();
            if (!$theme) {
                return "";
            }
            $styles = $theme->getAssets()[ThemeAssetFactory::ASSET_STYLES] ?? null;
            $isMinificationEnabled = Gdn::config("minify.styles", true);
            if ($styles) {
                $styleString = $styles->__toString();
                if ($isMinificationEnabled) {
                    $cssMinifier = new \MatthiasMullie\Minify\CSS($styleString);
                    $styleString = $cssMinifier->minify();
                }
                $this->inlineStyles = "<style>" . $styleString . "</style>";
            }
        }

        return $this->inlineStyles;
    }

    /**
     * Get the final HTML of the theme's footer.
     *
     * @return string
     */
    public function getThemeFooterHtml(): string
    {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return "";
        }

        $footer = $theme->getAssets()[ThemeAssetFactory::ASSET_FOOTER] ?? null;
        return $this->renderAsset($footer);
    }

    /**
     * Get the final HTML of the theme's header.
     *
     * @return string
     */
    public function getThemeHeaderHtml(): string
    {
        $theme = $this->getPreloadTheme();
        if (!$theme) {
            return "";
        }
        $header = $theme->getAssets()[ThemeAssetFactory::ASSET_HEADER] ?? null;
        $variables = $theme->getAssets()[ThemeAssetFactory::ASSET_VARIABLES] ?? null;
        if ($variables instanceof JsonThemeAsset) {
            $bgImage = $variables->get("titleBar.colors.bgImage", null);
            if ($bgImage !== null) {
                $asset = new WebAsset($bgImage);
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
    private function renderAsset(?ThemeAsset $themeAsset): string
    {
        $styles = $this->getThemeInlineCss();
        if ($themeAsset instanceof HtmlThemeAsset) {
            return $styles . $themeAsset->renderHtml();
        } elseif ($themeAsset instanceof TwigThemeAsset) {
            return $styles . $themeAsset->renderHtml([]);
        } else {
            return "";
        }
    }
}

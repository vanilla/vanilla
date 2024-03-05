<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\EventManager;
use Garden\Web\Data;
use Twig\TwigFunction;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\FileUtils;
use Vanilla\Models\SiteMeta;
use Vanilla\Theme\ThemePreloadProvider;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\Events\PageRenderBeforeEvent;

/**
 * Class for mapping data inside of a Gdn_Controller for the twig master view.
 */
class MasterViewRenderer
{
    const MASTER_VIEW_PATH = __DIR__ . "/MasterView.twig";

    const DEFAULT_LAYOUT_NAME = "layout.default.twig";

    const HOME_LAYOUT_NAME = "layout.home.twig";

    const FALLBACK_LAYOUT_PATH = PATH_ADDONS_THEMES . "/theme-foundation/views/" . self::DEFAULT_LAYOUT_NAME;

    use TwigRenderTrait;

    /** @var ThemePreloadProvider */
    private $themePreloader;

    /** @var SiteMeta */
    private $siteMeta;

    /** @var ConfigurationInterface */
    private $config;

    /** @var EventManager */
    private $eventManager;

    /**
     * DI.
     *
     * @param ThemePreloadProvider $themePreloader
     * @param SiteMeta $siteMeta
     * @param ConfigurationInterface $config
     * @param EventManager $eventManager
     */
    public function __construct(
        ThemePreloadProvider $themePreloader,
        SiteMeta $siteMeta,
        ConfigurationInterface $config,
        EventManager $eventManager
    ) {
        $this->themePreloader = $themePreloader;
        $this->siteMeta = $siteMeta;
        $this->config = $config;
        $this->eventManager = $eventManager;
    }

    /**
     * Render a master template using a `Page` instance.
     *
     * @param Page $page
     * @param array $viewData
     *
     * @return string
     */
    public function renderPage(Page $page, array $viewData, $masterViewPath = self::MASTER_VIEW_PATH): string
    {
        $head = $page->getHead();

        foreach ($this->getFontCssUrls() as $fontCssUrl) {
            $inlineContent = $head->getInlineStyleFromUrl($fontCssUrl);
            if ($inlineContent !== null) {
                $head->addInlineStyles($inlineContent);
            } else {
                $head->addLinkTag([
                    "rel" => "stylesheet",
                    "type" => "text/css",
                    "href" => $fontCssUrl,
                ]);
            }
        }

        $this->eventManager->fire("pageRenderBefore", new PageRenderBeforeEvent($head, $page));

        $extraData = [
            "seoContent" => new \Twig\Markup($page->getSeoContent(), "utf-8"),
            "cssClasses" => ["isLoading"],
            "pageHead" => $page->getHead()->renderHtml(),
            "title" => $page->getSeoTitle(),
        ];
        $data = array_merge($this->getSharedData(), $extraData, $viewData);
        $data["cssClasses"] = implode(" ", $data["cssClasses"]);

        return $this->renderTwig($masterViewPath, $data);
    }

    /**
     * Render the master view template for a `Controller` instance.
     *
     * @param \Gdn_Controller $controller
     * @return string
     */
    public function renderGdnController(\Gdn_Controller $controller): string
    {
        $data = array_merge($controller->Data, $this->getSharedData());

        $data["title"] = $data["Title"] ?? $this->siteMeta->getSiteTitle();

        $bodyHtmlKey = $controller->getIsReactView() ? "seoContent" : "bodyContent";

        foreach ($this->getFontCssUrls() as $fontCssUrl) {
            $modernPageHead = \Gdn::getContainer()->get(PageHead::class);
            $inlineContent = $modernPageHead->getInlineStyleFromUrl($fontCssUrl);
            if ($inlineContent !== null) {
                $controller->Head->addString("<style>{$inlineContent}</style>");
            } else {
                $controller->Head->addCss($fontCssUrl);
            }
        }

        $extraData = [
            $bodyHtmlKey => $this->renderThemeContentView($data) ?? $controller->renderAssetForTwig("Content"),
            "cssClasses" =>
                $controller->data("CssClass") .
                " isLoading" .
                (\Gdn::themeFeatures()->useDataDrivenTheme() ? " dataDriven" : ""),
            "pageHead" => $controller->renderAssetForTwig("Head"),
        ];

        $data = array_merge($data, $extraData);

        return $this->renderTwig(self::MASTER_VIEW_PATH, $data);
    }

    /**
     * Get the defined body view for a Gdn_Controller.
     *
     * This can be defined by a theme or come from an asset.
     *
     * @param array $data Data to render the view with.
     *
     * @return \Twig\Markup
     */
    private function renderThemeContentView(array $data): ?\Twig\Markup
    {
        $template = null;

        $themeViewPath = $this->siteMeta->getActiveThemeViewPath();
        if ($themeViewPath) {
            $defaultLayout = $themeViewPath . self::DEFAULT_LAYOUT_NAME;
            $homeLayout = $themeViewPath . self::HOME_LAYOUT_NAME;

            if (($data["isHomepage"] ?? false) && file_exists($homeLayout)) {
                $template = $homeLayout;
            } elseif (file_exists($defaultLayout)) {
                $template = $defaultLayout;
            } else {
                $template = self::FALLBACK_LAYOUT_PATH;
            }
        }

        if ($template) {
            return new \Twig\Markup($this->renderTwig($template, $data), "utf-8");
        } else {
            return null;
        }
    }

    /**
     * Get view data that is shared common between rendering styles.
     *
     * @return array
     */
    private function getSharedData(): array
    {
        $themeVariables = $this->themePreloader->getPreloadTheme()->getVariables();
        return [
            "theme" => $themeVariables,
            "siteMeta" => $this->siteMeta->value(),
            "locale" => $this->siteMeta->getLocaleKey(),
            "debug" => $this->siteMeta->getDebugModeEnabled(),
            "favIcon" => $this->siteMeta->getFavIcon(),
            "themeHeader" => new \Twig\Markup($this->themePreloader->getThemeHeaderHtml(), "utf-8"),
            "themeFooter" => new \Twig\Markup($this->themePreloader->getThemeFooterHtml(), "utf-8"),
            "homePageTitle" => $this->config->get("Garden.HomepageTitle", ""),
            "isDirectionRTL" => $this->siteMeta->getDirectionRTL(),
        ];
    }

    /**
     * @return string[]
     */
    private function getFontCssUrls(): array
    {
        $fontsAssets = $this->themePreloader->getFontsJson();
        $fontsAssetUrls = array_column($fontsAssets, "url");

        $variables = $this->themePreloader->getVariables();
        $fontVars = $variables["global"]["fonts"] ?? [];

        $customFontUrl = $fontVars["customFont"]["url"] ?? ($fontVars["customFontUrl"] ?? null);
        $forceGoogleFont = $fontVars["forceGoogleFont"] ?? false;
        $googleFont = $fontVars["googleFontFamily"] ?? "Open Sans";
        $googleFontUrl = asset("/resources/fonts/" . rawurlencode($googleFont) . "/font.css", true);

        if ($forceGoogleFont) {
            return [$googleFontUrl];
        } elseif (!empty($customFontUrl)) {
            // We have a custom font to load.
            return [$customFontUrl];
        } elseif (!empty($fontsAssetUrls)) {
            return $fontsAssetUrls;
        } else {
            // Default fallback.
            return [$googleFontUrl];
        }
    }
}

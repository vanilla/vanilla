<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\ThemePreloadProvider;

/**
 * Class for mapping data inside of a Gdn_Controller for the twig master view.
 */
class MasterViewRenderer {

    const MASTER_VIEW_PATH = __DIR__ . '/MasterView.twig';

    const DEFAULT_LAYOUT_NAME = 'layout.default.twig';

    const HOME_LAYOUT_NAME = 'layout.home.twig';

    use TwigRenderTrait;

    /** @var ThemePreloadProvider */
    private $themePreloader;

    /** @var SiteMeta */
    private $siteMeta;

    /**
     * DI.
     *
     * @param ThemePreloadProvider $themePreloader
     * @param SiteMeta $siteMeta
     */
    public function __construct(ThemePreloadProvider $themePreloader, SiteMeta $siteMeta) {
        $this->themePreloader = $themePreloader;
        $this->siteMeta = $siteMeta;
    }

    /**
     * Render the master template using a `Page` instance.
     *
     * @param Page $page
     * @param array $viewData
     *
     * @return string
     */
    public function renderPage(Page $page, array $viewData): string {
        $extraData = [
            'seoContent' => new \Twig\Markup($page->getSeoContent(), 'utf-8'),
            'cssClasses' => ['isLoading'],
            'pageHead' => $page->getHead()->renderHtml(),
        ];
        $data = array_merge($viewData, $extraData, $this->getSharedData());

        return $this->renderTwig(self::MASTER_VIEW_PATH, $data);
    }

    /**
     * Render the master view template for a `Controller` instance.
     *
     * @param \Gdn_Controller $controller
     * @return string
     */
    public function renderGdnController(\Gdn_Controller $controller): string {
        $data = array_merge($controller->Data, $this->getSharedData());

        $extraData = [
            'bodyContent' =>
                $this->renderThemeContentView($data)
                ?? $controller->renderAssetForTwig('Content'),
            'cssClasses' => $controller->data('CssClass') . ' isLoading',
            'pageHead' => $controller->renderAssetForTwig('Head'),
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
    private function renderThemeContentView(array $data): ?\Twig\Markup {
        $template = null;

        $theme = $this->siteMeta->getActiveTheme();
        if ($theme) {
            $defaultLayout = PATH_ROOT . $theme->getSubdir() . '/views/' . self::DEFAULT_LAYOUT_NAME;
            $homeLayout = PATH_ROOT . $theme->getSubdir() . '/views/' . self::HOME_LAYOUT_NAME;

            if (file_exists($defaultLayout)) {
                $template = $defaultLayout;
            }
        }

        if ($template) {
            return new \Twig\Markup($this->renderTwig($template, $data), 'utf-8');
        } else {
            return null;
        }
    }

    /**
     * Get view data that is shared common between rendering styles.
     *
     * @return array
     */
    private function getSharedData(): array {
        return [
            'locale' => $this->siteMeta->getLocaleKey(),
            'debug' => $this->siteMeta->getDebugModeEnabled(),
            'favIcon' => $this->siteMeta->getFavIcon(),
            'themeHeader' => new \Twig\Markup($this->themePreloader->getThemeHeaderHtml(), 'utf-8'),
            'themeFooter' => new \Twig\Markup($this->themePreloader->getThemeFooterHtml(), 'utf-8'),
        ];
    }
}

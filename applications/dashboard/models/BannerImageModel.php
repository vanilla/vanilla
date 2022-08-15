<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Gdn;
use Vanilla\AliasLoader;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\TwigStaticRenderer;
use Vanilla\ImageSrcSet\ImageSrcSetService;

/**
 * Banner Image Model.
 *
 * Previously this class was provided by the "Hero Image" plugin, but is now built in.
 */
class BannerImageModel
{
    const DEFAULT_CONFIG_KEY = "Garden.BannerImage";

    /** @var FormatService */
    private $formatService;

    /** @var ImageSrcSetService */
    private $imageSrcSetService;

    /**
     * BannerImageModel constructor.
     *
     * @param FormatService $formatService
     */
    public function __construct(FormatService $formatService)
    {
        $this->formatService = $formatService;
        $this->imageSrcSetService = Gdn::getContainer()->get(ImageSrcSetService::class);
    }

    /**
     * Render the Banner.
     *
     * @param array $props
     *
     * @return \Twig\Markup
     */
    public function renderBanner(array $props = []): \Twig\Markup
    {
        $controller = \Gdn::controller();
        $iconImage = self::getCurrentBannerIconLink();
        $backgroundImage = self::getCurrentBannerImageLink();
        $defaultProps = [
            "description" => $controller->contextualDescription(),
            "backgroundImage" => $backgroundImage,
            "iconImage" => $iconImage,
            "iconUrlSrcSet" => $this->imageSrcSetService->getResizedSrcSet($iconImage),
            "backgroundUrlSrcSet" => $this->imageSrcSetService->getResizedSrcSet($backgroundImage),
        ];
        $title = $controller->contextualTitle();

        if ($title) {
            $defaultProps["title"] = $this->formatService->renderPlainText($title, HtmlFormat::FORMAT_KEY);
        }

        //sanitize description before passing on to the component
        /** @var $htmlSanitizer */
        $htmlSanitizer = \Gdn::getContainer()->get(\Vanilla\Formatting\Html\HtmlSanitizer::class);
        $defaultProps["description"] = $htmlSanitizer->filter($defaultProps["description"]);

        $props = array_merge($defaultProps, $props);
        $html = "";
        $isRendered = false;
        $contents = "<div style=\"min-height:'500px'\"></div>";
        if (inSection(c("Theme.Banner.VisibleSections")) || $controller->data("isHomepage")) {
            $isRendered = true;
            $html = TwigStaticRenderer::renderReactModule("community-banner", $props, "", $contents);
        } elseif (inSection(c("Theme.ContentBanner.VisibleSections"))) {
            $isRendered = true;
            $html = TwigStaticRenderer::renderReactModule("community-content-banner", $props, "", $contents);
        }

        if ($isRendered) {
            /** @var \Garden\EventManager $eventManager */
            $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
            $afterBanner = $eventManager->fire("AfterBanner", $this);
            if (!empty($afterBanner)) {
                $html .= implode("", $afterBanner);
            }
        }

        return new \Twig\Markup($html, "utf-8");
    }

    /**
     * Get the slug of the banner image for a given category
     *
     * Categories will inherit their parents CategoryBanner if they don't have
     * their own set. If no BannerImage can be found the default from the config will be returned
     *
     * @param mixed $categoryID Set an explicit category.
     *
     * @return string The category's slug on success, the default otherwise.
     */
    public static function getBannerImageSlug($categoryID)
    {
        return self::getCategoryField($categoryID, "BannerImage", c(self::DEFAULT_CONFIG_KEY));
    }

    /**
     * Get a category image field recursively.
     *
     * @param mixed $categoryID
     * @param string $field
     * @param ?string $default
     *
     * @return mixed
     */
    private static function getCategoryField($categoryID, string $field, $default = null)
    {
        if (!class_exists(\CategoryModel::class)) {
            return $default;
        }
        /** @var \CategoryModel $categoryModel */
        $categoryModel = \Gdn::getContainer()->get(\CategoryModel::class);
        return $categoryModel->getCategoryFieldRecursive($categoryID, $field, $default);
    }

    /**
     * Get a fully qualified BannerImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     */
    public static function getCurrentBannerImageLink(): string
    {
        $controller = \Gdn::controller();
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = Gdn::getContainer()->get(SiteSectionModel::class);
        $currentSection = $siteSectionModel->getCurrentSiteSection();
        $siteSectionBanner = $currentSection->getBannerImageLink();
        $siteSectionCategory = $currentSection->getCategoryID();
        // Use category ID from site section when there is no Category ID or ContextualCategoryID in the controller.
        $categoryID = $controller
            ? $controller->data("Category.CategoryID", $controller->data("ContextualCategoryID", $siteSectionCategory))
            : $siteSectionCategory;
        $defaultBanner = $siteSectionBanner ?: Gdn::config(BannerImageModel::DEFAULT_CONFIG_KEY);
        $field = $categoryID ? self::getCategoryField($categoryID, "BannerImage", $defaultBanner) : $defaultBanner;
        return $field ? \Gdn_Upload::url($field) : $field;
    }

    /**
     * Get a fully qualified BannerImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     */
    public static function getCurrentBannerIconLink(): ?string
    {
        $controller = \Gdn::controller();
        $categoryID = $controller
            ? $controller->data("Category.CategoryID", $controller->data("ContextualCategoryID"))
            : null;
        $field = self::getCategoryField($categoryID, "Photo", null);
        return $field ? \Gdn_Upload::url($field) : $field;
    }

    /**
     * Old name banner image method.
     *
     * @inheritdoc
     * @deprecated 4.0 getBannerImageSlug
     */
    public static function getHeroImageSlug($categoryID)
    {
        deprecated(__FUNCTION__, "getBannerImageSlug");
        return self::getBannerImageSlug($categoryID);
    }

    /**
     * Get a fully qualified HeroImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     * @deprecated 4.0 getCurrentBannerImageLink
     */
    public static function getCurrentHeroImageLink()
    {
        deprecated(__FUNCTION__, "getCurrentBannerImageLink");
        return self::getCurrentBannerImageLink();
    }
}

// Create aliases for backwards compatibility.
AliasLoader::createAliases(BannerImageModel::class);

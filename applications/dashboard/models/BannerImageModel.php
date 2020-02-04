<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\AliasLoader;
use Vanilla\Models\SiteMeta;

/**
 * Banner Image Model.
 *
 * Previously this class was provided by the "Hero Image" plugin, but is now built in.
 */
class BannerImageModel {

    const DEFAULT_CONFIG_KEY = "Garden.BannerImage";

    /** @var SiteMeta */
    private $siteMeta;

    /**
     * BannerImageModel constructor.
     *
     * @param SiteMeta $siteMeta
     */
    public function __construct(SiteMeta $siteMeta) { $this->siteMeta = $siteMeta; }

    /**
     * Render the Banner.
     *
     * @param array $props
     *
     * @return \Twig\Markup
     */
    public function renderBanner(array $props = []): \Twig\Markup {
        $controller = \Gdn::controller();
        $defaultProps = [
            'title' => $controller->data(
                'Category.Name',
                $this->siteMeta->getSiteTitle()
            ),
            'description' => $controller->data(
                'Category.Description',
                $controller->description()
            ),
            'backgroundImage' => self::getCurrentBannerImageLink(),
        ];
        $props = array_merge($defaultProps, $props);
        $html = "";
        $propsJson = htmlspecialchars(json_encode($props, JSON_UNESCAPED_UNICODE));
        if (inSection(c("Theme.Banner.VisibleSections"))) {
            $html = "<div data-react='community-banner' data-props='$propsJson'><div style=\"minHeight='500px'\"></div></div>";
        }
        return new \Twig\Markup($html, 'utf-8');
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
    public static function getBannerImageSlug($categoryID) {
        $categoryID = filter_var($categoryID, FILTER_VALIDATE_INT);
        if (!$categoryID || $categoryID < 1 || !class_exists(\CategoryModel::class)) {
            return c(self::DEFAULT_CONFIG_KEY);
        }

        $category = \CategoryModel::instance()->getID($categoryID, DATASET_TYPE_ARRAY);
        $slug = $category['BannerImage'];

        if (!$slug) {
            $parentID = $category['ParentCategoryID'];
            $slug = self::getBannerImageSlug($parentID);
        }
        return $slug;
    }

    /**
     * Get a fully qualified BannerImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     */
    public static function getCurrentBannerImageLink(): string {
        $controller = \Gdn::controller();
        if (!$controller) {
            $imageSlug = self::getBannerImageSlug(null);
        } else {
            $categoryID = $controller->data('Category.CategoryID', $controller->data('ContextualCategoryID'));
            $imageSlug = self::getBannerImageSlug($categoryID);
        }

        return $imageSlug ? \Gdn_Upload::url($imageSlug) : '';
    }

    /**
     * Old name banner image method.
     *
     * @inheritdoc
     * @deprecated 4.0 getBannerImageSlug
     */
    public static function getHeroImageSlug($categoryID) {
        deprecated(__FUNCTION__, 'getBannerImageSlug');
        return self::getBannerImageSlug($categoryID);
    }

    /**
     * Get a fully qualified HeroImage link for the current category.
     *
     * @return string The URL to the category image. Will be empty if no slug could be found.
     * @deprecated 4.0 getCurrentBannerImageLink
     */
    public static function getCurrentHeroImageLink() {
        deprecated(__FUNCTION__, 'getCurrentBannerImageLink');
        return self::getCurrentBannerImageLink();
    }
}

// Create aliases for backwards compatibility.
AliasLoader::createAliases(BannerImageModel::class);

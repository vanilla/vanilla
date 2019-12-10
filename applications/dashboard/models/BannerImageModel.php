<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\AliasLoader;

/**
 * Hero Image Model.
 *
 * Previously this class was provided by the "Hero Image" plugin, but is now built in.
 */
class BannerImageModel {

    const DEFAULT_CONFIG_KEY = "Garden.BannerImage";

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
        if (!$categoryID || $categoryID < 1) {
            return c(self::DEFAULT_CONFIG_KEY);
        }

        $category = \CategoryModel::instance()->getID($categoryID, DATASET_TYPE_ARRAY);
        $slug = $categoryID['BannerImage'];

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
        $categoryID = \Gdn::controller()->data('Category.CategoryID');

        $imageSlug = self::getBannerImageSlug($categoryID);
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

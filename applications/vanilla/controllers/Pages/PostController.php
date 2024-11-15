<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Forum\Controllers\Api\PostTypesApiController;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Web\PageDispatchController;

class PostController extends PageDispatchController
{
    /**
     * D.I.
     *
     * @param PostTypesApiController $postTypesApi
     * @param \CategoryModel $categoryModel
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        private PostTypesApiController $postTypesApi,
        private \CategoryModel $categoryModel,
        private SiteSectionModel $siteSectionModel
    ) {
    }

    /**
     * Handle new post page.
     *
     * @param string $path
     * @param array $query
     * @return Data
     */
    public function index(string $path = "", array $query = []): Data
    {
        [$postTypeID, $categorySlug] = array_filter(explode("/", trim($path, "/")));

        $postType = !empty($postTypeID) ? $this->postTypesApi->get($postTypeID) : [];
        $title = $postType["postButtonLabel"] ?? "New Post";

        $categoryID = $this->categoryModel->ensureCategoryID($categorySlug);
        $siteSection = SiteSectionSchema::toArray($this->siteSectionModel->getCurrentSiteSection());

        $layoutFormAsset = new LayoutQuery(
            "post",
            params: [
                "postTypeID" => $postTypeID,
                "categoryID" => $categoryID,
                "locale" => $siteSection["contentLocale"],
                "siteSectionID" => $siteSection["sectionID"],
            ]
        );

        $page = $this->usePage(LayoutPage::class)
            ->permission(["discussions.add"], $query["categoryID"] ?? null)
            ->setSeoTitle(t($title))
            ->setSeoRequired(false)
            ->preloadLayout($layoutFormAsset)
            ->blockRobots();

        return $page->render();
    }
}

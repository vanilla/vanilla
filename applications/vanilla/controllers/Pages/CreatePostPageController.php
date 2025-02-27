<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Forum\Controllers\Api\PostTypesApiController;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Web\PageDispatchController;

class CreatePostPageController extends PageDispatchController
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
        // For new posts
        [$postTypeID, $categorySlug] = array_filter(explode("/", trim($path, "/")));

        /**
         * This branch is needed to get the categoryID for the discussion being edited so that it
         * can be used for the permission check below.
         *
         * For Edit posts
         */
        $isEdit = str_contains($path, "editdiscussion");
        if ($isEdit) {
            [$_, $discussionID, $draftID] = explode("/", trim($path, "/"));
            $postTypeID = null;
            $categorySlug = null;

            if ($discussionID !== "0") {
                // Look up CategoryID on the discussion
                $internalClient = \Gdn::getContainer()->get(InternalClient::class);
                $discussion = $internalClient->get("discussions/{$discussionID}")->getBody();
                $categoryID = $discussion["categoryID"];
            }
        }

        $postType = !empty($postTypeID) ? $this->postTypesApi->get($postTypeID) : [];
        $title = $postType["postButtonLabel"] ?? "Create Post";

        $categoryID = $categoryID ?? $this->categoryModel->ensureCategoryID($categorySlug);

        $page = $this->usePage(LayoutPage::class)
            ->permission(["discussions.add"], $categoryID)
            ->setSeoTitle(t($title))
            ->setSeoRequired(false)
            ->blockRobots();

        return $page->render();
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Container\NotFoundException;
use Garden\Web\Exception\Pass;
use Vanilla\Forum\Layout\View\DiscussionLayoutView;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout discussion list page
 *
 * {@link DiscussionLayoutView}
 */
class DiscussionPageController extends PageDispatchController
{
    private function filterCommentIDFromPath(string $path): int|null
    {
        preg_match("/\/comment\/(?<recordID>\d+)/", $path, $matches);
        return isset($matches["recordID"]) ? filter_var($matches["recordID"], FILTER_VALIDATE_INT) : null;
    }

    public function index(string $path, array $query = [])
    {
        $page = $this->usePage(LayoutPage::class);
        $page->setSeoRequired(false);

        $discussionID = StringUtils::parseIDFromPath($path, "\/");

        if (!$discussionID) {
            // If the route is a discussion controller action route pass along to the discussion controller
            if (preg_match("/^\/[a-z]+$/", $path)) {
                throw new Pass("Re-route the request");
            }

            //Check if its a comment route
            $commentID = $this->filterCommentIDFromPath($path);
            if (!$commentID) {
                throw new NotFoundException("Comment");
            }

            $page->preloadLayout(
                new LayoutQuery("post", "comment", $commentID, [
                    "commentID" => $commentID,
                    "sort" => $query["sort"] ?? null,
                ])
            );
        } else {
            $pageNumber = StringUtils::parsePageNumberFromPath($path);
            $page->preloadLayout(
                new LayoutQuery("post", "discussion", $discussionID, [
                    "discussionID" => $discussionID,
                    "page" => $pageNumber,
                    "sort" => $query["sort"] ?? null,
                ])
            );
        }

        return $page->render();
    }
}

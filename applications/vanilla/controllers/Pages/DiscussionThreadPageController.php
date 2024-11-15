<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Container\NotFoundException;
use Garden\Web\Exception\Pass;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout discussion list page
 *
 * {@link DiscussionThreadLayoutView}
 */
class DiscussionThreadPageController extends PageDispatchController
{
    private SiteSectionModel $siteSectionModel;

    /**
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(SiteSectionModel $siteSectionModel)
    {
        $this->siteSectionModel = $siteSectionModel;
    }

    private function filterCommentIDFromPath(string $path): int|null
    {
        preg_match("/\/comment\/(?<recordID>\d+)/", $path, $matches);
        return isset($matches["recordID"]) ? filter_var($matches["recordID"], FILTER_VALIDATE_INT) : null;
    }

    public function index(string $path)
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

            $siteSection = SiteSectionSchema::toArray($this->siteSectionModel->getCurrentSiteSection());

            $page->preloadLayout(
                new LayoutQuery("discussionThread", "comment", (string) $commentID, [
                    "commentID" => $commentID,
                    "locale" => $siteSection["contentLocale"],
                    "siteSectionID" => $siteSection["sectionID"],
                ])
            );
        } else {
            $pageNumber = StringUtils::parsePageNumberFromPath($path);
            $siteSection = SiteSectionSchema::toArray($this->siteSectionModel->getCurrentSiteSection());
            $page->preloadLayout(
                new LayoutQuery("discussionThread", "discussion", (string) $discussionID, [
                    "discussionID" => $discussionID,
                    "locale" => $siteSection["contentLocale"],
                    "siteSectionID" => $siteSection["sectionID"],
                    "page" => $pageNumber,
                ])
            );
        }

        return $page->render();
    }
}

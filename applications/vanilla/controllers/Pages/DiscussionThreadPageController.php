<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use CommentModel;
use DiscussionModel;
use Garden\Container\NotFoundException;
use Garden\Web\Exception\ResponseException;
use Garden\Web\Redirect;
use Gdn;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Http\InternalClient;
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

    public function index(string $path)
    {
        $page = $this->usePage(LayoutPage::class);
        $page->setSeoRequired(false);

        $discussionID = StringUtils::parseIDFromPath($path, "\/");

        if (!$discussionID) {
            $matches = [];
            preg_match("/\/comment\/(?<recordID>\d+)/", $path, $matches);
            $commentID = filter_var($matches["recordID"], FILTER_VALIDATE_INT);
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

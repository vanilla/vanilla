<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\Asset\LayoutQuery;
use Vanilla\Layout\LayoutPage;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout discussion list page
 *
 * {@link DiscussionThreadLayoutView}
 */
class DiscussionThreadPageController extends PageDispatchController
{
    private InternalClient $internalClient;
    private SiteSectionModel $siteSectionModel;

    /**
     * @param InternalClient $internalClient
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(InternalClient $internalClient, SiteSectionModel $siteSectionModel)
    {
        $this->internalClient = $internalClient;
        $this->siteSectionModel = $siteSectionModel;
    }

    public function index(string $path)
    {
        $discussionID = StringUtils::parseIDFromPath($path, "\/");
        $pageNumber = StringUtils::parsePageNumberFromPath($path);

        $page = $this->usePage(LayoutPage::class);
        $page->setSeoRequired(false);

        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $siteSectionID = $siteSection->getSectionID();

        if ($discussionID) {
            $page->preloadLayout(
                new LayoutQuery("discussionThread", "discussion", $discussionID, [
                    "locale" => $siteSection->getContentLocale(),
                    "siteSectionID" => $siteSectionID,
                    "discussionID" => $discussionID,
                    "page" => $pageNumber,
                ])
            );
        }
        return $page->render();
    }
}

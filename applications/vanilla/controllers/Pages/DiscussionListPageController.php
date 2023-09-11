<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Layout\View\DiscussionListLayoutView;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Layout\Asset\LayoutFormAsset;
use Vanilla\Layout\LayoutPage;
use Vanilla\Utility\StringUtils;
use Vanilla\Web\PageDispatchController;
use Vanilla\Site\SiteSectionModel;

/**
 * Controller for the custom layout discussion list page
 */
class DiscussionListPageController extends PageDispatchController
{
    private DiscussionListLayoutView $discussionListLayoutView;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * @param DiscussionListLayoutView $discussionListLayoutView
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(DiscussionListLayoutView $discussionListLayoutView, SiteSectionModel $siteSectionModel)
    {
        $this->discussionListLayoutView = $discussionListLayoutView;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Discussion list
     *
     * @param array $query
     * @return Data
     * @throws \Exception Base exception class for all possible exceptions.
     */
    public function index(array $query): Data
    {
        $siteSection = $this->siteSectionModel->getCurrentSiteSection();
        $schema = $this->discussionListLayoutView->getParamInputSchema();
        $query = $schema->validate($query);
        $query["siteSectionID"] = (string) $siteSection->getSectionID();
        $query["locale"] = $siteSection->getContentLocale();
        $layoutFormAsset = new LayoutFormAsset(
            "discussionList",
            "siteSection",
            (string) $siteSection->getSectionID(),
            $query
        );

        return $this->usePage(LayoutPage::class)
            ->permission("discussions.view")
            ->setSeoRequired(false)
            ->setSeoTitle(t("Recent Discussions"))
            ->setSeoDescription(
                \Gdn::formatService()->renderPlainText(c("Garden.Description", ""), HtmlFormat::FORMAT_KEY)
            )
            ->preloadLayout($layoutFormAsset)
            ->render();
    }
}

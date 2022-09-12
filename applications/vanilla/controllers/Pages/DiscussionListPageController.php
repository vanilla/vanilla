<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Pages;

use Garden\Web\Data;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Forum\Layout\View\DiscussionListLayoutView;
use Vanilla\Layout\LayoutPage;
use Vanilla\Web\PageDispatchController;

/**
 * Controller for the custom layout discussion list page
 */
class DiscussionListPageController extends PageDispatchController
{
    /** @var DiscussionListLayoutView */
    private $discussionListLayoutView;

    /**
     * @param DiscussionListLayoutView $discussionListLayoutView
     */
    public function __construct(DiscussionListLayoutView $discussionListLayoutView)
    {
        $this->discussionListLayoutView = $discussionListLayoutView;
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
        $schema = $this->discussionListLayoutView->getParamInputSchema();
        $query = $schema->validate($query);
        $status = $query["status"] ?? null;

        return $this->usePage(LayoutPage::class)
            ->permission("discussions.view")
            ->setSeoRequired(false)
            ->setSeoTitle(t("Recent Discussions"))
            ->setSeoDescription(
                \Gdn::formatService()->renderPlainText(c("Garden.Description", ""), HtmlFormat::FORMAT_KEY)
            )
            ->render();
    }
}

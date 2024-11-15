<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace Vanilla\QnA\Layout\View;

use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Http\InternalClient;
use Vanilla\QnA\Layout\Assets\TabbedCommentListAsset;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Forum\Widgets\DiscussionSuggestionsAsset;

class QuestionThreadLayoutView extends DiscussionThreadLayoutView
{
    public function __construct(
        InternalClient $internalClient,
        \DiscussionModel $discussionModel,
        BreadcrumbModel $breadcrumbModel,
        \Gdn_Request $request,
        SiteSectionModel $siteSectionModel
    ) {
        parent::__construct($internalClient, $discussionModel, $breadcrumbModel, $request, $siteSectionModel);

        $this->registerAssetClass(TabbedCommentListAsset::class);
        $this->registerAssetClass(DiscussionSuggestionsAsset::class);
    }

    /**
     * @inheritDoc
     */
    public function getExpands(): array
    {
        return array_merge(parent::getExpands(), ["acceptedAnswers", "rejectedAnswers"]);
    }

    /**
     * @inheritDoc
     */
    public function getTemplateID(): string
    {
        return "questionThread";
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Question Thread";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "questionThread";
    }
}

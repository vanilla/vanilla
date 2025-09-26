<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace Vanilla\QnA\Layout\View;

use Vanilla\Forum\Layout\View\DiscussionLayoutView;
use Vanilla\Http\InternalClient;
use Vanilla\Models\DiscussionPlaceModel;
use Vanilla\QnA\Layout\Assets\AnswerThreadAsset;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\QnA\Layout\Assets\SuggestedAnswersAsset;
use Vanilla\Site\SiteSectionModel;

class QuestionLayoutView extends DiscussionLayoutView
{
    public function __construct(
        protected InternalClient $internalClient,
        protected \DiscussionModel $discussionModel,
        protected BreadcrumbModel $breadcrumbModel,
        protected \Gdn_Request $request,
        protected SiteSectionModel $siteSectionModel,
        protected \CategoryModel $categoryModel,
        protected DiscussionPlaceModel $discussionPlaceModel
    ) {
        parent::__construct(
            $internalClient,
            $discussionModel,
            $breadcrumbModel,
            $request,
            $siteSectionModel,
            $categoryModel,
            $discussionPlaceModel
        );

        $this->registerAssetClass(AnswerThreadAsset::class);
        $this->registerAssetClass(SuggestedAnswersAsset::class);
    }

    /**
     * @inheritdoc
     */
    public function getExpands(): array
    {
        return array_merge(parent::getExpands(), ["acceptedAnswers", "rejectedAnswers"]);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateID(): string
    {
        return "question";
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Question";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "question";
    }
}

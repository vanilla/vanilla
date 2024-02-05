<?php

namespace Vanilla\QnA\Layout\View;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Http\InternalClient;
use Vanilla\QnA\Layout\Assets\TabbedCommentListAsset;
use Vanilla\Navigation\BreadcrumbModel;

/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

class QuestionThreadLayoutView extends DiscussionThreadLayoutView
{
    public function __construct(
        InternalClient $internalClient,
        ConfigurationInterface $configuration,
        \DiscussionModel $discussionModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        parent::__construct($internalClient, $configuration, $discussionModel, $breadcrumbModel);
        $this->registerAssetClass(TabbedCommentListAsset::class);
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

<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Widgets\DiscussionCommentEditorAsset;
use Vanilla\Forum\Widgets\DiscussionCommentsAsset;
use Vanilla\Forum\Widgets\DiscussionOriginalPostAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Web\PageHeadInterface;

/**
 * Legacy view type for discussion thread.
 */
class DiscussionThreadLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    private \DiscussionModel $discussionModel;
    private InternalClient $internalClient;
    private ConfigurationInterface $configuration;

    /**
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $configuration
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(
        InternalClient $internalClient,
        ConfigurationInterface $configuration,
        \DiscussionModel $discussionModel
    ) {
        $this->internalClient = $internalClient;
        $this->configuration = $configuration;
        $this->discussionModel = $discussionModel;
        $this->registerAssetClass(DiscussionCommentsAsset::class);
        $this->registerAssetClass(DiscussionOriginalPostAsset::class);
        $this->registerAssetClass(DiscussionCommentEditorAsset::class);
    }

    public function getLayoutID(): string
    {
        return "discussionThread";
    }

    public function getParamInputSchema(): Schema
    {
        return Schema::parse(["discussionID:i", "page:i" => ["default" => 1]]);
    }

    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $discussionID = $paramInput["discussionID"];
        $discussion = $this->internalClient->get("/discussions/{$discussionID}")->asData();

        // TODO: Pagination rel tags.

        $result = array_merge($paramInput, [
            "discussion" => $discussion,
        ]);

        return $result;
    }

    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse(["discussion:o"]);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Comments Page";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "discussionThread";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Discussion/Index";
    }
}

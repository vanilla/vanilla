<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Widgets\DiscussionCommentEditorAsset;
use Vanilla\Forum\Widgets\DiscussionCommentsAsset;
use Vanilla\Forum\Widgets\DiscussionOriginalPostAsset;
use Vanilla\Forum\Widgets\DiscussionTagsAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\LegacyLayoutViewInterface;
use Vanilla\Models\DiscussionJsonLD;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\PageHeadInterface;

/**
 * Legacy view type for discussion thread.
 */
class DiscussionThreadLayoutView extends AbstractCustomLayoutView implements LegacyLayoutViewInterface
{
    private \DiscussionModel $discussionModel;
    private InternalClient $internalClient;
    private ConfigurationInterface $configuration;
    private BreadcrumbModel $breadcrumbModel;

    /**
     * @param InternalClient $internalClient
     * @param ConfigurationInterface $configuration
     * @param \DiscussionModel $discussionModel
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(
        InternalClient $internalClient,
        ConfigurationInterface $configuration,
        \DiscussionModel $discussionModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->internalClient = $internalClient;
        $this->configuration = $configuration;
        $this->discussionModel = $discussionModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->registerAssetClass(DiscussionCommentsAsset::class);
        $this->registerAssetClass(DiscussionOriginalPostAsset::class);
        $this->registerAssetClass(DiscussionCommentEditorAsset::class);
        $this->registerAssetClass(DiscussionTagsAsset::class);
    }

    public function getTemplateID(): string
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

        // This api call functions as our permission check.
        $discussion = $this->internalClient->get("/discussions/{$discussionID}?expand=all")->asData();
        $discussionTags = $discussion["tags"];
        $discussionData = $discussion->getData();

        $pageHead->setSeoTitle($discussionData["name"], false);
        $pageHead->setSeoDescription(Gdn::formatService()->renderExcerpt($discussionData["body"], "html"));
        $crumbs = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($discussionData["categoryID"]));
        $pageHead->setSeoBreadcrumbs($crumbs);
        $url = isset($paramInput["page"])
            ? $discussionData["canonicalUrl"] . "/p{$paramInput["page"]}"
            : $discussionData["canonicalUrl"];
        $pageHead->setCanonicalUrl($url);
        $pageHead->addOpenGraphTag("og:url", $url);
        if (isset($discussionData["image"])) {
            $pageHead->addOpenGraphTag("og:image", $discussionData["image"]["url"]);
        }
        $pageHead->addJsonLDItem(new DiscussionJsonLD(ArrayUtils::pascalCase($discussionData), $this->discussionModel));

        $result = array_merge($paramInput, [
            "discussion" => $discussion,
            "tags" => $discussionTags,
        ]);

        return $result;
    }

    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse(["discussion:o", "tags:a"]);
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

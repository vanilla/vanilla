<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Addon;
use Vanilla\Forum\Widgets\CreatePostFormAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\PageHeadInterface;

class CreatePostLayoutView extends AbstractCustomLayoutView
{
    private \DiscussionModel $discussionModel;
    private InternalClient $internalClient;
    private BreadcrumbModel $breadcrumbModel;
    private \Gdn_Request $request;
    private SiteSectionModel $siteSectionModel;

    /**
     * @param InternalClient $internalClient
     * @param \DiscussionModel $discussionModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param \Gdn_Request $request
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        InternalClient $internalClient,
        \DiscussionModel $discussionModel,
        BreadcrumbModel $breadcrumbModel,
        \Gdn_Request $request,
        SiteSectionModel $siteSectionModel
    ) {
        $this->internalClient = $internalClient;
        $this->discussionModel = $discussionModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->request = $request;
        $this->siteSectionModel = $siteSectionModel;
        $this->registerAssetClass(CreatePostFormAsset::class);
    }

    public function getTemplateID(): string
    {
        return "createPost";
    }

    public function getParamInputSchema(): Schema
    {
        return Schema::parse([
            "postTypeID:s?",
            "postType:s?",
            "groupID:s?",
            "categoryID:s?",
            "recordID:s?",
            "parentRecordType:s?",
            "parentRecordID:s?",
        ]);
    }

    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        $record = null;

        if (!empty($paramInput["recordID"])) {
            $record = $this->internalClient->get("/discussions/{$paramInput["recordID"]}/edit")->getBody();
        }

        $resolved = [
            "postType" => $paramInput["postType"] ?? "discussion",
            "recordID" => $paramInput["recordID"],
            "record" => $record,
            "parentRecordType" => "category",
        ];

        return array_merge($paramInput, $resolved);
    }

    /**
     * @inheritDoc
     */
    public function getContexts(array $resolvedParams): array
    {
        return [
            [
                "\$reactComponent" => "DraftContextProvider",
                "\$reactProps" => [
                    "postType" => $resolvedParams["postType"],
                    "recordID" => $resolvedParams["recordID"],
                    "parentRecordType" => "category",
                    "parentRecordID" => $resolvedParams["parentRecordID"],
                ],
            ],
            [
                "\$reactComponent" => "ParentRecordContextProvider",
                "\$reactProps" => [
                    "parentRecordType" => "category",
                    "parentRecordID" => $resolvedParams["parentRecordID"],
                    "recordType" => $resolvedParams["postType"],
                    "recordID" => $resolvedParams["recordID"],
                    "record" => $resolvedParams["record"],
                ],
            ],
        ];
    }

    public function getParamResolvedSchema(): Schema
    {
        return Schema::parse([
            "postTypeID:s?",
            "postType:s?",
            "recordID:s?",
            "record:o?",
            "parentRecordType:s",
            "parentRecordID:s",
            "groupID:s?",
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Create Post";
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return "createPost";
    }

    /**
     * @inheritDoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Post/Discussion";
    }
}

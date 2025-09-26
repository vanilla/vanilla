<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Layout\View;

use Garden\Schema\Schema;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Forum\Widgets\CreatePostFormAsset;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\PageHeadInterface;

class CreatePostLayoutView extends AbstractCustomLayoutView
{
    /**
     * @param InternalClient $internalClient
     */
    public function __construct(private InternalClient $internalClient)
    {
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

            if (strtolower($record["format"]) !== Rich2Format::FORMAT_KEY) {
                // Get the formatted body
                $renderedRecord = $this->internalClient->get("/discussions/{$paramInput["recordID"]}")->getBody();
                $record["body"] = $renderedRecord["body"];
            }
        }

        $resolved = [
            "postType" => $paramInput["postType"] ?? "discussion",
            "recordID" => $paramInput["recordID"],
            "record" => $record,
            "parentRecordType" => $paramInput["parentRecordType"] ?? "category",
        ];

        return array_merge($paramInput, $resolved);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getName(): string
    {
        return "Create Post";
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "createPost";
    }

    /**
     * @inheritdoc
     */
    public function getLegacyType(): string
    {
        return "Vanilla/Post/Discussion";
    }
}

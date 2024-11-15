<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Layout\Widgets;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the DiscussionCommentEditorAsset.
 */
class DiscussionCommentEditorAssetTest extends SiteTestCase
{
    use LayoutTestTrait, CommunityApiTestTrait;

    /**
     * Test hydration of the DiscussionCommentEditorAsset.
     */
    public function testHydrate(): void
    {
        $discussion = $this->createDiscussion();

        $spec = [
            [
                '$hydrate' => "react.asset.comment-editor",
                '$reactTestID' => "defaults",
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "DiscussionCommentEditorAsset",
                '$reactProps' => [
                    "discussionID" => $discussion["discussionID"],
                    "categoryID" => $discussion["categoryID"],
                    "titleType" => "none",
                    "descriptionType" => "none",
                ],
                '$reactTestID' => "defaults",
            ],
        ];

        $this->assertHydratesTo($spec, ["discussionID" => $discussion["discussionID"]], $expected, "discussionThread");
    }

    /**
     * Test hydration of drafts in the DiscussionCommentEditorAsset.
     */
    public function testHydrateWithDraft(): void
    {
        $discussion = $this->createDiscussion();
        $draft = $this->api()
            ->post("/drafts", [
                "recordType" => "comment",
                "parentRecordID" => $discussion["discussionID"],
                "attributes" => [
                    "body" => "Hello world. I am a comment.",
                    "format" => "Markdown",
                ],
            ])
            ->getBody();

        $spec = [
            [
                '$hydrate' => "react.asset.comment-editor",
                '$reactTestID' => "defaults",
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "DiscussionCommentEditorAsset",
                '$reactProps' => [
                    "discussionID" => $discussion["discussionID"],
                    "categoryID" => $discussion["categoryID"],
                    "titleType" => "none",
                    "descriptionType" => "none",
                    "draft" => [
                        "draftID" => $draft["draftID"],
                        "dateUpdated" => $draft["dateUpdated"],
                        "body" => $draft["attributes"]["body"],
                        "format" => "Markdown",
                    ],
                ],
                '$reactTestID' => "defaults",
            ],
        ];

        $this->assertHydratesTo($spec, ["discussionID" => $discussion["discussionID"]], $expected, "discussionThread");
    }
}

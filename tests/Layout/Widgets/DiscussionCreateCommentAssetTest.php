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
 * Tests for the DiscussionCreateCommentAsset.
 */
class DiscussionCreateCommentAssetTest extends SiteTestCase
{
    use LayoutTestTrait, CommunityApiTestTrait;

    /**
     * Test hydration of the DiscussionCreateCommentAsset.
     */
    public function testHydrate(): void
    {
        $discussion = $this->createDiscussion();

        $spec = [
            [
                '$hydrate' => "react.asset.createComment",
                '$reactTestID' => "defaults",
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "CreateCommentAsset",
                '$reactProps' => [
                    "parentRecordType" => "discussion",
                    "parentRecordID" => $discussion["discussionID"],
                    "categoryID" => $discussion["categoryID"],
                    "title" => "Leave a comment",
                ],
                '$reactTestID' => "defaults",
            ],
        ];

        $this->assertHydratesTo($spec, ["discussionID" => $discussion["discussionID"]], $expected, "discussion");
    }
}

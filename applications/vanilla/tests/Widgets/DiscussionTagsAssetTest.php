<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Layout\HydrateAwareTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

class DiscussionTagsAssetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;
    use HydrateAwareTrait;

    /**
     * Test discussion tags are hydrated
     */
    public function testHydrateDiscussionTags()
    {
        // Create some tags
        $testTagOne = $this->createTag(["Name" => "TestTagOne"]);
        $testTagTwo = $this->createTag(["Name" => "TestTagTwo"]);
        $testTagThree = $this->createTag(["Name" => "TestTagThree"]);

        // Add to discussion
        $discussion = $this->createDiscussion();

        $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$testTagOne["tagID"], $testTagTwo["tagID"], $testTagThree["tagID"]],
        ]);

        $spec = [
            '$hydrate' => "react.asset.discussionTagsAsset",
            "title" => "My Discussion Tags",
            "titleType" => "static",
            "descriptionType" => "none",
            '$reactTestID' => "discussionTags",
        ];

        $expectedTags = [];
        foreach ([$testTagOne, $testTagTwo, $testTagThree] as $tag) {
            $expectedTags[] = [
                "tagID" => $tag["tagID"],
                "name" => $tag["name"],
                "urlcode" => $tag["urlcode"],
            ];
        }

        $expected = [
            '$reactComponent' => "DiscussionTagAsset",
            '$reactProps' => [
                "title" => "My Discussion Tags",
                "titleType" => "static",
                "descriptionType" => "none",
                "tags" => $expectedTags,
            ],
            '$reactTestID' => "discussionTags",
        ];

        $this->assertHydratesTo($spec, ["discussionID" => $discussion["discussionID"]], $expected, "discussionThread");
    }
}

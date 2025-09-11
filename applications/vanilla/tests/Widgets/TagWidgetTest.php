<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\CurrentTimeStamp;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test Tag Widget.
 */
class TagWidgetTest extends SiteTestCase
{
    use LayoutTestTrait, CommunityApiTestTrait;

    /**
     * Test that we can hydrate Tag Widget.
     */
    public function testHydrateTagWidget()
    {
        CurrentTimeStamp::mockTime(time());
        //create tags
        $tag1 = $this->createTag(["name" => "tagName_1"]);
        $tag2 = $this->createTag(["name" => "tagName_2"]);
        $tag3 = $this->createTag(["name" => "tagName_3"]);

        //associate to discussion
        $discussion = $this->createDiscussion();
        $this->api()->put("/discussions/{$discussion["discussionID"]}/tags", [
            "tagIDs" => [$tag1["tagID"], $tag2["tagID"], $tag3["tagID"]],
        ]);

        $spec = [
            '$hydrate' => "react.tag",
            "title" => "My Tags",
            "titleType" => "static",
            "descriptionType" => "none",
            '$reactTestID' => "tags",
        ];

        $expectedTags = [];
        foreach ([$tag1, $tag2, $tag3] as $tag) {
            $expectedTags[] = [
                "tagID" => $tag["tagID"],
                "id" => $tag["tagID"],
                "name" => $tag["name"],
                "urlcode" => $tag["urlcode"],
                "parentTagID" => null,
                "countDiscussions" => 1,
                "urlCode" => $tag["urlcode"],
                "url" => url("discussions/tagged/" . $tag["urlcode"], true),
                "type" => "User",
                "dateInserted" => CurrentTimeStamp::getDateTime()->format("c"),
            ];
        }

        $expected = [
            '$reactComponent' => "TagWidget",
            '$reactProps' => [
                "title" => "My Tags",
                "titleType" => "static",
                "descriptionType" => "none",
                "tags" => $expectedTags,
                "limit" => 10,
            ],
            '$reactTestID' => "tags",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My Tags</h2>
    </div>
    <ul class=linkList>
        <li><a href=https://vanilla.test/tagwidgettest/discussions/tagged/{$tag1["urlcode"]}>{$tag1["name"]}</a></li>
        <li><a href=https://vanilla.test/tagwidgettest/discussions/tagged/{$tag2["urlcode"]}>{$tag2["name"]}</a></li>
        <li><a href=https://vanilla.test/tagwidgettest/discussions/tagged/{$tag3["urlcode"]}>{$tag3["name"]}</a></li>
    </ul>
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}

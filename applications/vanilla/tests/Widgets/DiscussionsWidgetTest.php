<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\CurrentTimeStamp;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the discussion list widgets and assets.
 */
class DiscussionsWidgetTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use LayoutTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Discussion");
    }

    /**
     * Test the discussion list widget.
     */
    public function testDiscussionsWidgetHydrate()
    {
        CurrentTimeStamp::mockTime("Dec 1 2020");
        $discussion1 = $this->createDiscussion(["name" => "Disc1"]);
        unset($discussion1["body"]);
        CurrentTimeStamp::mockTime("Dec 2 2020");
        $discussion2 = $this->createDiscussion(["name" => "Disc2"]);
        unset($discussion2["body"]);

        $spec = [
            '$hydrate' => "react.discussion.discussions",
            '$reactTestID' => "discussions",
            "title" => "Recent Discussions",
            "titleType" => "static",
            "descriptionType" => "none",
            "apiParams" => [
                "sort" => "-dateLastComment",
            ],
        ];

        $expected = [
            '$reactComponent' => "DiscussionsWidget",
            '$reactProps' => self::markForSparseComparision([
                "apiParams" => self::markForSparseComparision([
                    "sort" => "-dateLastComment",
                ]),
                "discussions" => [
                    self::markForSparseComparision($discussion2),
                    self::markForSparseComparision($discussion1),
                ],
                "title" => "Recent Discussions",
            ]),
            '$reactTestID' => "discussions",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Recent Discussions</h2>
    </div>
    <ul class=linkList>
        <li><a href={$discussion2["url"]}>Disc2</a><p>Hello Discussion</p></li>
        <li><a href={$discussion1["url"]}>Disc1</a><p>Hello Discussion</p></li>
    </ul>
</div>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }

    /**
     * Test the discussion list asset.
     */
    public function testHydrateDiscussionListAsset()
    {
        CurrentTimeStamp::mockTime("Dec 1 2020");
        $discussion1 = $this->createDiscussion(["name" => "Disc1"]);
        unset($discussion1["body"]);
        CurrentTimeStamp::mockTime("Dec 2 2020");
        $discussion2 = $this->createDiscussion(["name" => "Disc2"]);
        unset($discussion2["body"]);

        $spec = [
            '$hydrate' => "react.asset.discussionList",
            '$reactTestID' => "discussions",
            "title" => "Recent Discussions",
            "titleType" => "static",
            "descriptionType" => "none",
            "apiParams" => [
                "sort" => "-dateLastComment",
            ],
        ];

        $expected = [
            '$reactComponent' => "DiscussionsWidget",
            '$reactProps' => self::markForSparseComparision([
                "apiParams" => self::markForSparseComparision([
                    "sort" => "-dateLastComment",
                ]),
                "isAsset" => true,
                "discussions" => [
                    self::markForSparseComparision($discussion2),
                    self::markForSparseComparision($discussion1),
                ],
                "title" => "Recent Discussions",
            ]),
            '$reactTestID' => "discussions",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Recent Discussions</h2>
    </div>
    <ul class=linkList>
        <li><a href={$discussion2["url"]}>Disc2</a><p>Hello Discussion</p></li>
        <li><a href={$discussion1["url"]}>Disc1</a><p>Hello Discussion</p></li>
    </ul>
</div>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected, "discussionList");
    }
}

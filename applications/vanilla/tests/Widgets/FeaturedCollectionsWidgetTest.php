<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Controllers\Api\CollectionsApiController;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the Featured Collections Widgt.
 */
class FeaturedCollectionsWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;

    /**
     * Test that we can hydrate the Featured Collections Widget
     */
    public function testHydrateFeaturedCollectionsWidget()
    {
        $discussion1 = $this->createDiscussion(["name" => "Discussion 1"]);
        $discussion2 = $this->createDiscussion(["name" => "Discussion 2"]);
        $discussions = [
            [
                "recordID" => $discussion1["discussionID"],
                "recordType" => "discussion",
                "sort" => 0,
            ],
            [
                "recordID" => $discussion2["discussionID"],
                "recordType" => "discussion",
                "sort" => 0,
            ],
        ];

        $collection = $this->createCollection($discussions);

        $apiParams = [
            "collectionID" => $collection["collectionID"],
        ];

        $spec = [
            '$hydrate' => "react.featuredcollections",
            "title" => "Featured Collection",
            "apiParams" => $apiParams,
            '$reactTestID' => "collection",
        ];
        $collectionController = \Gdn::getContainer()->get(CollectionsApiController::class);
        $expectedCollection = $collectionController->get_content($collection["collectionID"], "en");

        $expected = [
            '$reactComponent' => "FeaturedCollectionsWidget",
            '$reactProps' => [
                "title" => "Featured Collection",
                "apiParams" => $apiParams,
                "collection" => $expectedCollection,
            ],
            '$reactTestID' => "collection",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Featured Collection</h2>
    </div>
    <ul class=linkList>
        <li><a href={$discussion1["url"]}>Discussion 1</a><p>Hello Discussion</p></li>
        <li><a href={$discussion2["url"]}>Discussion 2</a><p>Hello Discussion</p></li>
    </ul>
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}

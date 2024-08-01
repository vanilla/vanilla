<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace tests;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Tests for the SplitMerge plugin.
 */
class SplitMergeTest extends AbstractAPIv2Test
{
    use CommunityApiTestTrait;

    protected static $addons = ["splitmerge", "ideation"];

    /**
     * Test that you can move a "redirect" type discussion to an Ideation category.
     *
     * @return void
     */
    public function testMovingRedirectDiscussion()
    {
        $fromCategory = $this->createCategory(["parentCategoryID" => -1, "name" => "Move From"]);
        $discussion = $this->createDiscussion();
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $discussionModel->update(["Type" => "redirect"], ["DiscussionID" => $discussion["discussionID"]]);

        $categoryModel = $this->container()->get(\CategoryModel::class);
        $toIdeaCategoryID = (int) $categoryModel->save([
            "Name" => "Test Idea Category",
            "UrlCode" => "test-idea-category",
            "InsertUserID" => self::$siteInfo["adminUserID"],
            "IdeationType" => "up",
        ]);

        $this->api()->patch("discussions/move", [
            "discussionIDs" => [$discussion["discussionID"]],
            "categoryID" => $toIdeaCategoryID,
        ]);

        $discussion = $this->api()
            ->get("discussions/{$discussion["discussionID"]}")
            ->getBody();
        $this->assertSame($toIdeaCategoryID, $discussion["categoryID"]);
    }
}

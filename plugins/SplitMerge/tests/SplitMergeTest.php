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

    /**
     * Test the legacy merge functionality.
     *
     * @return void
     */
    public function testLegacyMerge()
    {
        $discussion1 = $this->createDiscussion(["body" => "Disc 1"]);
        $discussion2 = $this->createDiscussion(["body" => "Disc 2"]);
        $comment = $this->createComment(["body" => "Comment 1"]);
        $discussion3 = $this->createDiscussion(["body" => "Disc 3"]);

        $this->checkDiscussionLegacy($discussion1["discussionID"]);
        $this->checkDiscussionLegacy($discussion2["discussionID"]);
        $this->checkDiscussionLegacy($discussion3["discussionID"]);

        // Assert that our checks are set
        $this->assertEquals(
            [$discussion1["discussionID"], $discussion2["discussionID"], $discussion3["discussionID"]],
            \Gdn::userModel()->getAttribute(\Gdn::session()->User->UserID, "CheckedDiscussions", [])
        );

        $response = $this->bessy()->postJsonData("/moderation/merge-discussions", [
            "DeliveryMethod" => "JSON",
            "TransientKey" => $this->api()->getTransientKey(),
            "RedirectLink" => true,
            "MergeDiscussionID" => $discussion1["discussionID"],
        ]);

        $this->assertEquals(200, $response->getStatus());

        $this->assertStringStartsWith(
            "Merged:",
            $this->api()
                ->get("/discussions/{$discussion2["discussionID"]}")
                ->getBody()["name"]
        );
        $this->assertStringStartsWith(
            "Merged:",
            $this->api()
                ->get("/discussions/{$discussion3["discussionID"]}")
                ->getBody()["name"]
        );
        $this->assertEquals(
            3,
            $this->api()
                ->get("/discussions/{$discussion1["discussionID"]}")
                ->getBody()["countComments"]
        );
        $this->assertEquals(
            $discussion1["discussionID"],
            $this->api()
                ->get("/comments/{$comment["discussionID"]}")
                ->getBody()["discussionID"]
        );

        // Assert that our checks are cleared
        $this->assertEquals(
            [],
            \Gdn::userModel()->getAttribute(\Gdn::session()->User->UserID, "CheckedDiscussions", [])
        );
    }

    /**
     * @param int $discussionID
     */
    private function checkDiscussionLegacy(int $discussionID)
    {
        $this->bessy()->postJsonData("/moderation/checked-discussions", [
            "CheckIDs" => [["checkId" => $discussionID, "checked" => true]],
            "DeliveryMethod" => "JSON",
            "TransientKey" => $this->api()->getTransientKey(),
        ]);
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\CurrentTimeStamp;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Verify discussion count routines.
 */
class ForumAggregateModelTest extends SiteTestCase
{
    public static $addons = ["ideation"];

    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    use TestCategoryModelTrait;
    use TestDiscussionModelTrait;
    use SchedulerTestTrait;

    /**
     * Verify counts after multiple discussions across different categories have been deleted.
     */
    public function testBulkDelete()
    {
        $partialDeleteCat = $this->createCategory(["name" => "partialDelete"]);
        $keepDisc1 = $this->createDiscussion();
        $this->createComment();
        $deleteDisc0 = $this->createDiscussion();
        $this->createComment();
        $this->createComment();

        $deleteEntirelyCat1 = $this->createCategory(["name" => "deleteEntirely"]);
        $deleteDisc1 = $this->createDiscussion();
        $this->createComment();
        $deleteDisc2 = $this->createDiscussion();
        $this->createComment();

        // Pause so it's like a real bulk operation with the deferred optimizations.
        $this->getScheduler()->pause();
        $response = $this->api()->deleteWithBody("/discussions/list", [
            "discussionIDs" => [
                $deleteDisc0["discussionID"],
                $deleteDisc1["discussionID"],
                $deleteDisc2["discussionID"],
            ],
        ]);
        $this->getScheduler()->resume();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDiscussionCounts($keepDisc1["discussionID"]);
        $this->assertCategoryCounts($partialDeleteCat["categoryID"]);
        $this->assertCategoryCounts($deleteEntirelyCat1["categoryID"]);
    }

    /**
     * Verify counts after using DiscussionModel::save to move discussions between categories.
     */
    public function testMoveUsingSave(): void
    {
        $oldCategory = $this->createCategory();
        $disc1 = $this->createDiscussion();
        $this->createComment();
        $newCategory = $this->createCategory(["parentCategoryID" => -1]);
        $this->createDiscussion();
        $this->createComment();

        $this->discussionModel->save([
            "DiscussionID" => $disc1["discussionID"],
            "CategoryID" => $oldCategory["categoryID"],
        ]);

        $this->assertCategoryCounts($oldCategory["categoryID"]);
        $this->assertCategoryCounts($newCategory["categoryID"]);
    }

    public function testBasicCrudAggregates()
    {
        $parentCategory = $this->createCategory(["name" => "parentCategory"]);
        CurrentTimeStamp::mockTime("2023-01-01");
        $parentDiscussion = $this->createDiscussion(["name" => "parentDiscussion"]);
        $this->createComment();
        $this->createComment();

        $this->assertDiscussionCounts($parentDiscussion["discussionID"]);
        $this->assertCategoryCounts($parentCategory["categoryID"]);

        // Nested propagation should work
        CurrentTimeStamp::mockTime("2023-01-02");
        $nestedCategory = $this->createCategory(["name" => "nestedCategory"]);
        $nestedDiscussion = $this->createDiscussion(["name" => "nestedDiscussion"]);
        CurrentTimeStamp::mockTime("2023-01-03");
        $this->createComment();

        $this->assertDiscussionCounts($nestedDiscussion["discussionID"]);
        $this->assertCategoryCounts($nestedCategory["categoryID"]);
        $this->assertCategoryCounts($parentCategory["categoryID"]);
    }

    /**
     * Test that restoring from the change log affects lastPost.
     *
     * @return void
     */
    public function testDeleteAndRestoreLog()
    {
        $cat1 = $this->createCategory();
        $cat2 = $this->createCategory();
        CurrentTimeStamp::mockTime("2023-01-01");
        $discussion1 = $this->createDiscussion(["name" => "disc1"]);
        // Have to use a recent time for this or the log model will be pruned immediately.
        CurrentTimeStamp::mockTime("now");
        $discussion2 = $this->createDiscussion(["name" => "disc2"]);

        $this->api()->delete("/discussions/{$discussion2["discussionID"]}");
        $this->assertCategoryCounts($cat1["categoryID"]);
        $this->assertLastPostName($cat1["categoryID"], "disc1");

        // Now restore it and counts should be recalculated.
        $this->bessy()->post("/dashboard/log/restore", ["LogIDs" => [$this->getLogIDForRecord($discussion2)]]);
        $this->assertLastPostName($cat1["categoryID"], "disc2");
    }

    /**
     * Test that restoring from the spam queue affects lastPost.
     */
    public function testDeleteAndRestoreSpam()
    {
        $cat1 = $this->createCategory();
        $cat2 = $this->createCategory();
        CurrentTimeStamp::mockTime("2023-01-01");
        $discussion1 = $this->createDiscussion(["name" => "disc1"]);
        // Have to use a recent time for this or the log model will be pruned immediately.

        CurrentTimeStamp::mockTime("now");
        $user2 = $this->createUser();
        $discussion2 = $this->runWithUser(function () {
            return $this->createDiscussion(["name" => "disc2"]);
        }, $user2);

        $this->reactDiscussion($discussion2, "spam");

        // Post was removed as spam because a moderation marked it.
        $this->assertCategoryCounts($cat1["categoryID"]);
        $this->assertLastPostName($cat1["categoryID"], "disc1");

        $this->bessy()->post("/dashboard/log/not-spam", ["LogIDs" => [$this->getLogIDForRecord($discussion2)]]);
        // Now restore it and counts should be recalculated.
        $this->assertCategoryCounts($cat1["categoryID"]);
        $this->assertLastPostName($cat1["categoryID"], "disc2");
    }

    /**
     * Get a log ID for a discussion.
     *
     * @param array $discussion
     * @return int
     */
    private function getLogIDForRecord(array $discussion): int
    {
        $logID =
            \Gdn::database()
                ->createSql()
                ->select("LogID")
                ->from("Log")
                ->where([
                    "RecordType" => "Discussion",
                    "RecordID" => $discussion["discussionID"],
                ])
                ->get()
                ->firstRow(DATASET_TYPE_ARRAY)["LogID"] ?? null;

        if (empty($logID)) {
            $this->fail("Could not find log record for discussion {$discussion["name"]}");
        }
        return $logID;
    }

    /**
     * Assert that a category has a particular last post name.
     *
     * @param int $categoryID
     * @param string $expected
     * @return void
     */
    private function assertLastPostName(int $categoryID, string $expected): void
    {
        $category = $this->api()->get("/categories/{$categoryID}", ["expand" => "lastPost"]);
        $this->assertEquals($expected, $category["lastPost"]["name"]);
    }
}

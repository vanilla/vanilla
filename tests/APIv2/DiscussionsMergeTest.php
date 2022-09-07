<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Vanilla\Community\Events\DiscussionEvent;
use VanillaTests\Analytics\SpyingAnalyticsTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test discussion merging.
 */
class DiscussionsMergeTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;

    /**
     * Test success PATCH /discussions/merge
     */
    public function testSuccessMergeDiscussions(): void
    {
        // Create our records.
        $this->enableCaching();
        $rootCategory = $this->createCategory();
        $category1 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);
        $discussion1 = $this->createDiscussion();
        $comment1_1 = $this->createComment(["body" => "comment1"]);
        $comment1_2 = $this->createComment(["body" => "comment2"]);
        $category2 = $this->createCategory(["parentCategoryID" => $rootCategory["categoryID"]]);
        $discussion2 = $this->createDiscussion(["body" => "discussion2"]);
        $comment2_1 = $this->createComment(["body" => "comment3"]);
        $comment2_2 = $this->createComment(["body" => "comment4"]);
        $discussion3 = $this->createDiscussion(["body" => "discussion3"]);

        $mergedDiscussionIDs = [$discussion2["discussionID"], $discussion3["discussionID"]];

        // Merge the records.
        $response = $this->api()->patch("/discussions/merge", [
            "discussionIDs" => $mergedDiscussionIDs,
            "destinationDiscussionID" => $discussion1["discussionID"],
            "addRedirects" => true,
        ]);
        // Request should be successful.
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEqualsCanonicalizing($mergedDiscussionIDs, $body["progress"]["successIDs"]);

        // Redirects should have been created.
        $sourceDiscussions = $this->api()->get("/discussions", ["discussionID" => $mergedDiscussionIDs]);
        $this->assertRowsLike(
            [
                "type" => ["redirect", "redirect"],
            ],
            $sourceDiscussions->getBody(),
            true,
            2
        );

        // Our comments should have been moved.
        $comments = $this->api()->get("/comments", ["discussionID" => $discussion1["discussionID"]]);
        $this->assertRowsLike(
            [
                "body" => [
                    $comment1_1["body"],
                    $comment1_2["body"],
                    $discussion2["body"],
                    $comment2_1["body"],
                    $comment2_2["body"],
                    $discussion3["body"],
                ],
            ],
            $comments->getBody(),
            false,
            6
        );

        // Make sure counts were adjusted properly.
        $categoryIDs = [$rootCategory["categoryID"], $category2["categoryID"], $category1["categoryID"]];
        $categories = $this->api()
            ->get("/categories", ["categoryID" => $categoryIDs])
            ->getBody();
        $this->assertRowsLike(
            [
                "categoryID" => $categoryIDs,
                "countComments" => [0, 0, 6],
                "countDiscussions" => [0, 2, 1],
            ],
            $categories,
            true,
            3
        );
    }

    /**
     * Test a user trying to merge with missing permissions.
     */
    public function testInvalidPermissions()
    {
        $modID = $this->createUserFixture(VanillaTestCase::ROLE_MOD);
        $permTarget = $this->createDiscussion();
        $permSource = $this->createDiscussion();

        $noPermCategory = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID]);
        $noPermTarget = $this->createDiscussion();
        $noPermSource = $this->createDiscussion();

        $this->runWithUser(function () use ($permTarget, $permSource, $noPermTarget, $noPermSource) {
            $this->runWithExpectedExceptionCode(403, function () use ($permSource, $noPermTarget) {
                $this->api()->patch("/discussions/merge", [
                    "discussionIDs" => [$permSource["discussionID"]],
                    "destinationDiscussionID" => $noPermTarget["discussionID"],
                ]);
            });

            $this->runWithExpectedExceptionCode(403, function () use ($noPermSource, $noPermTarget) {
                $this->api()->patch("/discussions/merge", [
                    "discussionIDs" => [$noPermSource["discussionID"]],
                    "destinationDiscussionID" => $noPermTarget["discussionID"],
                ]);
            });

            $this->runWithExpectedExceptionCode(403, function () use ($noPermSource, $permTarget) {
                $this->api()->patch("/discussions/merge", [
                    "discussionIDs" => [$noPermSource["discussionID"]],
                    "destinationDiscussionID" => $permTarget["discussionID"],
                ]);
            });
        }, $modID);
    }

    /**
     * Check that a not found error is returned if we try to merge non-existing discussions.
     */
    public function testNotFound()
    {
        $this->createDiscussion();
        $this->runWithExpectedExceptionCode(404, function () {
            $this->api()->patch("/discussions/merge", [
                "discussionIDs" => [5000],
                "destinationDiscussionID" => $this->lastInsertedDiscussionID,
            ]);
        });

        $this->runWithExpectedExceptionCode(404, function () {
            $this->api()->patch("/discussions/merge", [
                "discussionIDs" => [$this->lastInsertedDiscussionID],
                "destinationDiscussionID" => 5000,
            ]);
        });
    }

    /**
     * Test that redirect discussions can't be merged.
     */
    public function testMergeRedirects()
    {
        $normalID = $this->createDiscussion()["discussionID"];
        $redirectID = $this->createDiscussion([], ["Type" => "Redirect"])["discussionID"];

        $this->runWithExpectedExceptionCode(400, function () use ($normalID, $redirectID) {
            $this->api()->patch("/discussions/merge", [
                "discussionIDs" => [$normalID],
                "destinationDiscussionID" => $redirectID,
            ]);
        });

        $this->runWithExpectedExceptionCode(400, function () use ($normalID, $redirectID) {
            $this->api()->patch("/discussions/merge", [
                "discussionIDs" => [$redirectID],
                "destinationDiscussionID" => $normalID,
            ]);
        });
    }

    /**
     * Test that we can partially complete a merge.
     *
     * @return mixed
     */
    public function testMergePartial()
    {
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
        $this->getLongRunner()->setMaxIterations(1);

        $discussion1 = $this->createDiscussion();
        $discussion2 = $this->createDiscussion();
        $discussion3 = $this->createDiscussion();

        $response = $this->api()->patch(
            "/discussions/merge",
            [
                "discussionIDs" => [
                    $discussion1["discussionID"],
                    $discussion2["discussionID"],
                    $discussion3["discussionID"],
                ],
                "destinationDiscussionID" => $discussion1["discussionID"],
            ],
            [],
            ["throw" => false]
        );

        $this->assertEquals(408, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertEquals(2, $body["progress"]["countTotalIDs"]);
        $this->assertCount(1, $body["progress"]["successIDs"]);
        $this->assertNotNull($body["callbackPayload"]);
        return $body["callbackPayload"];
    }

    /**
     * Test that we can resume and finish our merge.
     *
     * @param string $callbackPayload
     *
     * @depends testMergePartial
     */
    public function testResumePartial(string $callbackPayload)
    {
        $this->getLongRunner()->reset();
        $response = $this->resumeLongRunner($callbackPayload);
        $this->assertEquals(200, $response->getStatusCode(), "Long runner should complete. " . $response->getRawBody());
        $this->assertEquals(2, $response->getBody()["progress"]["countTotalIDs"]);
        $allDiscussions = $this->api()
            ->get("/discussions")
            ->getBody();
        $this->assertCount(1, $allDiscussions);
        $this->assertEquals(2, $allDiscussions[0]["countComments"]);
    }

    /**
     * Test dispatched discussion resource events upon merge.
     */
    public function testMergeDiscussionsEvent(): void
    {
        $category = $this->createCategory();

        $discussions[] = $this->createDiscussion(["CategoryID" => $category["categoryID"]]);
        $discussions[] = $this->createDiscussion(["CategoryID" => $category["categoryID"]]);
        $discussions[] = $this->createDiscussion(["CategoryID" => $category["categoryID"]]);

        // Merge discussions to another discussion using `merge` API endpoint.
        $result = $this->api()->patch("/discussions/merge", [
            "discussionIDs" => [
                $discussions[0]["discussionID"],
                $discussions[1]["discussionID"],
                $discussions[2]["discussionID"],
            ],
            "destinationDiscussionID" => $discussions[2]["discussionID"],
        ]);

        // Assert that everything went well & the resource event was fired.
        $this->assertEquals(200, $result->getStatusCode());

        $this->assertEventsDispatched(
            [
                $this->expectedResourceEvent("discussion", DiscussionEvent::ACTION_MERGE, $discussions[0]),
                $this->expectedResourceEvent("discussion", DiscussionEvent::ACTION_MERGE, $discussions[1]),
            ],
            ["discussionID"]
        );
    }
}

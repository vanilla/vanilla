<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Events\ResourceEvent;
use Vanilla\Scheduler\Driver\LocalDriverSlip;
use Vanilla\Scheduler\Job\JobExecutionProgress;
use Vanilla\Scheduler\Job\LongRunnerJob;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerMiddleware;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\LogModelTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test deletion of a discussion.
 */
class DiscussionsDeleteTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    use EventSpyTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use LogModelTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->resetTable("Log");
        $this->resetTable("jobStatus");
        $this->enableCaching();
    }

    /**
     * Test that a long-running delete, deletes all items with the same log id.
     */
    public function testLogTransaction()
    {
        // Test that things are logged with same transaction.
        $this->createCategory();
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();
        $comment2 = $this->createComment();
        $comment3 = $this->createComment();

        $allIDs = [$discussion["discussionID"], $comment["commentID"], $comment2["commentID"], $comment3["commentID"]];

        $result = $this->getLongRunner()
            ->setMaxIterations(2)
            ->runImmediately(
                new LongRunnerAction(\DiscussionModel::class, "deleteDiscussionIterator", [
                    $this->lastInsertedDiscussionID,
                ])
            );

        $this->assertNotNull($result->getCallbackPayload());

        $result = $this->getLongRunner()
            ->reset()
            ->runImmediately(
                LongRunnerAction::fromCallbackPayload(
                    $result->getCallbackPayload(),
                    self::container()->getArgs(SystemTokenUtils::class),
                    \Gdn::request()
                )
            );

        $loggedRecords = $this->assertCountLoggedRecords(
            4,
            [
                "RecordID" => $allIDs,
                "Operation" => "Delete",
            ],
            "4 deleted records should have been logged"
        );
        $transactionIDs = array_column($loggedRecords, "TransactionLogID");
        $this->assertCount(1, array_unique($transactionIDs), "All log items should share a transactionID.");
        $this->assertNull($result->getCallbackPayload());
    }

    /**
     * Test that deleting a discussion adjusts aggregates.
     */
    public function testDeleteAdjustsAggregates()
    {
        $cat1 = $this->createCategory();
        $cat1_1 = $this->createCategory();
        $disc1 = $this->createDiscussion();
        $disc2 = $this->createDiscussion();
        $comment = $this->createComment();

        $common = [
            "CountAllDiscussions" => 2,
            "CountAllComments" => 1,
            "LastCommentID" => $comment["commentID"],
            "LastDiscussionID" => $disc2["discussionID"],
        ];
        $this->assertRecordsFound(
            "Category",
            [
                "CategoryID" => $cat1["categoryID"],
                // Direct counts.
                "CountDiscussions" => 0,
                "CountComments" => 0,
            ] + $common
        );

        $this->assertRecordsFound(
            "Category",
            [
                "CategoryID" => $cat1_1["categoryID"],
                // Direct counts.
                "CountDiscussions" => 2,
                "CountComments" => 1,
            ] + $common
        );

        $this->api()->delete("/discussions/{$disc2["discussionID"]}");
        $common = [
            "CountAllDiscussions" => 1,
            "CountAllComments" => 0,
            "LastCommentID" => null,
            "LastDiscussionID" => $disc1["discussionID"],
        ];
        $this->assertRecordsFound(
            "Category",
            [
                "CategoryID" => $cat1["categoryID"],
                "CountDiscussions" => 0,
                "CountComments" => 0,
            ] + $common
        );

        $this->assertRecordsFound(
            "Category",
            [
                "CategoryID" => $cat1_1["categoryID"],
                "CountDiscussions" => 1,
                "CountComments" => 0,
            ] + $common
        );
    }

    /**
     * Test that deleting a discussion updates bookmarked counts.
     */
    public function testDeleteAdjustsBookmarkCounts()
    {
        $discussion = $this->createDiscussion();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $this->runWithUser([$this, "bookmarkDiscussion"], $user1);
        $this->runWithUser([$this, "bookmarkDiscussion"], $user2);
        $this->assertUserField("CountBookmarks", 1, $user1["userID"]);
        $this->assertUserField("CountBookmarks", 1, $user2["userID"]);

        $response = $this->api()->delete("/discussions/" . $discussion["discussionID"]);
        $this->assertEquals(204, $response->getStatusCode());

        $this->assertNoRecordsFound("UserDiscussion", ["UserID" => [$user1["userID"], $user2["userID"]]]);
        $this->assertUserField("CountBookmarks", 0, $user1["userID"]);
        $this->assertUserField("CountBookmarks", 0, $user2["userID"]);
    }

    /**
     * Test that deleting a discussion deletes all comments and logs them with the correct transactionID.
     */
    public function testResourceEvents()
    {
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment2 = $this->createComment();
        $comment3 = $this->createComment();

        $this->api()->delete("/discussions/{$discussion["discussionID"]}");

        // Comment count
        $this->assertCount(
            0,
            $this->api()
                ->get("/comments", ["insertUserID" => $this->api()->getUserID()])
                ->getBody()
        );
        $this->assertEventsDispatched(
            [
                $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment1),
                $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment2),
                $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment3),
                $this->expectedResourceEvent("discussion", ResourceEvent::ACTION_DELETE, $discussion),
            ],
            ["commentID", "discussionID", "name"]
        );
    }

    /**
     * Assert that discussions are deleted immediately.
     */
    public function testSimpleDelete()
    {
        $discussion = $this->createDiscussion();
        $this->resetTable("jobStatus");
        $response = $this->api()->delete("/discussions/{$discussion["discussionID"]}");
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertTrackedJobCount(0, []);
    }

    /**
     * Test behaviour when multiple jobs are queued.
     */
    public function testQueuedDelete()
    {
        $scheduler = $this->getScheduler();

        // Create a discussion with comments.
        $discussion = $this->createDiscussion();
        $this->createComment();

        $this->resetTable("jobStatus");

        $where = ["DiscussionID" => $discussion["discussionID"]];

        // Pause the scheduler so the job doesn't execute immediately.
        $scheduler->pause();
        $response = $this->api()->delete("/discussions/{$discussion["discussionID"]}", [
            LongRunnerMiddleware::PARAM_MODE => LongRunner::MODE_ASYNC,
        ]);

        // We receive the scheduled job info.
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertTrackedJobCount(1, []);
        $body = $response->getBody();
        $trackingSlips = $body["trackingSlips"];
        $this->assertCount(1, $trackingSlips);
        $this->assertSame("received", $trackingSlips[0]["jobExecutionStatus"]);

        // Records weren't deleted yet.
        $this->assertRecordsFound("Discussion", $where);
        $this->assertRecordsFound("Comment", $where);

        // After the job executes its status is updated again.
        $scheduler->resume();
        $this->assertTrackedJobCount(1, [
            "jobExecutionStatus" => "complete",
        ]);
        $this->assertNoRecordsFound("Discussion", $where);
        $this->assertNoRecordsFound("Comment", $where);
    }

    /**
     * Test that our progress is tracked when deleting multiple items.
     */
    public function testBulkDeleteProgress()
    {
        $scheduler = $this->getScheduler();

        // Create a discussion with comments.
        $disc1 = $this->createDiscussion();
        $disc2 = $this->createDiscussion();
        $disc3 = $this->createDiscussion();
        $disc4 = $this->createDiscussion();
        $disc5 = $this->createDiscussion();
        $disc6 = $this->createDiscussion();
        $allDiscussions = [$disc1, $disc2, $disc3, $disc4, $disc5, $disc6];
        $allDiscussionIDs = array_column($allDiscussions, "discussionID");

        $scheduler->pause();
        $response = $this->api()->deleteWithBody("/discussions/list?longRunnerMode=async", [
            "discussionIDs" => $allDiscussionIDs,
        ]);
        $this->assertEquals(202, $response->getStatusCode());

        // Naturally this should all happen automatically, but because we can't use timeouts safely in tests
        // We've paused the scheduler and will progress the job manually.

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LongRunnerJob $job */
        $job = $driverSlip->getJob();

        $this->assertInstanceOf(LongRunnerJob::class, $job);

        // Run until we get our first progress.
        $generator = $job->runIterator();

        /** @var JobExecutionProgress $progress */
        $progress = $generator->current();
        $this->assertInstanceOf(JobExecutionProgress::class, $progress);
        $this->assertEquals(5, $progress->getQuantityComplete());
        $this->assertEquals(6, $progress->getQuantityTotal());
    }

    /**
     * Test that items that are already deleted are ignored.
     */
    public function testBulkDeleteAlreadyDeleted()
    {
        $disc1 = $this->createDiscussion();

        $bothIDs = [$disc1["discussionID"], 6711243];

        $response = $this->api()->deleteWithBody("/discussions/list", [
            "discussionIDs" => $bothIDs,
        ]);
        $body = $response->getBody();
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([$disc1["discussionID"]], $body["progress"]["successIDs"]);
        $this->assertEquals(1, $body["progress"]["countTotalIDs"]);
    }

    /**
     * Test that we validate permissions on all discussion IDs.
     */
    public function testMissingPermissionOnOne()
    {
        $disc1 = $this->createDiscussion();
        $role = $this->createRole();
        $this->createPermissionedCategory([], [$role["roleID"]]);
        $noPermDiscussion = $this->createDiscussion();

        $this->runWithPermissions(
            function () use ($disc1, $noPermDiscussion) {
                $this->expectExceptionCode(403);
                $this->api()->deleteWithBody("/discussions/list", [
                    "discussionIDs" => [$disc1["discussionID"], $noPermDiscussion["discussionID"]],
                ]);
            },
            [],
            [
                "type" => "category",
                "id" => 0,
                "permissions" => [
                    "discussions.delete" => true,
                ],
            ]
        );
    }
}

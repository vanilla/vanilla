<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license MIT
 */

namespace VanillaTests\Jobs;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Draft\ScheduledDraftJob;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Logger;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\ScheduledDraftModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\MockContentDraftModel;
use VanillaTests\Forum\ScheduledDraftTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for the ScheduleDraftJob
 */
class ScheduledDraftJobTest extends SiteTestCase
{
    use ExpectExceptionTrait, ScheduledDraftTestTrait, UsersAndRolesApiTestTrait;

    protected ScheduledDraftJob $scheduledDraftJob;

    public static $addons = ["QnA", "ideation"];

    private ScheduledDraftModel $scheduledDraftModel;

    private PostTypeModel $postTypeModel;

    private MockContentDraftModel $mockContentDraftModel;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::enableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        self::enableFeature(ContentDraftModel::FEATURE);
        $structure = \Gdn::database()->structure();
        ContentDraftModel::structure($structure);
        ScheduledDraftModel::structure($structure);
    }

    /**
     * @inheritdoc
     */
    public function setup(): void
    {
        parent::setUp();
        $this->init();
        $this->scheduledDraftJob = $this->container()->get(ScheduledDraftJob::class);
        $this->postTypeModel = $this->container()->get(PostTypeModel::class);
        $this->scheduledDraftModel = $this->container()->get(ScheduledDraftModel::class);
        $this->resetTable("draftScheduled");
        $this->resetTable("contentDraft");
        $this->resetTable("Discussion");
        $this->mockContentDraftModel = $this->container()->get(MockContentDraftModel::class);
        $this->realContentDraftModel = $this->container()->get(ContentDraftModel::class);
    }

    /**
     * Test that the job is abandoned if draft scheduling is not enabled.
     *
     * @return void
     */
    public function testJobISAbandonedIfDraftSchedulingIsNotEnabled()
    {
        $this->disableFeature(ContentDraftModel::FEATURE_SCHEDULE);
        $result = $this->scheduledDraftJob->run();
        $this->assertEquals(JobExecutionStatus::abandoned(), $result);
        $this->assertLogMessage("Draft scheduling is not enabled. Skipping the job.");
    }

    /**
     * @return void
     */
    public function testJobIsAbandonedIfNoDraftsAreScheduledForPublishing()
    {
        $result = $this->scheduledDraftJob->run();
        $this->assertEquals(JobExecutionStatus::abandoned(), $result);
        $this->assertLogMessage("No drafts are scheduled for publishing. Skipping the job.");
    }

    /**
     * Test that tags are applied to the discussion when a scheduled draft is published.
     */
    public function testTagsApplied(): void
    {
        $this->runWithConfig(["Tagging.Discussions.Enabled" => true], function () {
            $tag = $this->createTag();

            $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
            CurrentTimeStamp::mockTime($currentTimeStamp);

            $record = $this->scheduleDraftRecord([
                "draftMeta" => [
                    "tagIDs" => [$tag["tagID"]],
                    "postTypeID" => "discussion",
                ],
                "draftStatus" => "scheduled",
                "dateScheduled" => CurrentTimeStamp::getDateTime()
                    ->modify("+30 minutes")
                    ->format("c"),
            ]);

            $this->createScheduleDraft($record);

            CurrentTimeStamp::clearMockTime();

            $logger = \Gdn::getContainer()->get(Logger::class);
            $scheduledDraftJob = new ScheduledDraftJob($this->realContentDraftModel, $this->scheduledDraftModel);
            $scheduledDraftJob->setLogger($logger);
            $scheduledDraftJob->run();

            $this->api()
                ->get("/discussions")
                ->assertSuccess()
                ->assertCount(1)
                ->assertJsonArrayContains(["tagIDs" => [$tag["tagID"]]])
                ->getBody();
        });
    }

    /**
     *  Test running scheduled draft job
     */
    public function testScheduledDraftsArePublished(): void
    {
        $this->resetTable("draftScheduled");
        $this->resetTable("contentDraft");
        $this->createCategory(["name" => "ScheduledDrafts Category"]);

        // create some scheduled drafts for past
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
        CurrentTimeStamp::mockTime($currentTimeStamp);
        $this->createRandomScheduledDrafts(15, $currentTimeStamp->modify("+30 minutes"));

        CurrentTimeStamp::clearMockTime();
        //create two scheduled drafts for future.
        $this->createRandomScheduledDrafts(2, $currentTimeStamp->modify("+1 day"));

        $scheduledDrafts = $this->draftModel->selectSingle(["draftStatus" => 1], ["select" => "Count(*) as count"]);
        $this->assertEquals(17, $scheduledDrafts["count"]);

        $this->assertEquals(15, $this->draftModel->getCurrentScheduledDraftsCount());

        $scheduledDrafts = $this->draftModel->select([
            "draftStatus" => 1,
            "dateScheduled <=" => CurrentTimeStamp::getMySQL(),
        ]);

        $discussionID = array_column($scheduledDrafts, "recordID");

        $draftID = array_column($scheduledDrafts, "draftID");

        $discussions = $this->discussionModel->getWhere(["discussionID" => $discussionID]);
        $this->assertEmpty($discussions);

        // Run the jobber
        $this->scheduledDraftJob->run();

        // Make sure drafts are deleted and discussions are created
        $drafts = $this->draftModel->select(["draftID" => $draftID]);
        $this->assertEmpty($drafts);

        $discussions = $this->discussionModel->getIn($discussionID)->resultArray();
        $this->assertEquals(15, count($discussions));

        // The ones scheduled for later should still be there
        $scheduledCount = $this->draftModel->selectSingle(["draftStatus" => 1], ["select" => "Count(*) as count"]);
        $this->assertEquals(2, $scheduledCount["count"]);

        $jobsPosted = $this->scheduledDraftModel->select();
        $this->assertCount(1, $jobsPosted);
        $this->assertEquals(15, $jobsPosted[0]["totalDrafts"]);
        $this->assertEquals("processed", $jobsPosted[0]["status"]);
        $scheduledDrafts = array_column($scheduledDrafts, null, "recordID");
        //check the discussionIDs of the discussion match with the recordIDs of the drafts
        foreach ($discussions as $discussion) {
            $discussionID = $discussion["DiscussionID"];
            $this->assertArrayHasKey($discussionID, $scheduledDrafts);
            $this->assertEquals(
                $scheduledDrafts[$discussionID]["attributes"]["draftMeta"]["postTypeID"],
                $discussion["postTypeID"]
            );
        }
    }

    /**
     * Test the schedule drafts for live discussions get updated
     * @return void
     * @throws \DateMalformedStringException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function testDraftsArePublishedForLiveDiscussion()
    {
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
        CurrentTimeStamp::mockTime($currentTimeStamp);
        $this->createCategory(["name" => "Live ScheduledDrafts Category"]);
        $record = [
            "categoryID" => $this->lastInsertedCategoryID,
            "name" => "Test Idea",
            "body" => "Hello world!",
            "format" => "markdown",
            "postTypeID" => "idea",
        ];
        $response = $this->api()->post("discussions/idea", $record);
        $idea = $response->getBody();
        $discussion = $this->createDiscussion();
        $ideaDraft = $this->createScheduleDraft(
            $this->scheduleDraftRecord([
                "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"this is an updated ideation record\"}]}]",
                "format" => "rich2",
                "draftType" => "idea",
                "draftMeta" => [
                    "postTypeID" => "idea",
                    "name" => "Test updated Idea",
                ],
                "dateScheduled" => CurrentTimeStamp::getDateTime()
                    ->modify("+30 minutes")
                    ->format("c"),
            ]) + ["recordID" => $idea["discussionID"]]
        );
        $discussionDraft = $this->createScheduleDraft(
            $this->scheduleDraftRecord([
                "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"this is an updated discussion record\"}]}]",
                "draftMeta" => [
                    "name" => "Test updated Discusiion",
                ],
                "dateScheduled" => CurrentTimeStamp::getDateTime()
                    ->modify("+30 minutes")
                    ->format("c"),
            ]) + ["recordID" => $discussion["discussionID"]]
        );
        CurrentTimeStamp::clearMockTime();

        $this->scheduledDraftJob->run();

        // Make sure the draft is deleted and the discussion is updated
        $scheduledCount = $this->draftModel->selectSingle(["draftStatus" => 1], ["select" => "Count(*) as count"]);
        $this->assertEquals(0, $scheduledCount["count"]);

        $updatedIdea = $this->discussionModel->getID($idea["discussionID"], DATASET_TYPE_ARRAY);
        $this->assertEquals($ideaDraft["attributes"]["draftMeta"]["name"], $updatedIdea["Name"]);
        $this->assertEquals($ideaDraft["attributes"]["body"], $updatedIdea["Body"]);

        $updatedDiscussion = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY);
        $this->assertEquals($discussionDraft["attributes"]["draftMeta"]["name"], $updatedDiscussion["Name"]);
        $this->assertEquals($discussionDraft["attributes"]["body"], $updatedDiscussion["Body"]);

        $jobsPosted = $this->scheduledDraftModel->select();
        $this->assertCount(1, $jobsPosted);
        $this->assertEquals(2, $jobsPosted[0]["totalDrafts"]);
        $this->assertEquals("processed", $jobsPosted[0]["status"]);
    }

    /**
     * Create some random drafts for testing
     * @param int $count
     * @param \DateTimeImmutable $scheduledDate
     * @return void
     * @throws \Exception
     */
    private function createRandomScheduledDrafts(int $count, \DateTimeImmutable $scheduledDate): void
    {
        $parentPostType = ["idea", "question", "discussion"];
        $postTypes = $this->getPostTypes();
        for ($i = 0; $i < $count; $i++) {
            $randomType = array_rand($parentPostType);
            $postType = $postTypes[$parentPostType[$randomType]];
            $record = $this->scheduleDraftRecord([
                "draftMeta" => [
                    "categoryID" => $this->lastInsertedCategoryID,
                    "postTypeID" => $postType["postTypeID"],
                    "name" => "Scheduled Draft $randomType " . randomString(4),
                ],
                "draftStatus" => "scheduled",
                "dateScheduled" => $scheduledDate->format("c"),
            ]);

            $draft = $this->createScheduleDraft($record);
            $this->assertArrayHasKey("recordID", $draft);
            $this->assertNotEmpty($draft["recordID"]);
        }
    }

    /**
     * Get post types for the scheduled drafts
     *
     * @return array
     * @throws \Exception
     */
    private function getPostTypes(): array
    {
        $posTypeID = ["smart-ideas", "draft-questions", "scheduled-discussion"];

        $postTypes = $this->postTypeModel->getWhere(["postTypeID" => $posTypeID]);
        if (count($postTypes)) {
            return array_column($postTypes, null, "parentPostTypeID");
        }
        return [
            "idea" => $this->createPostType([
                "postTypeID" => "smart-ideas",
                "name" => "Smart Ideas",
                "parentPostTypeID" => "idea",
            ]),
            "question" => $this->createPostType([
                "postTypeID" => "draft-questions",
                "name" => "Draft Questions",
                "parentPostTypeID" => "question",
            ]),
            "discussion" => $this->createPostType([
                "postTypeID" => "scheduled-discussion",
                "name" => "Scheduled Discussion",
            ]),
        ];
    }

    /**
     * Test that the user receives a notification when a scheduled draft fails to publish.
     */
    public function testReceiveNotificationWhenScheduleFailsToPublish(): void
    {
        $this->createCategory(["name" => "ScheduledDrafts Category"]);
        $categoryID = $this->lastInsertedCategoryID;
        $categoryPermissions = [
            [
                "type" => "category",
                "id" => $categoryID,
                "permissions" => [
                    "discussions.view" => true,
                    "discussions.add" => true,
                    "comments.add" => true,
                ],
            ],
        ];
        $permissions = array_merge(
            [
                [
                    "type" => "global",
                    "permissions" => [
                        "schedule.allow" => true,
                        "email.view" => true,
                        "session.valid" => true,
                    ],
                ],
            ],
            $categoryPermissions
        );
        $role = $this->createRole([
            "name" => "ScheduledDraftsRole",
            "description" => "Test Role",
            "type" => "member",
            "permissions" => $permissions,
        ]);

        // Create two users with the role
        $user1 = $this->createUser(["roleID" => $role["roleID"]]);
        $user2 = $this->createUser(["roleID" => $role["roleID"]]);

        $userIDs = [$user1["userID"], $user2["userID"]];

        // Create a custom post type
        $this->createPostType([
            "postTypeID" => "scheduled-discussion-1",
            "name" => "Scheduled Discussion",
            "parentPostTypeID" => "discussion",
            "CategoryIDs" => [$categoryID],
        ]);
        // convert the time stamp to the past so that we can generate the scheduled drafts
        CurrentTimeStamp::mockTime(CurrentTimeStamp::getDateTime()->modify("-1 hour"));
        $record = $this->scheduleDraftRecord([
            "draftMeta" => [
                "categoryID" => $categoryID,
                "postTypeID" => "scheduled-discussion-1",
                "name" => "TestInvalid Draft",
            ],
            "draftStatus" => "Scheduled",
            "dateScheduled" => CurrentTimeStamp::getDateTime()
                ->modify("+30 minutes")
                ->format("c"),
        ]);
        // Generate the drafts for the users
        foreach ($userIDs as $userID) {
            $drafts[] = $this->runWithUser(function () use ($record, $userID) {
                $record["attributes"]["draftMeta"]["name"] .= " for user $userID";
                return $this->createScheduleDraft($record);
            }, $userID);
        }

        // Delete the post type so that the draft fails to publish
        $this->postTypeModel->delete(["postTypeID" => "scheduled-discussion-1"]);

        // Clear the mock time
        CurrentTimeStamp::clearMockTime();
        // Run the jobber
        $this->scheduledDraftJob->run();

        // Assert the drafts are errored
        $this->assertLogMessage("Error processing scheduled draft.");

        // Check proper error message is set and updated
        $erroredDrafts = $this->draftModel->select(
            ["draftID" => array_column($drafts, "draftID")],
            ["select" => ["draftStatus", "error"]]
        );
        foreach ($erroredDrafts as $draft) {
            $this->assertEquals(ContentDraftModel::DRAFT_TYPE_ERROR, $draft["draftStatus"]);
            $this->assertEquals("Invalid post type provided", $draft["error"]);
        }

        // Verify the notification email is sent
        $sentEmail = $this->assertEmailSentTo($user1["email"]);
        $this->assertEmailSentTo($user2["email"]);

        $emailHtml = $sentEmail->getHtmlDocument();
        $emailHtml->assertContainsString("Review");
        $emailHtml->assertContainsString(
            "Scheduled post: {$drafts[0]["attributes"]["draftMeta"]["name"]} has failed to publish."
        );
        $emailHtml->assertContainsString("There was an error with your scheduled post.");
        $emailHtml->assertContainsString("Follow the link below to see details.");
        $emailHtml->assertContainsString(url("/drafts?tab=errors", true));
    }

    /**
     * Test that Schedule Draft job is not stuck if the process results in an exception.
     */
    public function testJobIsNotStuckIfProcessResultsInException(): void
    {
        $logger = \Gdn::getContainer()->get(Logger::class);
        $this->createCategory(["name" => "Scheduled job test Category"]);
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
        CurrentTimeStamp::mockTime($currentTimeStamp);
        $this->createRandomScheduledDrafts(2, $currentTimeStamp->modify("+30 minutes"));
        $scheduledDrafts = $this->draftModel->selectSingle(["draftStatus" => 1], ["select" => "Count(*) as count"]);
        $this->assertEquals(2, $scheduledDrafts["count"]);
        CurrentTimeStamp::clearMockTime();
        $scheduledDraftJob = new ScheduledDraftJob($this->mockContentDraftModel, $this->scheduledDraftModel);
        $scheduledDraftJob->setLogger($logger);
        $result = $scheduledDraftJob->run();

        $scheduledDrafts = $this->scheduledDraftModel->select();
        $this->assertCount(0, $scheduledDrafts);

        $this->assertEquals(JobExecutionStatus::failed(), $result);

        $this->assertLogMessage("Error occurred while processing scheduled drafts.");
    }

    /**
     * Test that the "pinned" status is applied to the discussion when a scheduled draft is published with pinLocation set.
     *
     * @return void
     */
    public function testPinnedApplied(): void
    {
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->modify("-1 hour");
        CurrentTimeStamp::mockTime($currentTimeStamp);

        $record = $this->scheduleDraftRecord([
            "draftMeta" => [
                "pinLocation" => "recent",
            ],
            "draftStatus" => "scheduled",
            "dateScheduled" => CurrentTimeStamp::getDateTime()
                ->modify("+30 minutes")
                ->format("c"),
        ]);

        $this->createScheduleDraft($record);

        CurrentTimeStamp::clearMockTime();

        $logger = \Gdn::getContainer()->get(Logger::class);
        $scheduledDraftJob = new ScheduledDraftJob($this->realContentDraftModel, $this->scheduledDraftModel);
        $scheduledDraftJob->setLogger($logger);
        $scheduledDraftJob->run();

        $this->api()
            ->get("/discussions")
            ->assertSuccess()
            ->assertCount(1)
            ->assertJsonArrayContains(["pinned" => true, "pinLocation" => "recent"])
            ->getBody();
    }
}

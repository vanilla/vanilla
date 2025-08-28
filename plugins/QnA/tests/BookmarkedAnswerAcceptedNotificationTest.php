<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\QnA;

use Vanilla\Models\CommunityNotificationGenerator;
use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\DatabaseTestTrait;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunner;
use ActivityModel;
use ActivityTypeModel;

/**
 * Test suite for QnA bookmark notifications.
 *
 * This test suite verifies the notification system for bookmarked questions when answers are accepted.
 * It covers:
 * - Basic notification delivery
 * - User preference handling
 * - Long runner functionality
 * - Edge cases and error conditions
 */
class BookmarkedAnswerAcceptedNotificationTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;
    use QnaApiTestTrait;
    use SchedulerTestTrait;
    use DatabaseTestTrait;

    public static $addons = ["qna"];

    /**
     * Set up the test environment.
     *
     * Initializes:
     * - QnA API test traits
     * - Scheduler test traits
     * - Resets relevant database tables
     * - Ensures clean state for each test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpQnaApiTestTrait();
        $this->setupSchedulerTestTrait();

        // Reset all tables that might be affected by the tests
        $this->resetTable("Activity");
        $this->resetTable("UserDiscussion");
        $this->resetTable("UserMeta");
        $this->resetTable("Comment");
        $this->resetTable("Discussion");
    }

    /**
     * Test basic notification flow for bookmarked questions.
     *
     * Verifies that:
     * - Users who bookmark a question receive both Comment and BookmarkedAnswerAccepted notifications
     * - Question authors receive Comment notifications but not BookmarkedAnswerAccepted
     * - Multiple bookmarkers receive notifications independently
     * - Notification content includes correct user names and question titles
     */
    public function testBookmarkedAnswerAcceptedNotification(): void
    {
        // Create users
        $questionAuthor = $this->createUser();
        $bookmarkUser1 = $this->createUser();
        $bookmarkUser2 = $this->createUser();
        $answerAuthor = $this->createUser();

        // Set up notification preferences for bookmark users (using existing BookmarkComment preference)
        $this->api()->patch("/notification-preferences/{$bookmarkUser1["userID"]}", [
            "BookmarkComment" => ["email" => true, "popup" => true],
        ]);
        $this->api()->patch("/notification-preferences/{$bookmarkUser2["userID"]}", [
            "BookmarkComment" => ["email" => true, "popup" => true],
        ]);

        // Create a question
        $question = $this->runWithUser(function () {
            return $this->createQuestion([
                "name" => "Test Question for Bookmarking",
                "body" => "This is a test question",
            ]);
        }, $questionAuthor);

        // Bookmark the question for both users
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser1);
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser2);

        // Question author also bookmarks their own question (should NOT receive BookmarkedAnswerAccepted later)
        $this->bookmarkDiscussionWithUser($question["discussionID"], $questionAuthor);

        // Create an answer
        $answer = $this->runWithUser(function () use ($question) {
            return $this->createAnswer([
                "discussionID" => $question["discussionID"],
                "body" => "This is the answer to the question",
            ]);
        }, $answerAuthor);

        // Accept the answer as the question author
        $this->acceptAnswerAsUser($answer, $questionAuthor);

        // Check that both bookmark users received notifications (Comment + BookmarkedAnswerAccepted)
        $this->assertEquals(
            2,
            $this->getUserNotificationCount($bookmarkUser1),
            "User should receive exactly 2 notifications"
        );
        $this->assertUserHasNotificationsLike($bookmarkUser1, [
            new ExpectedNotification("Comment", [$answerAuthor["name"], "commented on", $question["name"]], "bookmark"),
            new ExpectedNotification(
                "BookmarkedAnswerAccepted",
                ["An answer was accepted", "bookmarked question", $question["name"]],
                "when a comment is chosen as an appropriate answer on your bookmarked questions"
            ),
        ]);

        $this->assertEquals(
            2,
            $this->getUserNotificationCount($bookmarkUser2),
            "User should receive exactly 2 notifications"
        );
        $this->assertUserHasNotificationsLike($bookmarkUser2, [
            new ExpectedNotification("Comment", [$answerAuthor["name"], "commented on", $question["name"]], "bookmark"),
            new ExpectedNotification(
                "BookmarkedAnswerAccepted",
                ["An answer was accepted", "bookmarked question", $question["name"]],
                "when a comment is chosen as an appropriate answer on your bookmarked questions"
            ),
        ]);

        // Check that the question author receives a Comment notification (normal behavior) but NOT BookmarkedAnswerAccepted
        $this->assertEquals(
            1,
            $this->getUserNotificationCount($questionAuthor),
            "Question author should receive exactly 1 notification (Comment)"
        );
        $this->assertUserHasNotificationsLike($questionAuthor, [
            new ExpectedNotification(
                "Comment",
                [$answerAuthor["name"], "commented on", $question["name"]],
                "mine, participated, bookmark"
            ),
        ]);
    }

    /**
     * Test that users who haven't bookmarked a question don't receive notifications when an answer is accepted.
     *
     */
    public function testNoNotificationForNonBookmarkedQuestion(): void
    {
        $nonBookmarkUser = $this->createUser();

        // Create a question (but don't bookmark it)
        $question = $this->createQuestion();

        // Create and accept an answer
        $answer = $this->createAnswer(["discussionID" => $question["discussionID"]]);
        $this->acceptAnswerAsUser($answer, $this->getSession()->User->UserID);

        // Check that the non-bookmark user doesn't receive any notifications
        $this->assertUserHasNoNotifications($nonBookmarkUser);
    }

    /**
     * Test user notification preference handling.
     *
     * Verifies that:
     * - Users with disabled bookmark notifications receive no notifications
     * - Users with default (enabled) preferences receive both Comment and BookmarkedAnswerAccepted notifications
     * - Notification preferences are properly read from user settings
     * - System respects both email and popup notification settings
     */
    public function testNotificationPreferencesRespected(): void
    {
        $bookmarkUserDisabled = $this->createUser();
        $bookmarkUserDefault = $this->createUser(); // has default (enabled) preferences

        // Explicitly disable bookmark notifications for this user (using existing BookmarkComment preference)
        $this->api()->patch("/notification-preferences/{$bookmarkUserDisabled["userID"]}", [
            "BookmarkComment" => ["email" => false, "popup" => false],
        ]);

        // Create a question and have both users bookmark it
        $question = $this->createQuestion();
        $answerAuthor = $this->createUser();

        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUserDisabled);
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUserDefault);

        // Create and accept an answer
        $answer = $this->runWithUser(function () use ($question) {
            return $this->createAnswer([
                "discussionID" => $question["discussionID"],
                "body" => "This is the answer",
            ]);
        }, $answerAuthor);
        $this->api()->patch("comments/answer/" . $answer["commentID"], ["status" => "accepted"]);

        // Disabled preference user should receive ZERO notifications
        $this->assertUserHasNoNotifications($bookmarkUserDisabled);

        // Default-preference user should receive both Comment & BookmarkedAnswerAccepted notifications
        $this->assertUserHasNotificationsLike($bookmarkUserDefault, [
            new ExpectedNotification("Comment", [$answerAuthor["name"], "commented on", $question["name"]], "bookmark"),
            new ExpectedNotification(
                "BookmarkedAnswerAccepted",
                ["An answer was accepted", "bookmarked question", $question["name"]],
                "when a comment is chosen as an appropriate answer on your bookmarked questions"
            ),
        ]);
    }

    /**
     * Test that the long runner can pause and resume processing notifications for bookmarked questions.
     *
     * This test simulates a real-world scenario where many users have bookmarked a popular question,
     * and when an answer gets accepted, the system needs to notify all those users. Since there could
     * be hundreds or thousands of bookmarked users, the notification process is split into smaller
     * batches to avoid overwhelming the system or causing timeouts.
     */
    public function testPauseAndResumeBookmarkedAnswerAcceptedLongRunner(): void
    {
        // Create a question that users might be interested in following
        $question = $this->createQuestion();

        // the expected notification when an answer is accepted
        $notification = [
            new ExpectedNotification(
                "BookmarkedAnswerAccepted",
                ["An answer was accepted", "bookmarked question", $question["name"]],
                "when a comment is chosen as an appropriate answer on your bookmarked questions"
            ),
        ];

        // Create an answer that will eventually be accepted as the best solution
        $answer = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "body" => "Answer",
        ]);

        // Accept the answer as the best solution to the question
        $answerModel = $this->container()->get(AnswerModel::class);
        $answerModel->updateCommentQnA($question, $answer, "Accepted");

        // Simulate multiple users who have bookmarked this question because they're interested in the topic
        // In a real scenario, these could be hundreds of users following a popular question
        $bookmarkUser1 = $this->createUser(["name" => "BookmarkUser1"]);
        $bookmarkUser2 = $this->createUser(["name" => "BookmarkUser2"]);
        $bookmarkUser3 = $this->createUser(["name" => "BookmarkUser3"]);
        // Every user bookmarks the question to follow updates
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser1);
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser2);
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser3);

        // Start the notification process but limit it to process only 1 notification at a time
        // This simulates a system under load or with rate limiting
        $this->getLongRunner()->setMaxIterations(1);
        $action = $answerModel->notifyBookmarkedAnswerAccepted($answer["commentID"]);
        $response = $this->getLongRunner()->runImmediately($action);

        // Verify that the first batch processed successfully and has information about remaining work
        $callbackPayload = $response->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertCount(1, $response->getSuccessIDs());

        // Assert that the first user received the notification
        $this->assertUserHasNotificationsLike($bookmarkUser1["userID"], $notification);
        $this->assertUserHasEmailsLike($bookmarkUser1["userID"], \ActivityModel::SENT_OK, $notification);

        // Now resume the process to handle the remaining notifications
        // This simulates the system picking up where it left off after a brief pause
        $this->getLongRunner()->reset();
        $this->getLongRunner()->setMaxIterations(1000);
        // Resume the long runner with the callback payload from the previous run
        $response = $this->resumeLongRunner($callbackPayload);

        // Verify that the resumed process completed successfully
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();

        // Check that the remaining notifications were processed in the second batch
        $this->assertNull($body["callbackPayload"]);
        $this->assertCount(
            2,
            $response["progress"]["successIDs"],
            "Should have processed 2 notifications in the second wave."
        );
        $this->assertEquals(3, $response["progress"]["countTotalIDs"]);

        // Assert that the users received the expected notifications
        $this->assertUserHasNotificationsLike($bookmarkUser2["userID"], $notification);
        $this->assertUserHasEmailsLike($bookmarkUser2["userID"], \ActivityModel::SENT_OK, $notification);
        $this->assertUserHasNotificationsLike($bookmarkUser3["userID"], $notification);
        $this->assertUserHasEmailsLike($bookmarkUser3["userID"], \ActivityModel::SENT_OK, $notification);
    }

    /**
     * Test that notifications contain correct data and links.
     */
    public function testNotificationContent(): void
    {
        $bookmarkUser = $this->createUser();
        $answerAuthor = $this->createUser();

        // Create a question with specific content
        $question = $this->createQuestion([
            "name" => "Specific Test Question",
            "body" => "This is a specific test question",
        ]);

        // Bookmark the question
        $this->bookmarkDiscussionWithUser($question["discussionID"], $bookmarkUser);

        // Create and accept an answer
        $answer = $this->runWithUser(function () use ($question) {
            return $this->createAnswer([
                "discussionID" => $question["discussionID"],
                "body" => "This is the specific answer",
            ]);
        }, $answerAuthor);

        $this->api()->patch("comments/answer/" . $answer["commentID"], ["status" => "accepted"]);

        // Verify the user receives both notifications with correct content
        $this->assertUserHasNotificationsLike($bookmarkUser, [
            new ExpectedNotification("Comment", [$answerAuthor["name"], "commented on", $question["name"]], "bookmark"),
            new ExpectedNotification(
                "BookmarkedAnswerAccepted",
                ["An answer was accepted", "bookmarked question", $question["name"]],
                "when a comment is chosen as an appropriate answer on your bookmarked questions"
            ),
        ]);
    }

    /**
     * Test that users without view permission don't receive notifications when an answer is accepted.
     */
    public function testNotifyBookmarkedAnswerAcceptedUserNoPermission(): void
    {
        // Create users
        $questionAuthor = $this->createUser();
        $bookmarkUser = $this->createUser();

        // Create a restricted category
        $category = $this->createPermissionedCategory(
            ["name" => "Restricted Category"],
            [\RoleModel::ADMIN_ID, \RoleModel::MOD_ID], // only admins and mods can view
            [
                "discussions.add" => [\RoleModel::MEMBER_ID],
                "comments.add" => [\RoleModel::MEMBER_ID],
            ]
        );

        // Create a question in the restricted category
        $question = $this->runWithUser(function () use ($category) {
            return $this->createQuestion([
                "name" => "Restricted Question",
                "body" => "This is a restricted question",
                "categoryID" => $category["categoryID"],
            ]);
        }, $questionAuthor);

        // Bookmark the question (using DiscussionModel directly to bypass permission check)
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $discussionModel->bookmark($question["discussionID"], $bookmarkUser["userID"]);

        // Create and accept an answer
        $answer = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "body" => "This is the answer",
        ]);

        $this->acceptAnswerAsUser($answer, $questionAuthor);

        // User without view permission should not receive any notifications
        $this->assertUserHasNoNotifications($bookmarkUser);
    }
}

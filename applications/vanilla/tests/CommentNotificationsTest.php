<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use Vanilla\Models\CommunityNotificationGenerator;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectedNotification;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use Gdn;
use \CategoryModel;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for comment notifications.
 */
class CommentNotificationsTest extends SiteTestCase
{
    use EventSpyTestTrait;
    use CommunityApiTestTrait;
    use ExpectExceptionTrait;
    use SchedulerTestTrait;
    use UsersAndRolesApiTestTrait;
    use NotificationsApiTestTrait;
    use DatabaseTestTrait;

    public static $addons = ["vanilla"];

    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get("Config");
        $configuration->set("Preferences.Popup.ParticipateComment", "1");
        $configuration->set(CategoryModel::CONF_CATEGORY_FOLLOWING, true);
    }

    /**
     * Create a comment activity to use in tests.
     *
     * @param string $catName Name of the category.
     * @param array $comment The subject of the activity.
     * @param array $discussion The discussion the comment belongs to.
     * @returns array
     */
    private function getCommentActivity(string $catName, array $comment, array $discussion): array
    {
        $commentID = $comment["commentID"];
        $activity = [
            "ActivityType" => "Comment",
            "ActivityUserID" => $comment["insertUserID"] ?? null,
            "HeadlineFormat" => t(
                "HeadlineFormat.Comment",
                '{ActivityUserID,user} commented on <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "PluralHeadlineFormat" => t(
                "PluralHeadlineFormat.Comment",
                'There are <strong>{count}</strong> new comments on discussion: <a href="{Url,html}">{Data.Name,text}</a>'
            ),
            "RecordType" => "Comment",
            "RecordID" => $commentID,
            "ParentRecordID" => $discussion["discussionID"],
            "Route" => "/discussion/comment/{$commentID}#Comment_{$commentID}",
            "Data" => [
                "Name" => $discussion["name"] ?? null,
                "Category" => $catName ?? null,
            ],
            "Ext" => [
                "Email" => [
                    "Format" => $comment["format"],
                    "Story" => $comment["body"],
                ],
            ],
        ];

        return $activity;
    }

    /**
     * Test pausing and resuming the processNotifications longrunner job.
     *
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testPauseAndResumeProcessNotifications(): void
    {
        // Create 2 users to mention.
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $category = $this->createCategory();
        // Get the discussion and comment from the DB to recreate an activity to process.
        $discussion = $this->createDiscussion();
        $discussionFromDB = Gdn::database()
            ->sql()
            ->getWhere("Discussion", ["DiscussionID" => $discussion["discussionID"]])
            ->resultArray()[0];
        $comment = $this->createComment();
        $commentFromDB = Gdn::database()
            ->sql()
            ->getWhere("Comment", ["CommentID" => $comment["commentID"]])
            ->resultArray()[0];

        $notificationGenerator = Gdn::getContainer()->get(CommunityNotificationGenerator::class);
        // Make sure we have 2 mentions to pass to the longrunner job.
        $twoMentions = Gdn::formatService()->parseMentions(
            "@{$user1["name"]} and @{$user2["name"]}",
            $commentFromDB["Format"]
        );
        $activity = $this->getCommentActivity($category["name"], $comment, $discussion);
        $longRunnerAction = $notificationGenerator->processMentionNotifications(
            $activity,
            $discussionFromDB,
            $twoMentions
        );
        $longRunner = $this->getLongRunner();
        $longRunner->reset();
        $longRunner->setMaxIterations(1);
        $response = $longRunner->runApi($longRunnerAction);

        // The first, but not the second, mention notification should have been processed, and we should have a callback payload.
        $longRunnerResult = $response->getData();
        $callbackPayload = $longRunnerResult->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertSame(2, $longRunnerResult->getCountTotalIDs());
        $this->assertCount(1, $longRunnerResult->getSuccessIDs());
        $this->assertEquals(
            "Comment_{$comment["commentID"]}_User_{$user1["userID"]}_NotificationType_mention",
            $longRunnerResult->getSuccessIDs()[0]
        );

        $longRunner->setMaxIterations(100);

        // The second mention notification should have been processed, and we should not have a callback payload.
        $responseBody = $this->resumeLongRunner($callbackPayload)->getBody();
        $this->assertNull($responseBody["callbackPayload"]);
        $this->assertSame(2, $responseBody["progress"]["countTotalIDs"]);
        $this->assertCount(1, $responseBody["progress"]["successIDs"]);
        $this->assertEquals(
            "Comment_{$comment["commentID"]}_User_{$user2["userID"]}_NotificationType_mention",
            $responseBody["progress"]["successIDs"][0]
        );
    }

    /**
     * Test pausing and resuming the processExpensiveNotifications longrunner job.
     */
    public function testPauseAndResumeProcessExpensiveNotifications()
    {
        $firstCommentUser = $this->createUser();
        $secondCommentUser = $this->createUser();

        $category = $this->createCategory();
        // Get the discussion and comment from the DB to recreate an activity to process.
        $discussion = $this->runWithUser([$this, "createDiscussion"], $firstCommentUser);
        $discussionFromDB = Gdn::database()
            ->sql()
            ->getWhere("Discussion", ["DiscussionID" => $discussion["discussionID"]])
            ->resultArray()[0];

        $comment = $this->runWithUser([$this, "createComment"], $secondCommentUser);

        $notificationGenerator = Gdn::getContainer()->get(CommunityNotificationGenerator::class);

        $commentActivity = $this->getCommentActivity($category["name"], $comment, $discussion);

        // Process the activity.
        $longRunnerAction = $notificationGenerator->processParticipatedNotifications(
            $commentActivity,
            $discussionFromDB
        );
        $longRunner = $this->getLongRunner();
        $longRunner->setMaxIterations(1);
        $response = $longRunner->runApi($longRunnerAction);

        // The first, but not the second, participated notification should have been processed, and we should have a callback payload.
        $longRunnerResult = $response->getData();
        $callbackPayload = $longRunnerResult->getCallbackPayload();
        self::assertNotNull($callbackPayload);
        self::assertSame(2, $longRunnerResult->getCountTotalIDs());
        self::assertCount(1, $longRunnerResult->getSuccessIDs());
        self::assertEquals(
            "Comment_{$comment["commentID"]}_User_{$firstCommentUser["userID"]}_NotificationType_participated",
            $longRunnerResult->getSuccessIDs()[0]
        );

        $longRunner->setMaxIterations(100);

        // The second participated notification should have been processed, and we should not have a callback payload.
        $responseBody = $this->resumeLongRunner($callbackPayload)->getBody();
        $this->assertNull($responseBody["callbackPayload"]);
        $this->assertSame(2, $responseBody["progress"]["countTotalIDs"]);
        $this->assertCount(1, $responseBody["progress"]["successIDs"]);
        $this->assertEquals(
            "Comment_{$comment["commentID"]}_User_{$secondCommentUser["userID"]}_NotificationType_participated",
            $responseBody["progress"]["successIDs"][0]
        );
    }

    /**
     * Test that we can pause and resume the category notifications longrunner.
     */
    public function testPauseAndResumeCategoryNotifications()
    {
        // Create 2 users and a category with a discussion that has a comment.
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $discussionFromDB = Gdn::database()
            ->sql()
            ->getWhere("Discussion", ["DiscussionID" => $discussion["discussionID"]])
            ->resultArray()[0];
        $comment = $this->createComment();

        // Have the 2 users subscribe to comment notifications for that category.
        $userMeta = [
            sprintf("Preferences.Email.NewComment.%d", $category["categoryID"]) => $category["categoryID"],
        ];
        $this->userModel::setMeta($user1["userID"], $userMeta);
        $this->userModel::setMeta($user2["userID"], $userMeta);

        // Recreate an activity to process.
        $activity = $this->getCommentActivity($category["name"], $comment, $discussion);

        // Process the activity.
        $longRunnerAction = new LongRunnerAction(
            CommunityNotificationGenerator::class,
            "categoryNotificationsIterator",
            [$activity, $discussionFromDB["DiscussionID"], "comment"]
        );
        $longRunner = $this->getLongRunner();
        $longRunner->setMaxIterations(1);
        $response = $longRunner->runApi($longRunnerAction);

        // The first, but not the second, mention notification should have been processed, and we should have a callback payload.
        $longRunnerResult = $response->getData();
        $callbackPayload = $longRunnerResult->getCallbackPayload();
        $this->assertNotNull($callbackPayload);
        $this->assertSame(2, $longRunnerResult->getCountTotalIDs());
        $this->assertCount(1, $longRunnerResult->getSuccessIDs());
        $this->assertEquals(
            "Comment_{$comment["commentID"]}_User_{$user1["userID"]}_NotificationType_category",
            $longRunnerResult->getSuccessIDs()[0]
        );

        $longRunner->setMaxIterations(100);

        // The second mention notification should have been processed, and we should not have a callback payload.
        $responseBody = $this->resumeLongRunner($callbackPayload)->getBody();
        $this->assertNull($responseBody["callbackPayload"]);
        $this->assertSame(2, $responseBody["progress"]["countTotalIDs"]);
        $this->assertCount(1, $responseBody["progress"]["successIDs"]);
        $this->assertEquals(
            "Comment_{$comment["commentID"]}_User_{$user2["userID"]}_NotificationType_category",
            $responseBody["progress"]["successIDs"][0]
        );
    }

    /**
     * Comment created starting longrunner for notification.
     */
    public function testDiscussionCreatedBookmarkedParticipatedUserNotifications()
    {
        $authorUser = $this->createUser();
        $bookmarkUser = $this->createUser();
        $commentUser = $this->createUser();
        $secondCommentUser = $this->createUser();

        $discussion = $this->runWithUser([$this, "createDiscussion"], $authorUser);
        $this->runWithUser([$this, "bookmarkDiscussion"], $bookmarkUser);
        $comment = $this->runWithUser(function () {
            return $this->createComment(["body" => "comment1"]);
        }, $commentUser);

        // user CREATED discussion receives comment notification
        $this->assertUserHasNotificationsLike($authorUser, [
            new ExpectedNotification(
                "Comment",
                [$commentUser["name"], "commented on", $discussion["name"]],
                "mine, participated"
            ),
        ]);

        // user BOOKMARKED discussion receives comment notification
        $this->assertUserHasNotificationsLike($bookmarkUser, [
            new ExpectedNotification(
                "Comment",
                [$commentUser["name"], "commented on", $discussion["name"]],
                "bookmark"
            ),
        ]);

        // user WHO MADE THIS COMMENT receives NO NOTIFICATION
        $this->assertUserHasNoNotifications($commentUser);

        $secondComment = $this->runWithUser(function () {
            return $this->createComment(["body" => "comment2"]);
        }, $secondCommentUser);

        // user CREATED discussion has 2 notifications grouped together..
        $this->assertUserHasNotificationsLike($authorUser, [
            new ExpectedNotification("Comment", ["2", "new comments", $discussion["name"]]),
        ]);

        // user BOOKMARKED discussion has 2 notifications grouped together.
        $this->assertUserHasNotificationsLike($bookmarkUser, [
            new ExpectedNotification("Comment", ["2", "new comments", $discussion["name"]]),
        ]);

        // First comment user receives a notification for participating.
        $this->assertUserHasNotificationsLike($commentUser, [
            new ExpectedNotification(
                "Comment",
                [$secondCommentUser["name"], "commented on", $discussion["name"]],
                "participated"
            ),
        ]);

        // Second comment user doesn't receive their own notification.
        $this->assertUserHasNoNotifications($secondCommentUser);
    }

    public function testUserMentionedNotifications()
    {
        $mentionedUser1 = $this->createUser();
        $mentionedUser2 = $this->createUser();
        $authorUser = $this->createUser();

        $discussion = $this->runWithUser(function () use ($mentionedUser1, $mentionedUser2, $authorUser) {
            $discussion = $this->createDiscussion();
            $comment = $this->createComment([
                "body" => "@{$mentionedUser1["name"]} @{$mentionedUser1["name"]} @{$mentionedUser2["name"]} @{$authorUser["name"]} comment1",
            ]);
            return $discussion;
        }, $authorUser);

        // Even though there were 2 mentions in 1 comment, the user receives one notification.
        $this->assertUserHasNotificationsLike($mentionedUser1, [
            new ExpectedNotification("CommentMention", [$authorUser["name"], "mentioned you in", $discussion["name"]]),
        ]);

        // Even though there were 2 mentions in 1 comment, the user receives one notification.
        $this->assertUserHasNotificationsLike($mentionedUser2, [
            new ExpectedNotification("CommentMention", [$authorUser["name"], "mentioned you in", $discussion["name"]]),
        ]);

        // We don't receive notifications when we mention ourselves.
        $this->assertUserHasNoNotifications($authorUser);
    }

    /**
     * Test that there are no duplicate
     */
    public function testMentionPrioritizedOverMine()
    {
        $notifyUser = $this->createUser();
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $notifyUser);

        $otherUser = $this->createUser();
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        // There should be a comment notification that there was a coment on my discssion
        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification(
                "Comment",
                [$otherUser["name"], "commented on", $discussion["name"]],
                "mine, participated"
            ),
        ]);

        $this->clearUserNotifications($notifyUser);

        // Comment again this time with a mention. The mention should take priority.
        $otherUser = $this->createUser();
        $this->runWithUser(function () use ($notifyUser) {
            $this->createComment(["body" => "Hello @{$notifyUser["name"]}"]);
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification(
                "CommentMention",
                [$otherUser["name"], "mentioned you", $discussion["name"]],
                "mention, mine, participated"
            ),
        ]);
    }

    /**
     * Test that mine notifications are more important than participated notifications.
     */
    public function testMinePrioritizedOverParticipated()
    {
        $notifyUser = $this->createUser();
        $this->runWithUser(function () {
            $this->createDiscussion();
            $this->createComment();
        }, $notifyUser);

        $otherUser = $this->createUser();
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification("Comment", [], "mine, participated"),
        ]);

        // Disable preference for mine (which normally gets priority).
        \Gdn::userModel()->savePreference($notifyUser["userID"], "Popup.DiscussionComment", false);
        $this->clearUserNotifications($notifyUser);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "participated")]);
    }

    /**
     * Test that participated notifications are more important than bookmarked notifications.
     */
    public function testParticipatedPrioritizedOverBookmarked()
    {
        $notifyUser = $this->createUser();
        $otherUser = $this->createUser();
        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $otherUser);
        $this->runWithUser(function () {
            $this->createComment();
            $this->bookmarkDiscussion($this->lastInsertedDiscussionID);
        }, $notifyUser);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification("Comment", [], "participated, bookmark"),
        ]);

        // Disable preference for participated (which normally gets priority).
        \Gdn::userModel()->savePreference($notifyUser["userID"], "Popup.ParticipateComment", false);
        $this->clearUserNotifications($notifyUser);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "bookmark")]);
    }

    /**
     * Test that bookmarked notifications are more important than category notifications.
     */
    public function testBookmarkedPrioritizedOverCategory()
    {
        $category = $this->createCategory();
        $notifyUser = $this->createUser();
        $otherUser = $this->createUser();

        // Create a discussion in a category we are following.
        $this->runWithUser(function () {
            $this->createDiscussion();
        }, $otherUser);
        $this->setCategoryPreference($notifyUser, $category, CategoryModel::NOTIFICATION_ALL);

        // The discussion is commented on.
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        // We get the category following notification.
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "advanced")]);

        // Now bookmark the discussion and try again. The bookmark should get prioritized.
        $this->runWithUser(function () {
            $this->bookmarkDiscussion($this->lastInsertedDiscussionID);
        }, $notifyUser);
        $this->clearUserNotifications($notifyUser);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [
            new ExpectedNotification("Comment", [], "bookmark, advanced"),
        ]);

        // Disable preference for bookmarked (which normally gets priority).
        \Gdn::userModel()->savePreference($notifyUser["userID"], "Popup.BookmarkComment", false);
        $this->clearUserNotifications($notifyUser);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "advanced")]);
    }

    /**
     * This test tests the following type of scenario:
     *
     * - The user is configured to receive comment and email notifications for "bookmarked" discussions.
     * - The user is configured to receive email notifications for "participated" discussions.
     *
     * Participated discussions are prioritized above bookmarked discussions.
     * As a result:
     * - the user will receive the email for the participation and an inapp for the bookmark.
     * - They will not receive an email for the bookmark (it would be a duplicate).
     */
    public function testDistinctDuplicateEmailAndPopupNotifications()
    {
        $notifyUser = $this->createUser();
        $otherUser = $this->createUser();
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $otherUser);

        // Participate and bookmark the discussion.
        $this->runWithUser(function () {
            $this->createComment();
            $this->bookmarkDiscussion($this->lastInsertedDiscussionID);
        }, $notifyUser);

        // Set preferences properly.
        $userModel = \Gdn::userModel();
        $userModel->savePreference($notifyUser["userID"], "Popup.BookmarkComment", true);
        $userModel->savePreference($notifyUser["userID"], "Email.BookmarkComment", true);
        $userModel->savePreference($notifyUser["userID"], "Popup.ParticipateComment", false);
        $userModel->savePreference($notifyUser["userID"], "Email.ParticipateComment", true);

        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);

        // We should have 1 participated popup notification and 1 email notification for the bookmark.
        // For the email notification we check for SENT_FAIL because we attempt to send an email but shouldn't be able to inside a test
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "bookmark")]);

        $this->assertUserHasEmailsLike($notifyUser, \ActivityModel::SENT_OK, [
            new ExpectedNotification("Comment", [], "participated, bookmark"),
        ]);
    }

    /**
     * Check that global preferences, user-specific preferences, email permission, and global email config all apply.
     */
    public function testOptOutChecks()
    {
        $userModel = \Gdn::userModel();
        $notifyUser = $this->createUser();
        $otherUser = $this->createUser();
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $otherUser);

        // Participate and bookmark the discussion.
        $this->runWithUser(function () {
            $this->createComment();
        }, $notifyUser);

        // Default participated is set to off.
        // User opted into popups.
        $this->resetTable("Activity");
        \Gdn::config()->saveToConfig([
            "Preferences.Email.Participated" => false,
        ]);
        $userModel->savePreference($notifyUser["userID"], "Popup.ParticipateComment", true);
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "participated")]);
        $this->assertUserHasEmailsLike($notifyUser, \ActivityModel::SENT_SKIPPED, [
            new ExpectedNotification("Comment", [], "participated"),
        ]);

        // Default preference is off, but user specific preference overrides that.
        $this->resetTable("Activity");
        $userModel->savePreference($notifyUser["userID"], "Email.ParticipateComment", true);
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "participated")]);
        $this->assertUserHasEmailsLike($notifyUser, \ActivityModel::SENT_OK, [
            new ExpectedNotification("Comment", [], "participated"),
        ]);

        // User opted into emails in the past but emails have been turned off globally.
        \Gdn::config()->saveToConfig([
            "Garden.Email.Disabled" => true,
        ]);
        $this->resetTable("Activity");
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "participated")]);
        $this->assertUserHasEmailsLike($notifyUser, \ActivityModel::SENT_SKIPPED, [
            new ExpectedNotification("Comment", [], "participated"),
        ]);

        // User has opted into popups in the past, but popups have been turned off globablly.
        \Gdn::config()->saveToConfig([
            "Garden.Popups.Disabled" => true,
        ]);
        $this->resetTable("Activity");
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        $this->assertUserHasNoNotifications($notifyUser);

        // User has opted into emails in the past, but does not have permission to receive emails.
        \Gdn::config()->saveToConfig([
            "Garden.Email.Disabled" => false,
            "Garden.Popups.Disabled" => false,
        ]);
        $this->setMemberRoleEmailPermission(false);
        $this->resetTable("Activity");
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        $this->assertUserHasNotificationsLike($notifyUser, [new ExpectedNotification("Comment", [], "participated")]);
        $this->assertUserHasEmailsLike($notifyUser, \ActivityModel::SENT_SKIPPED, [
            new ExpectedNotification("Comment", [], "participated"),
        ]);

        // User has been banned. They should not receive email notifications.
        $this->setMemberRoleEmailPermission(true);
        \Gdn::userModel()->ban($notifyUser["userID"], []);
        $this->resetTable("Activity");
        $this->runWithUser(function () {
            $this->createComment();
        }, $otherUser);
        // We truncated the table before trying to generate the notifications.
        // User should not receive any notification at all as a banned user.
        $this->assertNoRecordsFound("Activity", []);
    }

    /**
     * Change the email view permission for the member role.
     *
     * @param bool $value
     */
    private function setMemberRoleEmailPermission(bool $value)
    {
        $this->api()->patch("/roles/" . \RoleModel::MEMBER_ID, [
            "permissions" => [
                [
                    "id" => 0,
                    "permissions" => ["email.view" => $value],
                    "type" => "global",
                ],
            ],
        ]);
    }
}

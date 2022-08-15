<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use Vanilla\Models\CommunityNotificationGenerator;
use Vanilla\Scheduler\LongRunnerAction;
use VanillaTests\EventSpyTestTrait;
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

    public static function setupBeforeClass(): void
    {
        /**
         * {@inheritdoc}
         */
        parent::setupBeforeClass();
        /** @var \Gdn_Configuration $configuration */
        $configuration = static::container()->get("Config");
        $configuration->set("Preferences.Popup.ParticipateComment", "1");
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
     * Test that we can pause and resume the category notifications longrunner.
     */
    public function testPauseAndResumeCategoryNotifications()
    {
        $this->runWithConfig([CategoryModel::CONF_CATEGORY_FOLLOWING => true], function () {
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
                [$activity, $discussionFromDB, "comment"]
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
        });
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
            [$commentUser["name"], "commented on", $discussion["name"]],
        ]);

        // user BOOKMARKED discussion receives comment notification
        $this->assertUserHasNotificationsLike($bookmarkUser, [
            [$commentUser["name"], "commented on", $discussion["name"]],
        ]);

        // user WHO MADE THIS COMMENT receives NO NOTIFICATION
        $this->assertUserHasNoNotifications($commentUser);

        $secondComment = $this->runWithUser(function () {
            return $this->createComment(["body" => "comment2"]);
        }, $secondCommentUser);

        // user CREATED discussion has 2 notifications grouped together..
        $this->assertUserHasNotificationsLike($authorUser, [["2", "new comments", $discussion["name"]]]);

        // user BOOKMARKED discussion has 2 notifications grouped together.
        $this->assertUserHasNotificationsLike($bookmarkUser, [["2", "new comments", $discussion["name"]]]);

        // First comment user receives a notification for participating.
        $this->assertUserHasNotificationsLike($commentUser, [
            [$secondCommentUser["name"], "commented on", $discussion["name"]],
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
            [$authorUser["name"], "mentioned you in", $discussion["name"]],
        ]);

        // Even though there were 2 mentions in 1 comment, the user receives one notification.
        $this->assertUserHasNotificationsLike($mentionedUser2, [
            [$authorUser["name"], "mentioned you in", $discussion["name"]],
        ]);

        // We don't receive notifications when we mention ourselves.
        $this->assertUserHasNoNotifications($authorUser);
    }
}

<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Garden\EventManager;
use Gdn;
use LogModel;
use VanillaTests\ExpectedNotification;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\NotificationsApiTestTrait;
use VanillaTests\SiteTestCase;
use \UserModel;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test {@link UserModel}.
 */
class LogModelTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    use NotificationsApiTestTrait;

    /**
     * @var \Gdn_Session
     */
    private $session;

    private EventManager $eventManager;

    private LogModel $logModel;

    private \ReactionModel $reactionModel;

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->session = Gdn::session();
        $this->createUserFixtures();
        $this->eventManager = Gdn::getContainer()->get(EventManager::class);
        $this->logModel = Gdn::getContainer()->get(LogModel::class);
        $this->reactionModel = Gdn::getContainer()->get(\ReactionModel::class);
    }

    /**
     * Test createLogPostEvent
     *
     */
    public function testCreateLogPostEvent()
    {
        $logPostEvent = LogModel::createLogPostEvent(
            "save",
            "registration",
            ["test" => "result"],
            "reactions",
            $this->session->UserID,
            "negative",
            null
        );

        $this->assertSame("save", $logPostEvent->getAction());
    }

    /**
     * This tests that notifications are not sent for new posts that are auto-moderated and sent to the spam queue directly,
     * but notifications are sent when they are restored.
     *
     * @return void
     */
    public function testNotificationsAfterRestoringAutoModeratedPost()
    {
        $discussionAuthor = $this->createUser();
        Gdn::userModel()->savePreference($discussionAuthor["userID"], "Popup.DiscussionComment", true);
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $discussionAuthor);

        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind("base_checkSpam", $fn);

        $commentAuthor = $this->createUser();
        $this->runWithUser(function () use ($discussion, $commentAuthor, $discussionAuthor) {
            // This comment will be auto-moderated and sent to the spam queue.
            $this->createComment();

            // Auto-moderated posts don't cause notifications to be sent.
            $this->assertUserHasNoNotifications($discussionAuthor);

            $logs = $this->logModel->getWhere(["RecordType" => "Comment", "RecordUserID" => $commentAuthor["userID"]]);
            $this->assertCount(1, $logs);

            $this->logModel->restore($logs[0]);

            // Restoring the post causes the appropriate notifications to be sent out.
            $this->assertUserHasNotificationsLike($discussionAuthor, [
                new ExpectedNotification(
                    "Comment",
                    [$commentAuthor["name"], "commented on", $discussion["name"]],
                    "mine, participated"
                ),
            ]);
        }, $commentAuthor);
        $this->eventManager->unbind("base_checkSpam", $fn);
    }

    /**
     * This tests that notifications are sent for new posts that aren't auto-moderated, but no additional notifications
     * are sent if they are flagged as spam and later restored.
     *
     * @return void
     * @throws \Gdn_UserException
     */
    public function testNoNotificationsAfterRestoringFlaggedPost()
    {
        $discussionAuthor = $this->createUser();
        Gdn::userModel()->savePreference($discussionAuthor["userID"], "Popup.DiscussionComment", true);
        $discussion = $this->runWithUser(function () {
            return $this->createDiscussion();
        }, $discussionAuthor);

        $commentAuthor = $this->createUser();
        $comment = $this->runWithUser(function () use ($discussion, $commentAuthor, $discussionAuthor) {
            // This comment will be posted successfully.
            $comment = $this->createComment();

            // Creating posts that don't trigger auto-moderation should send notifications normally.
            $this->assertUserHasNotificationsLike($discussionAuthor, [
                new ExpectedNotification(
                    "Comment",
                    [$commentAuthor["name"], "commented on", $discussion["name"]],
                    "mine, participated"
                ),
            ]);
            $this->clearUserNotifications($discussionAuthor);

            return $comment;
        }, $commentAuthor);

        $this->reactionModel->react("Comment", $comment["commentID"], "Spam", null, false, \ReactionModel::FORCE_ADD);

        $logs = $this->logModel->getWhere(["RecordType" => "Comment", "RecordUserID" => $commentAuthor["userID"]]);
        $this->assertCount(1, $logs);
        $this->logModel->restore($logs[0]);

        // Restoring the post should not cause notifications to be sent a second time.
        $this->assertUserHasNoNotifications($discussionAuthor);
    }

    /**
     * @inheritDoc
     */
    public static function getAddons(): array
    {
        $addons = parent::getAddons();
        $addons[] = "reactions";
        return $addons;
    }
}

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
use Vanilla\Dashboard\Models\AutomationRuleRevisionModel;
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
     * Test restoring a user a banned user.
     *
     * @return void
     */
    public function testRestoreBannedUser(): void
    {
        $user = $this->createUser();
        $this->runWithUser(function () {
            $this->createDiscussion();
            $this->createComment();
        }, $user);

        $this->userModel->ban($user["userID"], ["DeleteContent" => true]);
        $logEntry = $this->logModel->getWhere(["Operation" => "Ban", "RecordID" => $user["userID"]]);
        $this->logModel->restore($logEntry[0]);

        $restoredUser = $this->userModel->getID($user["userID"], DATASET_TYPE_ARRAY);
        $this->assertEquals(2, $restoredUser["CountPosts"]);

        $discussions = $this->api()
            ->get("discussions", ["insertUserID" => $user["userID"]])
            ->getBody();
        $this->assertCount(1, $discussions);

        $comments = $this->api()
            ->get("comments", ["insertUserID" => $user["userID"]])
            ->getBody();
        $this->assertCount(1, $comments);
    }

    /**
     * Test log model getAutomationLogsByDispatchID for User type.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function testGetAutomationLogsByDispatchIDForUserType(): void
    {
        $automationRuleRevisionModel = $this->container()->get(AutomationRuleRevisionModel::class);
        $user = $this->createUser(["name" => "testUser"]);
        LogModel::insert("Automation", "User", [
            "RecordUserID" => $user["userID"],
            "DispatchUUID" => "xxx",
            "AutomationRuleRevisionID" => 1,
            "Data" => [
                "currentStatusID" => 1,
                "newStatusID" => 2,
            ],
        ]);
        $automationRuleRevisionModel->insert([
            "automationRuleID" => 1,
            "automationRuleRevisionID" => 1,
            "triggerType" => "testTrigger",
            "triggerValue" => ["key" => "value"],
            "actionType" => "testAction",
            "actionValue" => ["key" => "value"],
        ]);

        $logs = $this->logModel->getAutomationLogsByDispatchID("xxx", "User", 1);
        $this->assertCount(1, $logs);
        $logs = $logs[0];
        $this->assertArrayHasKey("RecordName", $logs);
        $this->assertEquals($user["name"], $logs["RecordName"]);
        $this->assertArrayHasKey("RecordEmail", $logs);
        $this->assertEquals($user["email"], $logs["RecordEmail"]);
        $this->assertEquals("User", $logs["RecordType"]);
    }

    /**
     * Test log model getAutomationLogsByDispatchID for Discussion type.
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\NotFoundException
     */
    public function testGetAutomationLogsByDispatchIDForDiscussionType()
    {
        $automationRuleRevisionModel = $this->container()->get(AutomationRuleRevisionModel::class);
        $automationRuleRevisionModel->insert([
            "automationRuleID" => 1,
            "automationRuleRevisionID" => 2,
            "triggerType" => "testTrigger",
            "triggerValue" => ["key" => "value"],
            "actionType" => "testAction",
            "actionValue" => ["key" => "value"],
        ]);
        $discussion = $this->createDiscussion();
        LogModel::insert("Automation", "Discussion", [
            "RecordID" => $discussion["discussionID"],
            "DispatchUUID" => "zyxw",
            "AutomationRuleRevisionID" => 2,
            "Data" => [
                "currentStatusID" => 1,
                "newStatusID" => 2,
            ],
        ]);

        $logs = $this->logModel->getAutomationLogsByDispatchID("zyxw", "Discussion", 1);
        $this->assertCount(1, $logs);
        $logs = $logs[0];
        $this->assertArrayHasKey("RecordName", $logs);
        $this->assertEquals($discussion["name"], $logs["RecordName"]);
        $this->assertArrayHasKey("RecordBody", $logs);
        $this->assertEquals($discussion["body"], $logs["RecordBody"]);
        $this->assertEquals("Discussion", $logs["RecordType"]);
        $this->assertArrayHasKey("Format", $logs);
    }
}

<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use ActivityModel;
use CategoryModel;
use Gdn;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use Garden\EventManager;
use Vanilla\Community\Events\CommentEvent;
use VanillaTests\TestLoggerTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test {@link CommentModel}.
 */
class CommentModelTest extends SiteTestCase
{
    use TestCategoryModelTrait,
        TestDiscussionModelTrait,
        TestCommentModelTrait,
        CommunityApiTestTrait,
        EventSpyTestTrait,
        UsersAndRolesApiTestTrait,
        TestLoggerTrait;

    /** @var CommentEvent */
    private $lastEvent;

    /** @var ActivityModel  */
    private $activityModel;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Test as an admin
        \Gdn::session()->start(self::$siteInfo["adminUserID"], false, false);
    }

    /**
     * Setup
     */
    public function setup(): void
    {
        parent::setUp();

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleCommentEvent");
        $this->activityModel = Gdn::getContainer()->get(ActivityModel::class);
        $this->userPreferenceModel = Gdn::getContainer()->get(UserNotificationPreferencesModel::class);
    }

    /**
     * A test listener that increments the counter.
     *
     * @param CommentEvent $e
     * @return CommentEvent
     */
    public function handleCommentEvent(CommentEvent $e): CommentEvent
    {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Test the lookup method.
     */
    public function testLookup()
    {
        $discussion = [
            "CategoryID" => 1,
            "Name" => "Comment Lookup Test",
            "Body" => "foo foo foo",
            "Format" => "Text",
            "InsertUserID" => 1,
        ];
        $discussionID = $this->discussionModel->save($discussion);

        $comment = [
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "Text",
        ];
        $commentID = $this->commentModel->save($comment);
        $this->assertNotFalse($commentID);

        $result = $this->commentModel->selectComments(["CommentID" => $commentID] + $comment);
        $this->assertInstanceOf("Gdn_DataSet", $result);
        $this->assertEquals(1, $result->count());

        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($commentID, $row["CommentID"]);
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void
    {
        $discussion = [
            "CategoryID" => 1,
            "Name" => "test delete event",
            "Body" => "foo foo foo",
            "Format" => "Text",
            "InsertUserID" => 1,
        ];
        $discussionID = $this->discussionModel->save($discussion);
        $commentID = $this->commentModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);

        $this->commentModel->deleteID($commentID);

        $this->assertInstanceOf(CommentEvent::class, $this->lastEvent);
        $this->assertEquals(CommentEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void
    {
        $discussion = [
            "CategoryID" => 1,
            "Name" => "test insert",
            "Body" => "foo foo foo",
            "Format" => "Text",
            "InsertUserID" => 1,
        ];
        $discussionID = $this->discussionModel->save($discussion);
        $this->commentModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->assertInstanceOf(CommentEvent::class, $this->lastEvent);
        $this->assertEquals(CommentEvent::ACTION_INSERT, $this->lastEvent->getAction());
    }

    /**
     * Verify update event dispatched during save.
     *
     * @return void
     */
    public function testSaveUpdateEventDispatched(): void
    {
        $discussion = [
            "CategoryID" => 1,
            "Name" => "test update",
            "Body" => "foo foo foo",
            "Format" => "Text",
            "InsertUserID" => 1,
        ];
        $discussionID = $this->discussionModel->save($discussion);
        $commentID = $this->commentModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);

        $this->commentModel->save([
            "CommentID" => $commentID,
            "Body" => "Hello world updated",
        ]);

        $this->assertInstanceOf(CommentEvent::class, $this->lastEvent);
        $this->assertEquals(CommentEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }

    /**
     * Smoke test `CommentModel::getByUser()`.
     *
     * @param int $version
     */
    public function testGetByUser(int $version = 1): void
    {
        $userID = \Gdn::session()->UserID;

        $comments = $this->insertComments(10);
        if ($version === 1) {
            $actual = $this->commentModel->getByUser($userID, 10, 0);
        } else {
            $actual = $this->commentModel->getByUser2($userID, 10, 0);
        }
        foreach ($actual as $row) {
            $this->assertEquals($userID, $row->InsertUserID);
            $this->assertNotEmpty($row->CategoryID);
        }
    }

    /**
     * Smoke test `CommentModel::getByUser2()`.
     */
    public function testGetByUser2(): void
    {
        $this->testGetByUser(2);
    }

    /**
     * Test `CommentModel::getByUser2()` with permission.
     */
    public function testGetByUser2Permission(): void
    {
        $adminUserID = \Gdn::session()->UserID;
        $roles = $this->getRoles();
        $memberRole = $roles["Member"];

        // Create a member user.
        $memberUserID = $this->userModel->save([
            "Name" => "testgetbyuser2",
            "Email" => __FUNCTION__ . "@example.com",
            "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
            "RoleID" => $memberRole,
        ]);

        $categoryAdmin = $this->createPermissionedCategory([], [$roles["Administrator"]]);
        $discussionAdmin = [
            "CategoryID" => $categoryAdmin["categoryID"],
            "Name" => __FUNCTION__ . "test discussion",
            "Body" => "foo foo foo",
            "Format" => "Text",
            "InsertUserID" => $adminUserID,
        ];

        $discussionIDAdmin = $this->discussionModel->insert($discussionAdmin);
        $this->commentModel->save([
            "DiscussionID" => $discussionIDAdmin,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);

        // Switch to member user.
        \Gdn::session()->start($memberUserID, false, false);
        $this->insertComments(10);
        $actual = $this->commentModel->getByUser2($memberUserID, 10, 0, false, null, "desc", "PermsDiscussionsView");
        $countRows = $actual->numRows();
        foreach ($actual as $row) {
            $this->assertEquals($memberUserID, $row->InsertUserID);
            $this->assertNotEmpty($row->CategoryID);
        }
        $this->assertEquals(10, $countRows);
    }

    /**
     * Test a dirty-record is added when calling setField.
     */
    public function testDirtyRecordAdded()
    {
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();
        $id = $comment["commentID"];
        $this->commentModel->setField($id, "Score", 5);
        $this->assertDirtyRecordInserted("comment", $id);
    }

    /**
     * Test a category following notification with Following disabled.
     */
    public function testAdvancedNoticationsFollowingDisabled()
    {
        $this->runWithConfig([CategoryModel::CONF_CATEGORY_FOLLOWING => false], function () {
            $roles = $this->getRoles();

            // Create a member user.
            $memberUser = $this->createUser([
                "Name" => "testNotications",
                "Email" => __FUNCTION__ . "@example.com",
                "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                "RoleID" => $this->memberID,
            ]);

            $commentUser = $this->createUser([
                "Name" => "testComment",
                "Email" => __FUNCTION__ . "@example.com",
                "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                "RoleID" => $this->memberID,
            ]);

            $categoryAdmin = $this->createPermissionedCategory([], [$roles["Member"]]);

            $userMeta = [
                sprintf("Preferences.Email.NewComment.%d", $categoryAdmin["categoryID"]) => $categoryAdmin[
                    "categoryID"
                ],
            ];
            $this->userModel::setMeta($memberUser["userID"], $userMeta);

            $discussionMember = [
                "CategoryID" => $categoryAdmin["categoryID"],
                "Name" => __FUNCTION__ . "test discussion",
                "Body" => "foo foo foo",
                "Format" => "Text",
                "InsertUserID" => $commentUser["userID"],
            ];

            $discussionIDMember = $this->createDiscussion($discussionMember);
            $this->createComment([
                "DiscussionID" => $discussionIDMember,
                "Body" => "Hello world.",
                "Format" => "markdown",
            ]);

            $this->api()->setUserID($memberUser["userID"]);
            $notifications = $this->api()
                ->get("/notifications")
                ->getBody();
            $this->assertCount(0, $notifications);
        });
    }

    /**
     * Test a category following notification with Following enabled.
     */
    public function testAdvancedNoticationsFollowingEnabled()
    {
        $this->runWithConfig([CategoryModel::CONF_CATEGORY_FOLLOWING => true], function () {
            $roles = $this->getRoles();

            // Create a member user.
            $memberUser = $this->createUser([
                "Name" => "testNotications",
                "Email" => __FUNCTION__ . "@example.com",
                "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                "RoleID" => $this->memberID,
            ]);

            $commentUser = $this->createUser([
                "Name" => "testComment",
                "Email" => __FUNCTION__ . "@example.com",
                "Password" => randomString(\Gdn::config("Garden.Password.MinLength")),
                "RoleID" => $this->memberID,
            ]);

            $categoryAdmin = $this->createPermissionedCategory([], [$roles["Member"]]);

            $this->userPreferenceModel->save($memberUser["userID"], [
                "Popup.NewComment.{$categoryAdmin["categoryID"]}" => 1,
            ]);

            $discussionMember = [
                "CategoryID" => $categoryAdmin["categoryID"],
                "Name" => __FUNCTION__ . "test discussion",
                "Body" => "foo foo foo",
                "Format" => "Text",
                "InsertUserID" => $commentUser["userID"],
            ];

            $discussionIDMember = $this->createDiscussion($discussionMember);
            $this->createComment([
                "DiscussionID" => $discussionIDMember,
                "Body" => "Hello world.",
                "Format" => "markdown",
            ]);

            $this->api()->setUserID($memberUser["userID"]);
            $notifications = $this->api()
                ->get("/notifications")
                ->getBody();
            $this->assertCount(1, $notifications);
        });
    }

    /**
     * Test that user counts do not get updated when the user do not exist.
     *
     * @return void
     */
    public function testUpdateUserExpectFail(): void
    {
        $discussionID = $this->createDiscussion();
        $comment = $this->createComment(["DiscussionID" => $discussionID]);

        // Set an invalid use
        $sql = $this->commentModel->Database->createSql();
        $sql->update("Comment", ["InsertUserID" => 1234567], ["CommentID" => $comment["commentID"]])->put();

        $this->commentModel->deleteID($comment["commentID"]);
        $this->assertLogMessage("Failed updating the user 1234567, this user do not exists.");
    }
}

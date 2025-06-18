<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the Reactions plugin.
 */
class ReactionsTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;
    use ReactionsTestTrait;

    public static $addons = ["dashboard", "vanilla"];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test reacting to a discussion through the /react/discussion/{reactionType} endpoint.
     */
    public function testDiscussionReaction(): void
    {
        $this->createCategory();
        $discussion = $this->createDiscussion();

        $user = $this->createReactingUser();

        $this->getSession()->start($user["userID"]);
        $this->react("discussion", $discussion["discussionID"], "like");

        $retrievedDiscussion = $this->api()
            ->get("/discussions/{$discussion["discussionID"]}", ["expand" => "reactions"])
            ->getBody();

        $likeReactions = $this->getPostReaction($retrievedDiscussion, "Like");

        $this->assertSame(1, $likeReactions["count"]);
    }

    /**
     * Test reacting to a comment through the /react/comment/{reactionType} endpoint.
     */
    public function testCommentReaction(): void
    {
        $this->createCategory();
        $this->createDiscussion();
        $comment = $this->createComment();

        $user = $this->createReactingUser();

        $this->getSession()->start($user["userID"]);
        $this->react("comment", $comment["commentID"], "like");

        $retrievedComment = $this->api()
            ->get("/comments/{$comment["commentID"]}", ["expand" => "reactions"])
            ->getBody();

        $likeReactions = $this->getPostReaction($retrievedComment, "Like");

        $this->assertSame(1, $likeReactions["count"]);
    }

    /**
     * Test that an error is thrown when reacting on a post in a category for which the user doesn't have the view permission.
     */
    public function testReactingInDisallowedCategory(): void
    {
        $reactingUser = $this->createReactingUser();

        $disallowedCategory = $this->createPermissionedCategory([], [\RoleModel::ADMIN_ID, \RoleModel::MOD_ID]);

        $discussion = $this->createDiscussion(["categoryID" => $disallowedCategory["categoryID"]]);

        $this->getSession()->start($reactingUser["userID"]);

        $this->expectExceptionCode(403);
        $this->react("discussion", $discussion["discussionID"], "like");
    }

    /**
     * Test that user points are reversed when a post marked as spam is approved
     *
     * @return void
     */
    public function testSpamApproval(): void
    {
        $this->createUserFixtures();
        $spamTestUser = $this->createUser(["name" => "SpamTest", "email" => "spamtestuser@test.com"]);
        $reactionModel = $this->container()->get(\ReactionModel::class);
        $logModel = $this->container()->get(\LogModel::class);
        $userPoints = function () use ($spamTestUser) {
            $sql = $this->userModel->createSql();
            return $sql
                ->select("points")
                ->from("User")
                ->where("userID", $spamTestUser["userID"])
                ->get()
                ->firstRow(DATASET_TYPE_ARRAY)["points"];
        };
        $this->runWithUser(function () use ($spamTestUser, $reactionModel, $userPoints) {
            $discussion = $this->createDiscussion([
                "name" => "SpamTest",
                "body" => "This is a discussion to test spam",
            ]);
            $reactionModel->react("discussion", $discussion["discussionID"], "like", $this->memberID);
            $currentPoints = $userPoints();
            $this->assertSame(1, $currentPoints);
        }, $spamTestUser["userID"]);

        //Now Mark this discussion as Spam
        $discussionID = $this->lastInsertedDiscussionID;
        $reactionModel->react("discussion", $discussionID, "spam", $this->getSession()->UserID);
        //Now test that the user has lost the points
        $currentPoints = $userPoints();
        $this->assertSame(0, $currentPoints);

        //Now Undo the spam
        $log = $logModel->getWhere([
            "RecordType" => "Discussion",
            "RecordID" => $discussionID,
            "Operation" => "Spam",
        ])[0];
        $logModel->restore($log);

        //Now test that the user has gained the points back
        $currentPoints = $userPoints();
        $this->assertSame(1, $currentPoints);
    }

    /**
     * Test that a moderator un spam another moderator post
     *
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testSpamCanBeUnmarkedByModerators(): void
    {
        $logModel = $this->container()->get(\LogModel::class);
        $discussionModel = $this->container()->get(\DiscussionModel::class);

        $userID = $this->createUserFixture("Member");
        $this->runWithUser(function () {
            $this->createDiscussion(["name" => "NotSpamCheck", "body" => "This is a discussion to test undo spam"]);
        }, $userID);
        $discussionID = $this->lastInsertedDiscussionID;
        $this->react("discussion", $discussionID, "Spam");
        //Check if the record was marked as spam
        $log = $logModel->getWhere([
            "RecordType" => "Discussion",
            "RecordID" => $discussionID,
            "Operation" => "Spam",
        ])[0];
        $this->assertNotEmpty($log);
        $this->assertEquals("Spam", $log["Operation"]);
        $this->assertEquals($this->getSession()->UserID, $log["InsertUserID"]);

        $discussion = $discussionModel->getID($this->lastInsertedDiscussionID);
        $this->assertEmpty($discussion);

        //Now give the created user moderator privileges
        $this->userModel->addRoles($userID, [\RoleModel::MOD_ID], false);

        //Now remove the discussion from spam queue
        $this->bessy()->post("/log/notSpam", ["LogIDs" => [$log["LogID"]]]);

        //Now make sure the discussion is added back
        $discussion = $discussionModel->getID($discussionID);
        $this->assertNotEmpty($discussion);

        //Now check if the log is removed
        $log = $logModel->getWhere([
            "RecordType" => "Discussion",
            "RecordID" => $discussionID,
            "Operation" => "Spam",
        ]);

        $this->assertEmpty($log);
    }
}

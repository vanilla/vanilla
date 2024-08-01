<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license Proprietary
 */

namespace Models;

use CommentModel;
use DiscussionModel;
use Vanilla\Dashboard\Models\UserMentionsModel;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test for UserMentionsModel.
 */
class UserMentionsModelTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;

    /** @var UserMentionsModel */
    private $userMentionModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->userMentionModel = $this->container()->get(UserMentionsModel::class);
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
        $this->commentModel = $this->container()->get(CommentModel::class);
    }

    /**
     * Test inserting Discussion Mentions using UserMentionsModel::addDiscussionMention().
     */
    public function testDiscussionMentionSuccess()
    {
        $user1 = ["userID" => 1, "name" => "testUser"];
        $user2 = ["userID" => 2, "name" => "testUser"];
        $discussion = ["discussionID" => 1, "categoryID" => 1];
        $expect = [
            "userID" => 1,
            "recordType" => "discussion",
            "recordID" => 1,
            "mentionedName" => "testUser",
            "parentRecordType" => "category",
            "parentRecordID" => 1,
            "status" => "active",
        ];
        $this->discussionModel->insertUserMentions($user1, $discussion);
        $this->discussionModel->insertUserMentions($user2, $discussion);

        $result = $this->userMentionModel->getByRecordID(1, $this->userMentionModel::DISCUSSION);
        unset($result[0]["dateInserted"]);
        $this->assertEquals($expect, $result[0]);
        $this->assertEquals(2, count($result));
    }

    /**
     * Test cases for attempting to insert missing data for UserMentionsModel::addDiscussionMention().
     */
    public function testDiscussionMentionFailure()
    {
        $result = $this->discussionModel->insertUserMentions(["name" => "testUser"], ["discussionID" => 1]);
        $this->assertFalse($result, "Error: The UserID is required");

        $result = $this->discussionModel->insertUserMentions(["UserID" => 1], ["discussionID" => 1]);
        $this->assertFalse($result, "Error: The username is required");

        $result = $this->discussionModel->insertUserMentions(["UserID" => 1, "name" => "testUser"], []);
        $this->assertFalse($result, "Error: The DiscussionID is required");
    }

    /**
     * Test inserting Comment Mentions using UserMentionsModel::addCommentMention().
     */
    public function testCommentMentionSuccess()
    {
        $this->resetTable("userMention");
        $user1 = ["userID" => 1, "name" => "testUser"];
        $user2 = ["userID" => 2, "name" => "testUser"];
        $comment = ["commentID" => 1, "discussionID" => 1];

        $expect = [
            "userID" => 1,
            "recordType" => "comment",
            "recordID" => 1,
            "mentionedName" => "testUser",
            "parentRecordType" => "discussion",
            "parentRecordID" => 1,
            "status" => "active",
        ];
        $this->commentModel->insertUserMentions($user1, $comment);
        $this->commentModel->insertUserMentions($user2, $comment);

        $result = $this->userMentionModel->getByRecordID(1, $this->userMentionModel::COMMENT);
        unset($result[0]["dateInserted"]);
        $this->assertEquals($expect, $result[0]);
        $this->assertEquals(2, count($result));
    }

    /**
     * Test cases for attempting to insert missing data for UserMentionsModel::addCommentMention().
     */
    public function testCommentMentionFailure()
    {
        $result = $this->commentModel->insertUserMentions(["name" => "testUser"], ["commentID" => 1]);
        $this->assertFalse($result, "Error: The UserID is required");

        $result = $this->commentModel->insertUserMentions(["UserID" => 1], ["commentID" => 1]);
        $this->assertFalse($result, "Error: The username is required");

        $result = $this->commentModel->insertUserMentions(["UserID" => 1, "name" => "testUser"], []);
        $this->assertFalse($result, "Error: The CommentID is required");
    }

    /**
     * Test for UserMentionsModel::getByUser().
     */
    public function testGetUserMentionByID()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $discussion = ["discussionID" => 1, "categoryID" => 1];
        $comment = ["commentID" => 1, "discussionID" => 1];

        $this->discussionModel->insertUserMentions($user1, $discussion);
        $this->discussionModel->insertUserMentions($user2, $discussion);
        $this->commentModel->insertUserMentions($user1, $comment);

        $results = $this->userMentionModel->getByUser($user1["userID"]);

        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            $this->assertEquals($user1["userID"], $result["userID"]);
        }
    }

    /**
     * Tests for UserMentionEventHandler:: handleDiscussionEvent.
     */
    public function testDiscussionMentionEvents()
    {
        $user1 = $this->createUser(["name" => "user1" . __FUNCTION__]);
        $user2 = $this->createUser(["name" => "user2" . __FUNCTION__]);

        $discussion = $this->createDiscussion(["body" => "test @\"{$user1["name"]}\""]);
        $resultUser1 = $this->userMentionModel->getByUser($user1["userID"]);
        $this->assertEquals(1, count($resultUser1));

        $this->api()->patch("/discussions/" . $discussion["discussionID"], ["body" => "test @\"{$user2["name"]}\""]);
        $resultUser1 = $this->userMentionModel->getByUser($user1["userID"]);
        $resultUser2 = $this->userMentionModel->getByUser($user2["userID"]);
        $this->assertEquals(0, count($resultUser1));
        $this->assertEquals(1, count($resultUser2));

        $this->api()->delete("/discussions/" . $discussion["discussionID"]);
        $resultUser2 = $this->userMentionModel->getByUser($user2["userID"]);
        $this->assertEquals(0, count($resultUser2));
    }

    /**
     * Tests for UserMentionEventHandler:: handleCommentEvent.
     */
    public function testCommentMentionEvents()
    {
        $user1 = $this->createUser(["name" => "user1" . __FUNCTION__]);
        $user2 = $this->createUser(["name" => "user2" . __FUNCTION__]);
        $this->createDiscussion();

        $comment = $this->createComment(["body" => "test @\"{$user1["name"]}\""]);
        $resultUser1 = $this->userMentionModel->getByUser($user1["userID"]);
        $this->assertEquals(1, count($resultUser1));

        $this->api()->patch("/comments/" . $comment["commentID"], ["body" => "test @\"{$user2["name"]}\""]);
        $resultUser1 = $this->userMentionModel->getByUser($user1["userID"]);
        $resultUser2 = $this->userMentionModel->getByUser($user2["userID"]);
        $this->assertEquals(0, count($resultUser1));
        $this->assertEquals(1, count($resultUser2));

        $this->api()->delete("/comments/" . $comment["commentID"]);
        $resultUser2 = $this->userMentionModel->getByUser($user2["userID"]);
        $this->assertEquals(0, count($resultUser2));
    }
}

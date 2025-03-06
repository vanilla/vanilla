<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Models;

use Garden\EventManager;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `SpamModel` class.
 */
class SpamModelTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    const EVENT_NAME = "base_checkSpam";
    /**
     * @var \LogModel
     */
    private $logModel;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()->call(function (\LogModel $logModel, EventManager $eventManager) {
            $this->logModel = $logModel;
            $this->eventManager = $eventManager;
        });
        $this->createUserFixtures();
        $this->getSession()->start($this->memberID);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $handlers = $this->eventManager->getHandlers(self::EVENT_NAME);
        foreach ($handlers as $handler) {
            $this->eventManager->unbind(self::EVENT_NAME, $handler);
        }
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultSpam(): void
    {
        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $id = self::id();
        $isSpam = \SpamModel::isSpam("Discussion", ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultDiscussionSpam(): void
    {
        $discussion = $this->createDiscussion();
        $id = $discussion["discussionID"];

        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $isSpam = \SpamModel::isSpam("Discussion", [
            "Name" => __FUNCTION__,
            "Body" => __FUNCTION__,
            "DiscussionID" => $id,
        ]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["Operation" => "Spam", "RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $dbRecord = $discussionModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertSame(false, $dbRecord);
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultSpamNotDeletedOnUpdate(): void
    {
        $discussion = $this->createDiscussion();
        $id = $discussion["discussionID"];

        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $isSpam = \SpamModel::isSpam(
            "Discussion",
            ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id, "DiscussionID" => $id],
            ["action" => "update"]
        );
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["Operation" => "Spam", "RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $dbRecord = $discussionModel->getID($id, DATASET_TYPE_ARRAY);
        // Discussion is not deleted on update.
        $this->assertSame($id, $dbRecord["DiscussionID"]);
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultCommentSpam(): void
    {
        $this->createDiscussion();

        $comment = $this->createComment();
        $id = $comment["commentID"];

        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $isSpam = \SpamModel::isSpam("Comment", [
            "Name" => __FUNCTION__,
            "Body" => __FUNCTION__,
            "CommentID" => $id,
        ]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["Operation" => "Spam", "RecordType" => "Comment", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
        $commentModel = $this->container()->get(\CommentModel::class);
        $dbRecord = $commentModel->getID($id, DATASET_TYPE_ARRAY);
        $this->assertSame(false, $dbRecord);
    }

    /**
     * By default, a spam record should go to the spam queue.
     */
    public function testDefaultSpamCommentNotDeletedOnUpdate(): void
    {
        $this->createDiscussion();
        $comment = $this->createComment();
        $id = $comment["commentID"];

        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $isSpam = \SpamModel::isSpam(
            "Comment",
            ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id, "CommentID" => $id],
            ["action" => "update"]
        );
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["Operation" => "Spam", "RecordType" => "Comment", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_SPAM, $row["Operation"]);
        $commentModel = $this->container()->get(\CommentModel::class);
        $dbRecord = $commentModel->getID($id, DATASET_TYPE_ARRAY);
        // Comment is not deleted on update.
        $this->assertSame($id, $dbRecord["CommentID"]);
    }

    /**
     * Addons should be able to change the type of log to moderation.
     */
    public function testChangeLogOperation(): void
    {
        $this->resetTable("Log");
        $fn = function (\SpamModel $sender, $args) {
            $sender->EventArguments["IsSpam"] = true;
            $sender->EventArguments["Options"]["Operation"] = \LogModel::TYPE_MODERATE;
        };

        $this->eventManager->bind(self::EVENT_NAME, $fn);

        $id = self::id();
        $isSpam = \SpamModel::isSpam("Discussion", ["Name" => __FUNCTION__, "Body" => __FUNCTION__, "RecordID" => $id]);
        $this->assertTrue($isSpam);

        $r = $this->logModel->getWhere(["RecordType" => "Discussion", "RecordID" => $id]);
        $this->assertCount(1, $r);

        $row = $r[0];
        $this->assertSame(\LogModel::TYPE_MODERATE, $row["Operation"]);
    }
}

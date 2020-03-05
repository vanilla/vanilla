<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;
use Garden\EventManager;
use Vanilla\Community\Events\CommentEvent;

/**
 * Test {@link CommentModel}.
 */
class CommentModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        setupBeforeClass as baseSetupBeforeClass;
    }

    /** @var CommentEvent */
    private $lastEvent;

    /**
     * @var \DiscussionModel
     */
    private $discussionModel;

    /**
     * @var \CommentModel
     */
    private $commentModel;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::baseSetupBeforeClass();

        // Test as an admin
        self::container()->get('Session')->start(self::$siteInfo['adminUserID']);
    }

    /**
     * Setup
     */
    public function setup(): void {
        $this->commentModel = $this->container()->get(\CommentModel::class);
        $this->discussionModel = $this->container()->get(\DiscussionModel::class);
        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleCommentEvent");
    }

    /**
     * A test listener that increments the counter.
     *
     * @param TestEvent $e
     * @return TestEvent
     */
    public function handleCommentEvent(CommentEvent $e): CommentEvent {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Test the lookup method.
     */
    public function testLookup() {
        $discussion = [
            'CategoryID' => 1,
            'Name' => 'Comment Lookup Test',
            'Body' => 'foo foo foo',
            'Format' => 'Text',
            'InsertUserID' => 1
        ];
        $discussionID = $this->discussionModel->save($discussion);

        $comment = [
            'DiscussionID' => $discussionID,
            'Body' => 'Hello world.',
            'Format' => 'Text'
        ];
        $commentID = $this->commentModel->save($comment);
        $this->assertNotFalse($commentID);

        $result = $this->commentModel->lookup(['CommentID' => $commentID] + $comment);
        $this->assertInstanceOf('Gdn_DataSet', $result);
        $this->assertEquals(1, $result->count());

        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($commentID, $row['CommentID']);
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void {
        $discussion = [
            'CategoryID' => 1,
            'Name' => 'test delete event',
            'Body' => 'foo foo foo',
            'Format' => 'Text',
            'InsertUserID' => 1
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
    public function testSaveInsertEventDispatched(): void {
        $discussion = [
            'CategoryID' => 1,
            'Name' => 'test insert',
            'Body' => 'foo foo foo',
            'Format' => 'Text',
            'InsertUserID' => 1
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
    public function testSaveUpdateEventDispatched(): void {
        $discussion = [
            'CategoryID' => 1,
            'Name' => 'test update',
            'Body' => 'foo foo foo',
            'Format' => 'Text',
            'InsertUserID' => 1
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
}

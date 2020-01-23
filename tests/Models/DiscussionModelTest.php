<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use DiscussionModel;
use Garden\EventManager;
use Gdn;
use PHPUnit\Framework\TestCase;
use Vanilla\Community\Events\DiscussionEvent;
use VanillaTests\ExpectErrorTrait;
use VanillaTests\SiteTestTrait;

/**
 * Some basic tests for the `DiscussionModel`.
 */
class DiscussionModelTest extends TestCase {
    use SiteTestTrait, ExpectErrorTrait;

    /** @var DiscussionEvent */
    private $lastEvent;

    /**
     * @var \DiscussionModel
     */
    private $model;

    /**
     * @var \DateTimeImmutable
     */
    private $now;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * A test listener that increments the counter.
     *
     * @param TestEvent $e
     * @return TestEvent
     */
    public function handleDiscussionEvent(DiscussionEvent $e): DiscussionEvent {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Get a new model for each test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->model = $this->container()->get(\DiscussionModel::class);
        $this->now = new \DateTimeImmutable();
        $this->session = Gdn::session();

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleDiscussionEvent");
    }

    /**
     * An empty archive date should be null.
     */
    public function testArchiveDateEmpty() {
        $this->model->setArchiveDate('');
        $this->assertNull($this->model->getArchiveDate());
    }

    /**
     * A date expression is valid.
     */
    public function testDayInPast() {
        $this->model->setArchiveDate('-3 days');
        $this->assertLessThan($this->now, $this->model->getArchiveDate());
    }

    /**
     * A future date expression gets flipped to the past.
     */
    public function testDayFlippedToPast() {
        $this->model->setArchiveDate('3 days');
        $this->assertLessThan($this->now, $this->model->getArchiveDate());
    }

    /**
     * An invalid archive date should throw an exception.
     */
    public function testInvalidArchiveDate() {
        $this->expectException(\Exception::class);

        $this->model->setArchiveDate('dnsfids');
    }

    /**
     * Test `DiscussionModel::isArchived()`.
     *
     * @param string $archiveDate
     * @param string|null $dateLastComment
     * @param bool $expected
     * @dataProvider provideIsArchivedTests
     */
    public function testIsArchived(string $archiveDate, ?string $dateLastComment, bool $expected) {
        $this->model->setArchiveDate($archiveDate);
        $actual = $this->model->isArchived($dateLastComment);
        $this->assertSame($expected, $actual);
    }

    /**
     * An invalid date should return a warning.
     */
    public function testIsArchivedInvalidDate() {
        $this->model->setArchiveDate('2019-10-26');

        $this->runWithExpectedError(function () {
            $actual = $this->model->isArchived('fldjsjs');
            $this->assertFalse($actual);
        }, self::assertErrorNumber(E_USER_WARNING));
    }

    /**
     * Provide some tests for `DiscussionModel::isArchived()`.
     *
     * @return array
     */
    public function provideIsArchivedTests(): array {
        $r = [
            ['2000-01-01', '2019-10-26', false],
            ['2000-01-01', '1999-12-31', true],
            ['2001-01-01', '2001-01-01', false],
            ['', '1999-01-01', false],
            ['2001-01-01', null, false],
        ];

        return $r;
    }


    /**
     * Test canClose() where Admin is false and user has CloseOwn permission.
     */
    public function testCanCloseAdminFalseCloseOwnTrue() {
        $this->session->UserID = 123;
        $this->session->getPermissions()->set('Vanilla.Discussions.CloseOwn', $this->session->UserID);
        $this->session->getPermissions()->setAdmin(false);
        $discussion = [
            'DiscussionID' => 0,
            'CategoryID' => 1,
            'Name' => 'test',
            'Body' => 'discuss',
            'InsertUserID' => 123
        ];
        $actual = DiscussionModel::canClose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() where Admin is false and user has CloseOwn permission but user did not start the discussion.
     */
    public function testCanCloseCloseOwnTrueNotOwn() {
        $this->session->UserID = 123;
        $this->session->getPermissions()->set('Vanilla.Discussions.CloseOwn', $this->session->UserID);
        $this->session->getPermissions()->setAdmin(false);
        $discussion = [
            'DiscussionID' => 0,
            'CategoryID' => 1,
            'Name' => 'test',
            'Body' => 'discuss',
            'InsertUserID' => 321
        ];
        $actual = DiscussionModel::canClose($discussion);
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() with discussion already closed and user didn't start the discussion.
     */
    public function testCanCloseCloseIsClosed() {
        $this->session->UserID = 123;
        $this->session->getPermissions()->set('Vanilla.Discussions.CloseOwn', $this->session->UserID);
        $this->session->getPermissions()->setAdmin(false);
        $discussion = [
            'DiscussionID' => 0,
            'CategoryID' => 1,
            'Name' => 'test',
            'Body' => 'discuss',
            'InsertUserID' => 321,
            'Closed' => true,
            'Attributes' => ['ClosedByUserID' => 321]
        ];
        $actual = DiscussionModel::canClose($discussion);
        $expected = false;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() where Admin is true.
     */
    public function testCanCloseAdminTrue() {
        $this->session->UserID = 123;
        $discussion = ['DiscussionID' => 0, 'CategoryID' => 1, 'Name' => 'test', 'Body' => 'discuss', 'InsertUserID' => 123];
        $actual = DiscussionModel::canCLose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() with discussion object.
     */
    public function testCanCloseDiscussionObject() {
        $this->session->UserID = 123;
        $discussion = new \stdClass();
        $discussion->DiscussionID = 0;
        $discussion->CategoryID = 1;
        $discussion->Name = 'test';
        $discussion->Body = 'discuss';
        $discussion->InsertUserID = 123;
        $actual = DiscussionModel::canClose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void {
        $discussionID = $this->model->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->model->deleteID($discussionID);

        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void {
        $this->model->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_INSERT, $this->lastEvent->getAction());
    }

    /**
     * Verify update event dispatched during save.
     *
     * @return void
     */
    public function testSaveUpdateEventDispatched(): void {
        $discussionID = $this->model->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->model->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello again, world.",
        ]);

        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }
}

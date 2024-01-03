<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use ActivityModel;
use CategoryModel;
use DiscussionModel;
use Exception;
use Garden\EventManager;
use Garden\Events\BulkUpdateEvent;
use Gdn;
use RoleModel;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerResult;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use VanillaTests\Bootstrap;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Some basic tests for the `DiscussionModel`.
 */
class DiscussionModelTest extends SiteTestCase
{
    use ExpectExceptionTrait,
        TestDiscussionModelTrait,
        EventSpyTestTrait,
        TestCategoryModelTrait,
        CommunityApiTestTrait,
        UsersAndRolesApiTestTrait,
        TestCommentModelTrait,
        SchedulerTestTrait;

    /** @var DiscussionEvent */
    private $lastEvent;

    /**
     * @var \DateTimeImmutable
     */
    private $now;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * @var array
     */
    private $publicCategory;

    /**
     * @var array
     */
    private $privateCategory;

    /**
     * @var array
     */
    private static $data = [];

    /**
     * @var ActivityModel
     */

    private $activityModel;
    /**
     * A test listener that increments the counter.
     *
     * @param DiscussionEvent $e
     * @return DiscussionEvent
     */
    public function handleDiscussionEvent(DiscussionEvent $e): DiscussionEvent
    {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Get a new model for each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->now = new \DateTimeImmutable();
        $this->session = Gdn::session();

        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleDiscussionEvent");

        $this->publicCategory = $this->insertCategories(1, ["UrlCode" => "public-%s"])[0];
        $this->insertDiscussions(2, ["CategoryID" => $this->publicCategory["CategoryID"]]);

        $this->privateCategory = $this->insertPrivateCategory(
            [$this->roleID(Bootstrap::ROLE_ADMIN), $this->roleID(Bootstrap::ROLE_MOD)],
            ["UrlCode" => "private-%s"]
        );
        $this->insertDiscussions(3, ["CategoryID" => $this->privateCategory["CategoryID"]]);
        DiscussionModel::cleanForTests();
        $this->activityModel = Gdn::getContainer()->get(ActivityModel::class);
    }

    /**
     * Test DiscussionModel::save() merging validations.
     */
    public function testDiscussionSaveValidationMerge(): void
    {
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->bind("discussionmodel_beforesavediscussion", [$this, "handleDiscussionSaveValidationMerge"]);

        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "valid discussion",
            "Format" => "markdown",
        ]);
        $validationResults = $this->discussionModel->Validation->results();
        $this->assertEmpty($discussionID);
        $this->assertEquals($validationResults["Body"], ["test validate discussion"]);
    }

    /**
     * Handler for discussionmodel_beforesavediscussion.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function handleDiscussionSaveValidationMerge(DiscussionModel $sender, array $args): void
    {
        $sender->Validation->addValidationResult("Body", "test validate discussion");
    }

    /**
     * An empty archive date should be null.
     */
    public function testArchiveDateEmpty()
    {
        $this->discussionModel->setArchiveDate("");
        $this->assertNull($this->discussionModel->getArchiveDate());
    }

    /**
     * A date expression is valid.
     */
    public function testDayInPast()
    {
        $this->discussionModel->setArchiveDate("-3 days");
        $this->assertLessThan($this->now, $this->discussionModel->getArchiveDate());
    }

    /**
     * A future date expression gets flipped to the past.
     */
    public function testDayFlippedToPast()
    {
        $this->discussionModel->setArchiveDate("3 days");
        $this->assertLessThan($this->now, $this->discussionModel->getArchiveDate());
    }

    /**
     * An invalid archive date should throw an exception.
     */
    public function testInvalidArchiveDate()
    {
        $this->expectException(Exception::class);

        $this->discussionModel->setArchiveDate("dnsfids");
    }

    /**
     * Test `DiscussionModel::isArchived()`.
     *
     * @param string $archiveDate
     * @param string|null $dateLastComment
     * @param bool $expected
     * @dataProvider provideIsArchivedTests
     */
    public function testIsArchived(string $archiveDate, ?string $dateLastComment, bool $expected)
    {
        $this->discussionModel->setArchiveDate($archiveDate);
        $actual = $this->discussionModel->isArchived($dateLastComment);
        $this->assertSame($expected, $actual);
    }

    /**
     * An invalid date should return a warning.
     */
    public function testIsArchivedInvalidDate()
    {
        $this->discussionModel->setArchiveDate("2019-10-26");

        $actual = @$this->discussionModel->isArchived("fldjsjs");
        $this->assertFalse($actual);

        $this->expectWarning();
        $this->discussionModel->isArchived("fldjsjs");
    }

    /**
     * Provide some tests for `DiscussionModel::isArchived()`.
     *
     * @return array
     */
    public function provideIsArchivedTests(): array
    {
        $r = [
            ["2000-01-01", "2019-10-26", false],
            ["2000-01-01", "1999-12-31", true],
            ["2001-01-01", "2001-01-01", false],
            ["", "1999-01-01", false],
            ["2001-01-01", null, false],
        ];

        return $r;
    }

    /**
     * Test canClose() where Admin is false and user has CloseOwn permission.
     */
    public function testCanCloseAdminFalseCloseOwnTrue()
    {
        $this->session->UserID = 123;
        $this->session->getPermissions()->set("Vanilla.Discussions.CloseOwn", $this->session->UserID);
        $this->session->getPermissions()->setAdmin(false);
        $discussion = [
            "DiscussionID" => 0,
            "CategoryID" => 1,
            "Name" => "test",
            "Body" => "discuss",
            "InsertUserID" => 123,
        ];
        $actual = DiscussionModel::canClose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() where Admin is false and user has CloseOwn permission but user did not start the discussion.
     */
    public function testCanCloseCloseOwnTrueNotOwn()
    {
        $this->runWithUser(function () {
            $this->session->getPermissions()->set("Vanilla.Discussions.CloseOwn", true);
            $this->session->getPermissions()->setAdmin(false);
            $discussion = [
                "DiscussionID" => 0,
                "CategoryID" => 1,
                "Name" => "test",
                "Body" => "discuss",
                "InsertUserID" => 321,
            ];
            $actual = DiscussionModel::canClose($discussion);
            $expected = false;
            $this->assertSame($expected, $actual);
        }, 123);
    }

    /**
     * Test canClose() with discussion already closed and user didn't start the discussion.
     */
    public function testCanCloseCloseIsClosed()
    {
        $this->runWithUser(function () {
            $this->session->getPermissions()->set("Vanilla.Discussions.CloseOwn", true);
            $this->session->getPermissions()->setAdmin(false);
            $discussion = [
                "DiscussionID" => 0,
                "CategoryID" => 1,
                "Name" => "test",
                "Body" => "discuss",
                "InsertUserID" => 321,
                "Closed" => true,
                "Attributes" => ["ClosedByUserID" => 321],
            ];
            $actual = DiscussionModel::canClose($discussion);
            $expected = false;
            $this->assertSame($expected, $actual);
        }, 123);
    }

    /**
     * Test canClose() where Admin is true.
     */
    public function testCanCloseAdminTrue()
    {
        $this->session->UserID = 123;
        $discussion = [
            "DiscussionID" => 0,
            "CategoryID" => 1,
            "Name" => "test",
            "Body" => "discuss",
            "InsertUserID" => 123,
        ];
        $actual = DiscussionModel::canClose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Test canClose() with discussion object.
     */
    public function testCanCloseDiscussionObject()
    {
        $this->session->UserID = 123;
        $discussion = new \stdClass();
        $discussion->DiscussionID = 0;
        $discussion->CategoryID = 1;
        $discussion->Name = "test";
        $discussion->Body = "discuss";
        $discussion->InsertUserID = 123;
        $actual = DiscussionModel::canClose($discussion);
        $expected = true;
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for maxDate().
     */

    /**
     * $dateOne > $dateTwo
     */
    public function testMaxDateDateOneGreater()
    {
        $dateOne = "2020-01-09 16:22:42";
        $dateTwo = "2019-12-02 21:55:40";
        $expected = $dateOne;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateTwo > $dateOne
     */
    public function testMaxDateDateTwoGreater()
    {
        $dateOne = "2019-12-02 21:55:40";
        $dateTwo = "2020-01-09 16:22:42";
        $expected = $dateTwo;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateOne is null
     */
    public function testMaxDateDateOneNull()
    {
        $dateOne = null;
        $dateTwo = "2020-01-09 16:22:42";
        $expected = $dateTwo;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateTwo is null
     */
    public function testMaxDateDateTwoNull()
    {
        $dateOne = "2020-01-09 16:22:42";
        $dateTwo = null;
        $expected = $dateOne;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * Both dates are null
     */
    public function testMaxDateWithTwoNullValues()
    {
        $dateOne = null;
        $dateTwo = null;
        $expected = null;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests for calculateWatch().
     *
     * @param object|array $testDiscussionArray Data to plug into discussion object.
     * @param int $testLimit Max number to get.
     * @param int $testOffset Number to skip.
     * @param int $testTotalComments Total in entire discussion (hard limit).
     * @param string|null $testMaxDateInserted The most recent insert date of the viewed comments.
     * @param array $expected The expected result.
     * @dataProvider provideTestCalculateWatchArrays
     * @throws Exception Throws an exception if given an invalid timestamp.
     */
    public function testCalculateWatch(
        $testDiscussionArray,
        int $testLimit,
        int $testOffset,
        int $testTotalComments,
        ?string $testMaxDateInserted,
        $expected
    ) {
        $discussion = (object) $testDiscussionArray;
        $actual = $this->discussionModel->calculateWatch(
            $discussion,
            $testLimit,
            $testOffset,
            $testTotalComments,
            $testMaxDateInserted
        );
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testCalculateWatch}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestCalculateWatchArrays()
    {
        $r = [
            "Unread Discussion With No Comments" => [
                [
                    "DateLastViewed" => null,
                    "CountCommentWatch" => null,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-17 19:20:02",
                    "Bookmarked" => 0,
                ],
                30,
                0,
                0,
                null,
                [0, "2020-01-17 19:20:02", "insert"],
            ],
            "Unread Discussion with One Comment" => [
                [
                    "DateLastViewed" => null,
                    "CountCommentWatch" => null,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-18 19:20:02",
                    "Bookmarked" => 0,
                ],
                30,
                0,
                1,
                "2020-01-18 19:20:02",
                [1, "2020-01-18 19:20:02", "insert"],
            ],
            "Unread Discussion with One More Total Comments than the Limit" => [
                [
                    "DateLastViewed" => null,
                    "CountCommentWatch" => null,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-19 19:20:02",
                    "Bookmarked" => 0,
                ],
                30,
                0,
                31,
                "2020-01-18 19:20:02",
                [30, "2020-01-18 19:20:02", "insert"],
            ],
            "Read Discussion with No Comments" => [
                [
                    "DateLastViewed" => "2020-01-17 19:20:02",
                    "CountCommentWatch" => 0,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-17 19:20:02",
                    "Bookmarked" => 0,
                    "WatchUserID" => 1,
                ],
                30,
                0,
                0,
                "2020-01-17 19:20:02",
                [0, "2020-01-17 19:20:02", null],
            ],
            "Read Discussion with New Comments" => [
                [
                    "DateLastViewed" => "2020-01-18 19:20:02",
                    "CountCommentWatch" => 5,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-19 19:20:02",
                    "Bookmarked" => 0,
                    "WatchUserID" => 1,
                ],
                30,
                5,
                20,
                "2020-01-19 19:20:02",
                [20, "2020-01-19 19:20:02", "update"],
            ],
            "User Has Read Page One, but not Page Two" => [
                [
                    "DateLastViewed" => "2020-01-18 19:20:02",
                    "CountCommentWatch" => 30,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-19 19:20:02",
                    "Bookmarked" => 0,
                    "WatchUserID" => 1,
                ],
                30,
                30,
                31,
                "2020-01-19 19:20:02",
                [31, "2020-01-19 19:20:02", "update"],
            ],
            "Comments Read is Greater than Total Comments" => [
                [
                    "DateLastViewed" => "2020-01-18 19:20:02",
                    "CountCommentWatch" => 6,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-18 19:20:02",
                    "Bookmarked" => 0,
                    "WatchUserID" => 1,
                ],
                30,
                5,
                5,
                "DateLastComment" => "2020-01-18 19:20:02",
                [5, "2020-01-18 19:20:02", "update"],
            ],
            "Discussion Bookmarked Before Viewed" => [
                [
                    "DateLastViewed" => null,
                    "CountCommentWatch" => null,
                    "DateInserted" => "2020-01-17 19:20:02",
                    "DateLastComment" => "2020-01-18 19:20:02",
                    "Bookmarked" => 1,
                    "WatchUserID" => 1,
                ],
                30,
                0,
                0,
                "DateLastComment" => "2020-01-18 19:20:02",
                [0, "2020-01-18 19:20:02", "update"],
            ],
        ];

        return $r;
    }

    /**
     * Test {@link calculateCommentReadData()} against various scenarios.
     *
     * @param int $discussionCommentCount The number of comments in the discussion according to the Discussion Table.
     * @param string|null $discussionLastCommentDate Date of last Comment according to the Discussion table.
     * @param int|null $userReadComments Number of Comments the user has read according to the UserDiscussion table.
     * @param string|null $userLastReadDate Date of last Comment read according to the UserDiscussion table.
     * @param array $expected The expected result.
     * @dataProvider provideTestCalculateCommentReadData
     */
    public function testCalculateCommentReadData(
        int $discussionCommentCount,
        ?string $discussionLastCommentDate,
        ?int $userReadComments,
        ?string $userLastReadDate,
        $expected
    ) {
        $actual = $this->discussionModel->calculateCommentReadData(
            $discussionCommentCount,
            $discussionLastCommentDate,
            $userReadComments,
            $userLastReadDate
        );
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for testCalculateCommentReadData().
     *
     * @return array Returns an array of test data.
     */
    public function provideTestCalculateCommentReadData()
    {
        $r = [
            "discussionLastCommentDateIsNull" => [10, null, null, "2020-01-09 16:22:42", [true, 0]],
            "userReadCommentIsNullWithReadDate" => [10, "2019-12-02 21:55:40", null, "2020-01-09 16:22:42", [true, 0]],
            "userReadCommentIsNullWithUnreadDate" => [
                10,
                "2020-01-09 16:22:42",
                null,
                "2019-12-02 21:55:40",
                [false, 1],
            ],
            "userReadCommentsIsNullWithoutReadDate" => [10, "2019-12-02 21:55:40", null, null, [false, true]],
            "CommentsAndUserReadEqualDatesConcur" => [10, "2019-12-02 21:55:40", 10, "2020-01-09 16:22:42", [true, 0]],
            "CommentsAndUserReadEqualDatesDisagree" => [
                10,
                "2020-01-09 16:22:42",
                10,
                "2019-12-02 21:55:40",
                [false, 1],
            ],
            "MoreCommentsThanReadDatesAgree" => [15, "2020-01-09 16:22:42", 10, "2019-12-02 21:55:40", [false, 5]],
            "MoreCommentsThanReadDatesDisagreeOnePage" => [
                15,
                "2019-12-02 21:55:40",
                10,
                "2020-01-09 16:22:42",
                [true, 0],
            ],
            "MoreReadThanCommentsLastCommentLater" => [5, "2020-01-09 16:22:42", 10, "2019-12-02 21:55:40", [false, 1]],
            "MoreReadThanCommentsLastCommentEarlier" => [
                5,
                "2019-12-02 21:55:40",
                10,
                "2020-01-09 16:22:42",
                [true, 0],
            ],
            "ReadCommentsNoDiscussionCommentsDiscussionNotRead" => [
                0,
                "2020-01-09 16:22:42",
                50,
                "2019-12-02 21:55:40",
                [false, true],
            ],
            "NullUserReadDateNoReadComments" => [5, "2020-01-09 16:22:42", 0, null, [false, 5]],
            "NullUserCountWithReadComments" => [5, "2020-01-09 16:22:42", 10, null, [false, 1]],
        ];

        return $r;
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void
    {
        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->discussionModel->deleteID($discussionID);

        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void
    {
        $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertTrackablePayload($this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_INSERT, $this->lastEvent->getAction());
    }

    /**
     * Verify update event dispatched during save.
     *
     * @return void
     */
    public function testSaveUpdateEventDispatched(): void
    {
        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
        ]);
        $this->discussionModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello again, world.",
        ]);

        $this->assertInstanceOf(DiscussionEvent::class, $this->lastEvent);
        $this->assertEquals(DiscussionEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }

    /**
     * Test that changing a discussions category triggers a bulk update for all of it's comments.
     */
    public function testChangeTriggersBulkUpdate()
    {
        $this->session->getPermissions()->setAdmin(true);
        $discussionID = $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "CategoryID" => 1,
        ]);
        $commentModel = new \CommentModel();
        $commentID = $commentModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "DiscussionID" => $discussionID,
        ]);
        $this->clearDispatchedEvents();

        // No change.
        $this->discussionModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "CategoryID" => 1,
        ]);
        $this->assertNoEventsDispatched(BulkUpdateEvent::class);

        // Changed
        $this->discussionModel->save([
            "DiscussionID" => $discussionID,
            "Body" => "Hello world.",
            "CategoryID" => -1,
        ]);
        $this->assertBulkEventDispatched(
            new BulkUpdateEvent(
                "comment",
                [
                    "discussionID" => (int) $discussionID,
                ],
                [
                    "categoryID" => -1,
                ]
            )
        );
    }

    /**
     * Test inserting and updating a user's watch status of comments in a discussion.
     *
     * @return void
     * @throws Exception Throws an exception if given an invalid timestamp.
     */
    public function testSetWatch(): void
    {
        $this->session->start(self::$siteInfo["adminUserID"], false, false);

        $countComments = 5;
        $discussion = [
            "CategoryID" => 1,
            "Name" => "Comment Watch Test",
            "Body" => "foo bar baz",
            "Format" => "Text",
            "CountComments" => $countComments,
            "InsertUserID" => 1,
        ];

        // Confirm the initial state, so changes are easy to detect.
        $discussionID = $this->discussionModel->save($discussion);
        $this->assertNotEmpty($discussionID, $this->discussionModel->Validation->resultsText());
        $discussion = $this->discussionModel->getID($discussionID);
        $this->assertIsObject($discussion);
        $this->assertNull($discussion->CountCommentWatch, "Initial comment watch status not null.");

        // Create a comment watch status.
        $this->discussionModel->setWatch($discussion, 10, 0, $discussion->CountComments);
        $discussionFirstVisit = $this->discussionModel->getID($discussionID);
        $this->assertSame(
            $discussionFirstVisit->CountComments,
            $discussionFirstVisit->CountCommentWatch,
            "Creating new comment watch status failed."
        );

        // Update an existing comment watch status.
        $updatedCountComments = $countComments + 1;
        $this->discussionModel->setField($discussionID, "CountComments", $updatedCountComments);
        $this->discussionModel->setWatch($discussionFirstVisit, 10, 0, $updatedCountComments);
        $discussionSecondVisit = $this->discussionModel->getID($discussionID);
        $this->assertSame(
            $discussionSecondVisit->CountComments,
            $discussionSecondVisit->CountCommentWatch,
            "Updating comment watch status failed."
        );
    }

    /**
     * Test calculate() with various category marked read discussion dates.
     *
     * @param string $discussionInserted
     * @param string|null $discussionMarkedRead
     * @param string|null $categoryMarkedRead
     * @param string|null $expected
     * @dataProvider provideMarkedRead
     */
    public function testDiscussionCategoryMarkedRead(
        string $discussionInserted,
        ?string $discussionMarkedRead,
        ?string $categoryMarkedRead,
        ?string $expected
    ): void {
        // Set up a CategoryModel instance to test.
        CategoryModel::$Categories = [
            100 => [
                "Name" => "foo",
                "UrlCode" => "foo",
                "PermissionCategoryID" => 1,
                "DateMarkedRead" => $categoryMarkedRead,
            ],
        ];

        $discussion = (object) [
            "DiscussionID" => 0,
            "CategoryID" => 100,
            "Name" => "test",
            "Body" => "discuss",
            "InsertUserID" => 123,
            "DateInserted" => $discussionInserted,
            "Url" => "bar",
            "Attributes" => [],
            "Tags" => [],
            "LastCommentUserID" => 234,
            "CountComments" => 5,
            "DateLastComment" => "2020-01-01 16:22:42",
            "DateLastViewed" => $discussionMarkedRead,
            "CountCommentWatch" => $discussionMarkedRead ? 5 : null,
        ];

        $this->discussionModel->calculate($discussion);

        $this->assertSame($expected, $discussion->DateLastViewed);

        // Reset that static property.
        CategoryModel::$Categories = null;
    }

    /**
     * Provide data for testing date-marked-read calculations.
     *
     * @return array
     */
    public function provideMarkedRead(): array
    {
        $result = [
            "Discussion unread, category unread" => [
                "2020-01-01 00:00:00", // Discussion.DateInserted
                null, // Discussion.DateLastViewed
                null, // Category.DateMarkedRead
                null, // Expected value.
            ],
            "Discussion read, category unread." => [
                "2020-01-01 00:00:00",
                "2020-01-08 00:00:00",
                null,
                "2020-01-08 00:00:00",
            ],
            "Discussion read, category read more recently." => [
                "2020-01-01 00:00:00",
                "2020-01-08 00:00:00",
                "2020-01-10 00:00:00",
                "2020-01-10 00:00:00",
            ],
            "Discussion read, category read prior." => [
                "2020-01-01 00:00:00",
                "2020-01-22 00:00:00",
                "2020-01-08 00:00:00",
                "2020-01-22 00:00:00",
            ],
            "Discussion read, category read before discussion created." => [
                "2020-01-01 00:00:00",
                "2020-01-08 00:00:00",
                "2019-12-25 00:00:00",
                "2020-01-08 00:00:00",
            ],
            "Discussion unread, category read after discussion created." => [
                "2020-01-01 00:00:00",
                null,
                "2020-01-15 00:00:00",
                "2020-01-15 00:00:00",
            ],
            "Discussion unread, category read before discussion created." => [
                "2020-01-22 00:00:00",
                null,
                "2020-01-15 00:00:00",
                null,
            ],
        ];
        return $result;
    }

    /**
     * Announcements should properly sort.
     */
    public function testAnnouncementSorting()
    {
        $row = ["Name" => "ax1", "Announce" => 1];
        $this->insertDiscussions(10, $row);

        $rows = $this->discussionModel->getAnnouncements($row, 0, false)->resultArray();
        self::assertSorted($rows, "-DateLastComment");
    }

    /**
     * Test DiscussionTypes()
     */
    public function testDiscussionTypes()
    {
        $discussionTypes = $this->discussionModel::discussionTypes();
        $this->assertSame(
            [
                "Discussion" => [
                    "apiType" => "discussion",
                    "Singular" => "Discussion",
                    "Plural" => "Discussions",
                    "AddUrl" => "/post/discussion",
                    "AddText" => "New Discussion",
                    "AddIcon" => "new-discussion",
                ],
            ],
            $discussionTypes
        );
    }

    /**
     * An admin should be able to see everything.
     *
     * @return int
     */
    public function testDiscussionCountAsFullAdmin(): int
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_ADMIN);
        $this->session->start($userID);
        $adminCountAllowed = $this->discussionModel->getCount([
            "d.CategoryID" => [$this->publicCategory["CategoryID"], $this->privateCategory["CategoryID"]],
        ]);
        $this->assertSame(5, $adminCountAllowed);

        return $adminCountAllowed;
    }

    /**
     * A user without access to a category should not see it included in discussion counts.
     *
     * @param int $adminCountAllowed
     * @depends testDiscussionCountAsFullAdmin
     */
    public function testDiscussionCountWithOneUnviewableCategory($adminCountAllowed): void
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER);
        $this->session->start($userID);

        $memberCountAllowed = $this->discussionModel->getCount([
            "d.CategoryID" => [$this->publicCategory["CategoryID"], $this->privateCategory["CategoryID"]],
        ]);
        $this->assertSame(2, $memberCountAllowed);
        $this->assertLessThan($adminCountAllowed, $memberCountAllowed);
    }

    /**
     * Admin with no parameters provided to getCount(), as in getting recent discussions.
     *
     * @return int
     */
    public function testDiscussionCountRecentDiscussionsAdmin(): int
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_ADMIN);
        $this->session->start($userID);
        $allCategories = DiscussionModel::categoryPermissions();
        $adminCountAllowed = $this->discussionModel->getCount();
        $this->assertDiscussionCountsFromDb($allCategories, $adminCountAllowed);

        return $adminCountAllowed;
    }

    /**
     * Member with no parameters provided to getCount(), as in getting recent discussions.
     *
     * @param int $adminCountAllowed
     * @depends testDiscussionCountRecentDiscussionsAdmin
     */
    public function testDiscussionCountRecentDiscussionsMember($adminCountAllowed): void
    {
        $userID = $this->createUserFixture(Bootstrap::ROLE_MEMBER);
        $this->session->start($userID);
        $allCategories = DiscussionModel::categoryPermissions();
        $memberCountAllowed = $this->discussionModel->getCount();
        $this->assertDiscussionCountsFromDb($allCategories, $memberCountAllowed);
        $this->assertLessThan($adminCountAllowed, $memberCountAllowed);
    }

    /**
     * Smoke test DiscussionModel::getByUser()
     */
    public function testgetByUser(): void
    {
        $memberUserID = $this->createUserFixture(VanillaTestCase::ROLE_MEMBER);
        $countDiscussionsMember = $this->discussionModel->getCount(["d.InsertUserID" => $memberUserID]);
        $adminUserID = $this->createUserFixture(VanillaTestCase::ROLE_ADMIN);
        $this->session->start($adminUserID);
        $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world Admin.",
            "Format" => "markdown",
            "CategoryID" => $this->privateCategory["CategoryID"],
        ]);

        $this->session->start($memberUserID);
        $this->discussionModel->save([
            "Name" => __FUNCTION__,
            "Body" => "Hello world Member",
            "Format" => "markdown",
            "CategoryID" => $this->publicCategory["CategoryID"],
        ]);

        $discussionsMember = $this->discussionModel->getByUser(
            $memberUserID,
            10,
            0,
            false,
            $memberUserID,
            "PermsDiscussionsView"
        );
        $discussionsMemberRows = $discussionsMember->numRows();
        $this->assertEquals($discussionsMemberRows, $discussionsMemberRows + $countDiscussionsMember);
    }

    /**
     * Test a dirty-record is added when calling setField.
     */
    public function testDirtyRecordAdded()
    {
        $discussion = $this->insertDiscussions(1);
        $id = $discussion[0]["DiscussionID"];
        $this->discussionModel->setField($id, "Announce", 1);
        $this->assertDirtyRecordInserted("discussion", $id);
    }

    /**
     * Test participation count.
     */
    public function testParticipatedCount()
    {
        $this->resetTable("Comment");
        $user2 = $this->createUser();
        $this->createCategory();
        $disc1 = $this->createDiscussion();
        $disc2 = $this->createDiscussion();
        $this->runWithUser(function () use ($disc1, $disc2) {
            $this->createComment(["discussionID" => $disc1["discussionID"]]);
            $this->createComment(["discussionID" => $disc2["discussionID"]]);
            $this->assertEquals(2, $this->discussionModel->getCountParticipated());
        }, $user2);

        $this->assertEquals(0, $this->discussionModel->getCountParticipated());
        $this->assertEquals(2, $this->discussionModel->getCountParticipated($user2["userID"]));
    }

    /**
     * Smoke test `DiscussionModel::resolveDiscussionArg()`.
     */
    public function testResolveDiscussionArg(): void
    {
        $expected = $this->insertDiscussions(1)[0];

        [$id, $actual] = $this->discussionModel->resolveDiscussionArg($expected);
        $this->assertSame($expected, $actual);
        $this->assertSame($expected["DiscussionID"], $id);

        // These fields aren't necessary for comparison and are inconsistent between `DiscussionModel::getWhere()` and `DiscussionModel::getID()`.
        unset($expected["FirstEmail"], $expected["FirstName"], $expected["FirstPhoto"], $expected["WatchUserID"]);

        [$id2, $actual2] = $this->discussionModel->resolveDiscussionArg($expected["DiscussionID"]);
        $this->assertArraySubsetRecursive($expected, $actual2);
        $this->assertSame($expected["DiscussionID"], $id2);
    }

    /**
     * Assert that HTML encoded titles directly out of the models are fixed in the API.
     *
     * @see https://github.com/vanilla/support/issues/4044
     */
    public function testDiscussionEncoding()
    {
        $in = 'Hello name with "quotes" & ampersand';
        $this->createDiscussion(["name" => $in]);
        $fetchedBack = $this->api()
            ->get("/discussions/{$this->lastInsertedDiscussionID}")
            ->getBody();
        $this->assertEquals($in, $fetchedBack["name"]);
    }

    /**
     * Test DiscussionModel::FilterCategoryPermissions().
     */
    public function testFilterCategoryPermissions(): void
    {
        $discussion1 = $this->createDiscussion();
        $discussion2 = $this->createDiscussion();
        $discussionIDs = [$discussion1["discussionID"], $discussion2["discussionID"]];
        $result = $this->discussionModel->filterCategoryPermissions($discussionIDs, "Vanilla.Discussions.Delete");
        $this->assertEquals($discussionIDs, $result);
        $userGuest = $this->createUser(["RoleID" => \RoleModel::GUEST_ID]);
        $result = $this->runWithUser(function () use ($discussionIDs) {
            return $this->discussionModel->filterCategoryPermissions($discussionIDs, "Vanilla.Discussions.Delete");
        }, $userGuest);
        $this->assertEmpty($result);
    }

    /**
     * Test DiscussionModel::CheckPermission().
     */
    public function testCheckPermission(): void
    {
        $category = $this->createCategory();
        $discussion = $this->createDiscussion(["categoryID" => $category["categoryID"]]);
        $discussion = ArrayUtils::pascalCase($discussion);
        $result = $this->discussionModel->checkPermission($discussion, "Vanilla.Discussions.View");
        $this->assertTrue($result);
        $result = $this->discussionModel->checkPermission($discussion, "View");
        $this->assertTrue($result);
        unset($discussion["CategoryID"]);
        $result = $this->discussionModel->checkPermission($discussion, "Vanilla.Discussions.View");
        $this->assertFalse($result);
    }

    /**
     * Test Successful DiscussionModel::MoveDiscussions().
     *
     * @depends testPrepareMoveDiscussionsData
     */
    public function testSuccessDiscussionsMove(): void
    {
        // Move valid discussions.
        ModelUtils::consumeGenerator(
            $this->discussionModel->moveDiscussionsIterator(
                self::$data["validDiscussionIDs"],
                ArrayUtils::pascalCase(self::$data["validCategory2"])["CategoryID"],
                true
            )
        );
        $discussions = $this->discussionModel->getIn(self::$data["validDiscussionIDs"])->resultArray();
        foreach ($discussions as $discussion) {
            $this->assertEquals(self::$data["validCategory2"]["categoryID"], $discussion["CategoryID"]);
        }
    }

    /**
     * Prepare move discussions test data.
     */
    public function testPrepareMoveDiscussionsData(): void
    {
        $rd1 = rand(5000, 6000);
        $rd2 = rand(3000, 4000);
        $rd3 = rand(1000, 2000);
        $categoryInvalid = [
            "categoryID" => 123456,
            "name" => "invalid category",
            "urlCode" => "invalid category" . $rd1 . $rd2,
            "parentCategoryID" => $rd1,
        ];
        $category_1Name = "category_1" . $rd1;
        $category_2Name = "category_2" . $rd2;
        $category_3Name = "category_3" . $rd3;
        $categoryData_1 = [
            "name" => $category_1Name,
        ];

        $categoryData_2 = [
            "name" => $category_2Name,
        ];
        $categoryData_3 = [
            "name" => $category_3Name,
        ];

        $category_1 = $this->createCategory($categoryData_1);
        $category_2 = $this->createCategory($categoryData_2);
        $category_3 = $this->createCategory($categoryData_3);
        $category_permission = $this->createPermissionedCategory([], [RoleModel::ADMIN_ID]);
        $discussionData_1 = [
            "name" => "Test Discussion_1",
            "categoryID" => $category_1["categoryID"],
        ];
        $discussionData_2 = [
            "name" => "Test Discussion_2",
            "categoryID" => $category_1["categoryID"],
        ];
        $discussionData_3 = [
            "name" => "Test Discussion_3",
            "categoryID" => $category_1["categoryID"],
        ];
        $discussionData_4 = [
            "name" => "Test Discussion_4",
            "categoryID" => $category_1["categoryID"],
        ];
        $discussionData_Permission = [
            "name" => "Test Discussion_Permission",
            "categoryID" => $category_permission["categoryID"],
        ];
        $discussion_1 = $this->createDiscussion($discussionData_1);
        $discussion_2 = $this->createDiscussion($discussionData_2);
        $discussion_3 = $this->createDiscussion($discussionData_3);
        $discussion_4 = $this->createDiscussion($discussionData_Permission);
        $discussion_Permission = $this->createDiscussion($discussionData_4);
        $discussionIDs = [
            $discussion_1["discussionID"],
            $discussion_2["discussionID"],
            $discussion_3["discussionID"],
            $discussion_4["discussionID"],
        ];
        self::$data["invalidDiscussionIDs"] = [$rd1, $rd2];
        self::$data["invalidCategory"] = $categoryInvalid;
        self::$data["validCategory1"] = $category_1;
        self::$data["validCategory2"] = $category_2;
        self::$data["validCategory3"] = $category_3;
        self::$data["category_permission"] = $category_permission;
        self::$data["discussion_1"] = $discussionData_1;
        self::$data["discussion_2"] = $discussionData_2;
        self::$data["discussion_Permission"] = $discussion_Permission;
        self::$data["validDiscussionIDs"] = $discussionIDs;
        self::$data["mixedIDs"] = array_merge(self::$data["validDiscussionIDs"], [1234]);

        $this->assertTrue(!empty(self::$data));
    }

    /**
     * Test failed DiscussionModel::DiscussionMove.
     *
     * @param string $discussionIDs
     * @param string $category
     * @param int $expectedCode
     * @param int|null $maxIterations
     *
     * @depends testPrepareMoveDiscussionsData
     * @dataProvider provideDiscussionsMoveData
     */
    public function testMoveDiscussionProgress(
        string $discussionIDs,
        string $category,
        int $expectedCode,
        ?int $maxIterations
    ): void {
        if ($maxIterations !== null) {
            $this->getLongRunner()->setMaxIterations($maxIterations);
        }
        $user = $category === "category_permission" ? $this->createUser() : self::$siteInfo["adminUserID"];
        /** @var LongRunnerResult $result */
        $result = $this->runWithUser(function () use ($discussionIDs, $category) {
            return $this->getLongRunner()->runImmediately(
                new LongRunnerAction(DiscussionModel::class, "moveDiscussionsIterator", [
                    self::$data[$discussionIDs],
                    self::$data[$category]["categoryID"],
                    true,
                ])
            );
        }, $user);

        $this->assertEquals($expectedCode, $result->asData()->getStatus());
    }

    /**
     * Provide discussions move data.
     *
     * @return array
     */
    public function provideDiscussionsMoveData(): array
    {
        return [
            "invalid-discussion" => ["invalidDiscussionIDs", "validCategory1", 404, null],
            "invalid-category" => ["validDiscussionIDs", "invalidCategory", 404, null],
            "valid-invalidIDs" => ["mixedIDs", "validCategory2", 404, null],
            "timeout" => ["validDiscussionIDs", "validCategory3", 408, 2],
            "permission-invalid" => ["validDiscussionIDs", "category_permission", 400, null],
        ];
    }

    /**
     * Test that our length validation works as expected.
     *
     * @param array $config
     * @param array $record
     * @param string|null $expectError
     *
     * @dataProvider provideLengthValidation
     */
    public function testLengthValidation(array $config, array $record, string $expectError = null)
    {
        $this->runWithConfig($config, function () use ($record, $expectError) {
            if ($expectError) {
                $this->expectExceptionMessage($expectError);
            }

            $discussion = $this->createDiscussion($record);
            $this->assertIsInt($discussion["discussionID"]);
        });
    }

    /**
     * Providate cases for validating length of discussions.
     */
    public function provideLengthValidation()
    {
        return [
            "too short plaintext" => [
                [
                    "Vanilla.Comment.MinLength" => 5,
                ],
                [
                    "body" => "**four**",
                    "format" => MarkdownFormat::FORMAT_KEY,
                ],
                "Body is 1 character too short",
            ],
            "bytes over plaintext limit, but plaintext limit under" => [
                [
                    "Vanilla.Comment.MinLength" => 5,
                ],
                [
                    "body" => "**four**",
                    "format" => MarkdownFormat::FORMAT_KEY,
                ],
                "Body is 1 character too short",
            ],
            "too long plaintext" => [
                [
                    "Vanilla.Comment.MaxLength" => 5,
                ],
                [
                    "body" => "**morethanfive**",
                    "format" => MarkdownFormat::FORMAT_KEY,
                ],
                "Body is 7 characters too long.",
            ],
        ];
    }

    /**
     * Test a category following notification with CONF_CATEGORY_FOLLOWING disabled.
     */
    public function testAdvancedNoticationsFailure()
    {
        $this->runWithConfig([CategoryModel::CONF_CATEGORY_FOLLOWING => false], function () {
            $roles = $this->getRoles();

            // Create a member user.
            $discussionUser = $this->createUser([
                "Name" => "testDiscussion",
                "Email" => __FUNCTION__ . "@example1.com",
                "Password" => "vanilla",
                "RoleID" => $this->memberID,
            ]);

            $memberUser = $this->createUser([
                "Name" => "testNotications2",
                "Email" => __FUNCTION__ . "@example1.com",
                "Password" => "vanilla",
                "RoleID" => $this->memberID,
            ]);

            $categoryAdmin = $this->createPermissionedCategory([], [$roles["Member"]]);

            $userMeta = [
                sprintf("Preferences.Email.NewDiscussion.%d", $categoryAdmin["categoryID"]) => $categoryAdmin[
                    "categoryID"
                ],
            ];
            $this->userModel::setMeta($memberUser["userID"], $userMeta);

            $discussionMember = [
                "CategoryID" => $categoryAdmin["categoryID"],
                "Name" => __FUNCTION__ . "test discussion",
                "Body" => "foo foo foo",
                "Format" => "Text",
                "InsertUserID" => $discussionUser["userID"],
            ];

            $this->createDiscussion($discussionMember);

            $this->api()->setUserID($memberUser["userID"]);
            $notifications = $this->api()
                ->get("/notifications")
                ->getBody();
            $this->assertCount(0, $notifications);
        });
    }

    /**
     * Test a category following notification with CONF_CATEGORY_FOLLOWING enabled.
     */
    public function testAdvancedNoticationsSuccess()
    {
        $this->runWithConfig([CategoryModel::CONF_CATEGORY_FOLLOWING => true], function () {
            $roles = $this->getRoles();

            // Create a member user.
            $discussionUser = $this->createUser([
                "Name" => "testDiscussion",
                "Email" => __FUNCTION__ . "@example1.com",
                "Password" => "vanilla",
                "RoleID" => $this->memberID,
            ]);

            $memberUser = $this->createUser([
                "Name" => "testNotications2",
                "Email" => __FUNCTION__ . "@example1.com",
                "Password" => "vanilla",
                "RoleID" => $this->memberID,
            ]);

            $categoryAdmin = $this->createPermissionedCategory([], [$roles["Member"]]);

            $userMeta = [
                sprintf("Preferences.Email.NewDiscussion.%d", $categoryAdmin["categoryID"]) => $categoryAdmin[
                    "categoryID"
                ],
            ];
            $this->userModel::setMeta($memberUser["userID"], $userMeta);

            $discussionMember = [
                "CategoryID" => $categoryAdmin["categoryID"],
                "Name" => __FUNCTION__ . "test discussion",
                "Body" => "foo foo foo",
                "Format" => "Text",
                "InsertUserID" => $discussionUser["userID"],
            ];

            $this->createDiscussion($discussionMember);

            $this->api()->setUserID($memberUser["userID"]);
            $notifications = $this->api()
                ->get("/notifications")
                ->getBody();
            $this->assertCount(1, $notifications);
        });
    }

    /**
     * Test that the old bookmarks alias `w` is compatible with getWhere();
     *
     * @return void
     * @throws Exception
     */
    public function testLegacyBookmarkAlias(): void
    {
        $discussion = $this->createDiscussion();
        $this->bookmarkDiscussion();
        $result = $this->discussionModel
            ->getWhere(["w.Bookmarked" => 1, "DiscussionID" => $discussion["discussionID"]])
            ->firstRow(DATASET_TYPE_ARRAY);

        $this->assertEquals($discussion["discussionID"], $result["DiscussionID"]);
    }

    /**
     * Test that DiscussionModel::getWhere() respects the configured default sort when not passed one.
     */
    public function testRespectsDefaultSorts()
    {
        $this->resetTable("Discussion");
        CurrentTimeStamp::mockTime("Dec 1 2020");
        $disc1 = $this->createDiscussion([], ["Score" => 20]);

        CurrentTimeStamp::mockTime("Dec 15 2020");
        $disc2 = $this->createDiscussion([], ["Score" => 10]);

        // Default is ordered by dateLastComment
        $discussions = $this->discussionModel->getWhere()->resultArray();
        $this->assertRowsLike(
            [
                "DiscussionID" => [$disc2["discussionID"], $disc1["discussionID"]],
            ],
            $discussions
        );

        // Now with a different sort order.
        $this->runWithConfig(["Vanilla.Discussions.SortField" => "Score"], function () use ($disc1, $disc2) {
            $discussions = $this->discussionModel->getWhere()->resultArray();
            $this->assertRowsLike(
                [
                    "DiscussionID" => [$disc1["discussionID"], $disc2["discussionID"]],
                ],
                $discussions
            );
        });
    }
}

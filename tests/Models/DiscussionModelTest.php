<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;


use DiscussionModel;
use Gdn;
use PHPUnit\Framework\TestCase;
use VanillaTests\ExpectErrorTrait;
use VanillaTests\SiteTestTrait;

/**
 * Some basic tests for the `DiscussionModel`.
 */
class DiscussionModelTest extends TestCase {
    use SiteTestTrait, ExpectErrorTrait;

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
     * Get a new model for each test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->model = $this->container()->get(\DiscussionModel::class);
        $this->now = new \DateTimeImmutable();
        $this->session = Gdn::session();
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
        $actual = DiscussionModel::canClose($discussion);
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
     * Tests for maxDate().
     */

    /**
     * $dateOne > $dateTwo
     */
    public function testMaxDateDateOneGreater() {
        $dateOne = '2020-01-09 16:22:42';
        $dateTwo = '2019-12-02 21:55:40';
        $expected = $dateOne;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateTwo > $dateOne
     */
    public function testMaxDateDateTwoGreater() {
        $dateOne = '2019-12-02 21:55:40';
        $dateTwo = '2020-01-09 16:22:42';
        $expected = $dateTwo;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateOne is null
     */
    public function testMaxDateDateOneNull() {
        $dateOne = null;
        $dateTwo = '2020-01-09 16:22:42';
        $expected = $dateTwo;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * $dateTwo is null
     */
    public function testMaxDateDateTwoNull() {
        $dateOne = '2020-01-09 16:22:42';
        $dateTwo = null;
        $expected = $dateOne;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * Both dates are null
     */
    public function testMaxDateWithTwoNullValues() {
        $dateOne = null;
        $dateTwo = null;
        $expected = null;
        $actual = DiscussionModel::maxDate($dateOne, $dateTwo);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test {@link reconcileDiscrepantCommentData()} against various scenarios.
     *
     * @param int $discussionCommentCount The number of comments in the discussion according to the Discussion Table.
     * @param string|null $discussionLastCommentDate Date of last Comment according to the Discussion table.
     * @param int $userReadComments Number of Comments the user has read according to the UserDiscussion table.
     * @param string|null $userLastReadDate Date of last Comment read according to the UserDiscussion table.
     * @param array $expected The expected result.
     * @dataProvider provideTestReconcileDiscrepantCommentData
     */
    public function testReconcileDiscrepantCommentData(
        int $discussionCommentCount,
        ?string $discussionLastCommentDate,
        int $userReadComments,
        ?string $userLastReadDate,
        $expected
    ) {
        $actual = DiscussionModel::reconcileDiscrepantCommentData(
            $discussionCommentCount,
            $discussionLastCommentDate,
            $userReadComments,
            $userLastReadDate
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide test data for testReconcileDiscrepantCommentData().
     *
     * @return array Returns an array of test data.
     */
    public function provideTestReconcileDiscrepantCommentData() {
        $r = [
            'CommentsAndUserReadEqualDatesConcur' => [
                10,
                '2019-12-02 21:55:40',
                10,
                '2020-01-09 16:22:42',
                [true, 0],
            ],
            'CommentsAndUserReadEqualDatesDisagree' => [
                10,
                '2020-01-09 16:22:42',
                10,
                '2019-12-02 21:55:40',
                [false, 1],
            ],
            'MoreCommentsThanReadDatesAgree' => [
                15,
                '2020-01-09 16:22:42',
                10,
                '2019-12-02 21:55:40',
                [false, 5],
            ],
            'MoreCommentsThanReadDatesDisagree' => [
                15,
                '2019-12-02 21:55:40',
                10,
                '2020-01-09 16:22:42',
                [true, 0],
            ],
            'MoreReadThanCommentsLastCommentLater' => [
                5,
                '2020-01-09 16:22:42',
                10,
                '2019-12-02 21:55:40',
                [false, 1],
            ],
            'MoreReadThanCommentsLastCommentEarlier' => [
                5,
                '2019-12-02 21:55:40',
                10,
                '2020-01-09 16:22:42',
                [true, 0],
            ],
            'ReadCommentsNoDiscussionCommentsDiscussionNotRead' => [
                0,
                '2020-01-09 16:22:42',
                50,
                '2019-12-02 21:55:40',
                [false, true],
            ],
            'NullUserReadDateNoReadComments' => [
                5,
                '2020-01-09 16:22:42',
                0,
                null,
                [false, 5],
            ],
            'NullUserCountWithReadComments' => [
                5,
                '2020-01-09 16:22:42',
                10,
                null,
                [false, 1],
            ],
        ];

        return $r;
    }
}

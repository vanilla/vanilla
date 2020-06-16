<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;

/**
 * Test count updating around categories, discussions, and comments.
 */
class CountsTest extends TestCase {
    use SetupTraitsTrait, SiteTestTrait, TestCategoryModelTrait, TestDiscussionModelTrait, TestCommentModelTrait;

    /**
     * @var array
     */
    private $discussions = [];

    /**
     * @var array
     */
    private $comments = [];

    /**
     * @var \Gdn_SQLDriver
     */
    private $sql;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->initializeDatabase();
        $this->setupTestTraits();
        $this->sql = $this->discussionModel->SQL;

        $this->categories = [];
        $this->discussions = [];
        $this->comments = [];

        // Insert some test records.
        $discussions = $this->insertDiscussions(2);
        $this->discussions += array_column($discussions, null, 'DiscussionID');

        // Insert some comments for each discussion.
        foreach ($discussions as $discussion) {
            $comments = $this->insertComments(5, ['DiscussionID' => $discussion['DiscussionID']]);
            $this->comments += array_column($comments, null, 'CommentID');
        }
    }

    /**
     * Test the counts on the records that were inserted during setup.
     */
    public function testSetUpCounts(): void {
        foreach ($this->discussions as $row) {
            $this->assertDiscussionCounts($row['DiscussionID']);
        }
    }

    /**
     * Assert that all of the cached aggregate data on the discussion table is correct.
     *
     * @param int $discussionID
     */
    public function assertDiscussionCounts(int $discussionID): void {
        // Use database to ensure no model flim-flammery.
        $discussion = $this->sql->getWhere('Discussion', ['DiscussionID' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertNotEmpty($discussion);

        $counts = $this->query(<<<SQL
select
    count(c.CommentID) as CountComments,
    max(c.DateInserted) as DateLastComment
from GDN_Comment c
where c.DiscussionID = :id
SQL
            , ['id' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);

        if (empty($counts)) {
            $counts = [
                'CountComments' => 0,
                'DateLastComment' => $discussion['DateInserted'],
            ];
        } else {
            // Get the last comment by date then ID.
            $firstComment = $this->sql
                ->orderBy(['DateInserted', 'CommentID'])
                ->limit(1)
                ->getWhere('Comment', ['DiscussionID' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);

            $lastComment = $this->sql
                ->orderBy(['-DateInserted', '-CommentID'])
                ->limit(1)
                ->getWhere('Comment', ['DiscussionID' => $discussionID])->firstRow(DATASET_TYPE_ARRAY);

            $counts += [
                'FirstCommentID' => $firstComment['CommentID'],
                'LastCommentID' => $lastComment['CommentID'],
                'LastCommentUserID' => $lastComment['InsertUserID'],
            ];
        }

        $this->assertPartialArray($counts, $discussion, "discussionID: {$discussionID}, name: {$discussion['Name']}");
    }

    /**
     * Assert that the parts of an array that intersect match eachother.
     *
     * @param array $partial
     * @param array $full
     * @param string $message
     */
    public static function assertPartialArray(array $partial, array $full, string $message = '') {
        $full = array_intersect_key($full, $partial);
        ksort($partial);
        ksort($full);

        TestCase::assertSame($partial, $full, $message);
    }

    /**
     * Execute an ad hoc query on the database.
     *
     * @param string $sql
     * @param array $params
     * @return \Gdn_DataSet
     */
    protected function query(string $sql, array $params = []): \Gdn_DataSet {
        $px = $this->sql->Database->DatabasePrefix;
        $sql = str_replace("GDN_", $px, $sql);

        $r = $this->sql->Database->query($sql, $params);
        return $r;
    }
}

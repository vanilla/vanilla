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
abstract class CountsTest extends TestCase {
    use SetupTraitsTrait, SiteTestTrait, TestCategoryModelTrait, TestDiscussionModelTrait, TestCommentModelTrait;

    /**
     * @var array
     */
    protected $categories;

    /**
     * @var array
     */
    protected $discussions = [];

    /**
     * @var array
     */
    protected $comments = [];

    /**
     * @var \Gdn_SQLDriver
     */
    protected $sql;

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

        // Insert some category containers.
        $parentCategories = $this->insertCategories(2, ['Name' => 'Parent Count %s', 'DisplayAs' => \CategoryModel::DISPLAY_NESTED]);
        $this->categories += array_column($parentCategories, null, 'CategoryID');

        foreach ($parentCategories as $category) {
            $childCategories = $this->insertCategories(
                2,
                [
                'Name' => 'Test Count %s',
                'DisplayAs' => \CategoryModel::DISPLAY_DISCUSSIONS,
                'ParentCategoryID' => $category['CategoryID'],
                ]
            );

            $this->categories += array_column($childCategories, null, 'CategoryID');

            foreach ($childCategories as $childCategory) {
                // Insert some test discussions.
                $discussions = $this->insertDiscussions(2, ['CategoryID' => $childCategory['CategoryID']]);
                $this->discussions += array_column($discussions, null, 'DiscussionID');

                // Insert some comments for each discussion.
                foreach ($discussions as $discussion) {
                    $comments = $this->insertComments(5, ['DiscussionID' => $discussion['DiscussionID']]);
                    $this->comments += array_column($comments, null, 'CommentID');
                }
            }
        }
    }

    /**
     * Test the counts on the records that were inserted during setup.
     */
    public function assertAllCounts(): void {
        foreach ($this->discussions as $row) {
            $this->assertDiscussionCounts($row['DiscussionID']);
        }
//
        foreach ($this->categories as $categoryID => $_) {
            $this->assertCategoryCounts($categoryID);
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
     * Assert that all of the cached aggregate data on the category table is correct.
     *
     * @param int $categoryID
     */
    public function assertCategoryCounts(int $categoryID): void {
        $category = $this->sql->getWhere('Category', ['CategoryID' => $categoryID])->firstRow(DATASET_TYPE_ARRAY);
        $this->assertNotEmpty($category);

        $counts = $this->query(<<<SQL
select
    count(d.DiscussionID) as CountDiscussions,
    sum(d.CountComments) as CountComments
from GDN_Discussion d
where d.CategoryID = :id
SQL
            , ['id' => $categoryID])->firstRow(DATASET_TYPE_ARRAY);

        if (empty($counts)) {
            $counts = [
                'CountDiscussions' => 0,
                'CountComments' => 0
            ];
        } else {
            $counts['CountComments'] = (int)($counts['CountComments'] ?? 0);
            // Get the last comment to fix the last discussion ID.
//            if ($counts['LastCommentID'] !== null) {
//                $lastComment = $this->sql->getWhere('Comment', ['CommentID' => $counts['LastCommentID']])->firstRow(DATASET_TYPE_ARRAY);
//                $this->assertNotEmpty($lastComment);
//                $counts['LastDiscussionID'] = $lastComment['DiscussionID'];
//            }
        }

        $this->assertPartialArray($counts, $category, "categoryID: $categoryID, name: {$category['Name']}");
    }

    /**
     * Assert that the parts of an array that intersect match eachother.
     *
     * @param array $partial
     * @param array $full
     * @param string $message
     */
    public static function assertPartialArray(array $partial, array $full, string $message = ''): void {
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

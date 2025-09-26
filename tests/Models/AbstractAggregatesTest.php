<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Test count updating around categories, discussions, and comments.
 */
abstract class AbstractAggregatesTest extends SiteTestCase
{
    use TestCategoryModelTrait, TestDiscussionModelTrait, TestCommentModelTrait;

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
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->sql = $this->discussionModel->SQL;
        $this->enableCaching();

        $this->categories = [];
        $this->discussions = [];
        $this->comments = [];

        // Insert some category containers.
        $parentCategories = $this->insertCategories(2, [
            "Name" => "Parent Count %s",
            "DisplayAs" => \CategoryModel::DISPLAY_NESTED,
        ]);
        $this->categories += array_column($parentCategories, null, "CategoryID");

        foreach ($parentCategories as $category) {
            $childCategories = $this->insertCategories(2, [
                "Name" => "Test Count %s",
                "DisplayAs" => \CategoryModel::DISPLAY_DISCUSSIONS,
                "ParentCategoryID" => $category["CategoryID"],
            ]);

            $this->categories += array_column($childCategories, null, "CategoryID");

            foreach ($childCategories as $childCategory) {
                // Insert some test discussions.
                $discussions = $this->insertDiscussions(2, ["CategoryID" => $childCategory["CategoryID"]]);
                $this->discussions += array_column($discussions, null, "DiscussionID");

                // Insert some comments for each discussion.
                foreach ($discussions as $discussion) {
                    $comments = $this->insertComments(5, ["DiscussionID" => $discussion["DiscussionID"]]);
                    $this->comments += array_column($comments, null, "CommentID");
                }
            }
        }
        // Reload Categories, Discussions & Comments to ensure counts "fresh".
        $this->reloadCategoriesDiscussionsComments();
    }

    /**
     * Reloads Categories, Discussions & Comments to the classes members.
     */
    public function reloadCategoriesDiscussionsComments(): void
    {
        // Reload categories.
        $categoriesRows = $this->categoryModel
            ->getWhere(["CategoryID" => array_keys($this->categories)])
            ->resultArray();
        $this->categories = [];
        foreach ($categoriesRows as $categoriesRow) {
            $this->categories[$categoriesRow["CategoryID"]] = $categoriesRow;
        }

        // Reload discussions
        $discussionsRows = $this->discussionModel
            ->getWhere(["DiscussionID" => array_keys($this->discussions), "Announce" => false])
            ->resultArray();
        $this->discussions = [];
        foreach ($discussionsRows as $discussionsRow) {
            $this->discussions[$discussionsRow["DiscussionID"]] = $discussionsRow;
        }

        // Reload comments
        $commentsRows = $this->commentModel->getWhere(["CommentID" => array_keys($this->comments)])->resultArray();
        $this->comments = [];
        foreach ($commentsRows as $commentsRow) {
            $this->comments[$commentsRow["CommentID"]] = $commentsRow;
        }
    }

    /**
     * Test the counts on the records that were inserted during setup.
     */
    public function assertAllCounts(): void
    {
        foreach ($this->discussions as $row) {
            $this->assertDiscussionCounts($row["DiscussionID"]);
        }

        foreach ($this->categories as $categoryID => $_) {
            $this->assertCategoryCounts($categoryID);
        }
    }

    /**
     * Assert that the parts of an array that intersect match eachother.
     *
     * @param array $partial
     * @param array $full
     * @param string $message
     */
    public static function assertPartialArray(array $partial, array $full, string $message = ""): void
    {
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
    protected function query(string $sql, array $params = []): \Gdn_DataSet
    {
        $px = $this->sql->Database->DatabasePrefix;
        $sql = str_replace("GDN_", $px, $sql);

        $r = $this->sql->Database->query($sql, $params);
        return $r;
    }
}

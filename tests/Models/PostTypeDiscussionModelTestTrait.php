<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use DiscussionModel;
use Garden\Web\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Vanilla\Forum\Models\DiscussionMergeModel;
use VanillaTests\VanillaTestCase;

/**
 * Useful methods for testing a discussion model with postTypeID.
 */
trait PostTypeDiscussionModelTestTrait
{
    /**
     * @var \DiscussionModel
     */
    protected $discussionModel;

    /** @var DiscussionMergeModel */
    protected $mergeModel;

    /**
     * Instantiate a fresh model for each
     */
    protected function setupTestDiscussionModel()
    {
        $this->discussionModel = $this->container()->get(DiscussionModel::class);
        $this->mergeModel = $this->container()->get(DiscussionMergeModel::class);
        DiscussionModel::cleanForTests();
    }

    /**
     * Create a test record.
     *
     * @param array $override
     *
     * @return array
     */
    public function newDiscussion(array $override): array
    {
        $r = VanillaTestCase::sprintfCounter(
            $override + [
                "Name" => "How do I test %s?",
                "CategoryID" => 1,
                "Body" => "Foo %s.",
                "Format" => "Text",
                "DateInserted" => TestDate::mySqlDate(),
                "postTypeID" => "discussion",
            ],
            __FUNCTION__
        );

        return $r;
    }

    /**
     * Insert test records and return them.
     *
     * @param int $count
     * @param array $overrides An array of row overrides.
     * @return array
     */
    protected function insertDiscussions(int $count, array $overrides = []): array
    {
        $announce = $overrides["Announce"] ?? false;
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->discussionModel->save($this->newDiscussion($overrides));
        }
        $rows = $this->discussionModel->getWhere(["DiscussionID" => $ids, "Announce" => $announce])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test discussions were inserted.");

        return $rows;
    }

    /**
     * Assert that a count matches the database.
     *
     * @param int[]|true $categoryIDs The categories to check or true for all categories.
     * @param int $actualCount The count to assert against.
     */
    protected function assertDiscussionCountsFromDb($categoryIDs, int $actualCount): void
    {
        $this->categoryModel->SQL->select("CountDiscussions", "sum")->from("Category");
        if (is_array($categoryIDs)) {
            $this->categoryModel->SQL->whereIn("CategoryID", $categoryIDs);
        }
        $expectedCounts = (int) $this->categoryModel->SQL->get()->value("CountDiscussions", null);
        $this->assertSame($expectedCounts, $actualCount);
    }

    /**
     * Assert that all of the cached aggregate data on the discussion table is correct.
     *
     * @param int $discussionID
     */
    public function assertDiscussionCounts(int $discussionID): void
    {
        $sql = \Gdn::database()->createSql();
        $discussion = $sql->getWhere("Discussion", ["DiscussionID" => $discussionID])->firstRow(DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new NotFoundException("Discussion", ["discussionID" => $discussionID]);
        }
        $countComments = $sql->getCount("Comment", ["DiscussionID" => $discussionID]);

        $expected = [
            "CountComments" => $countComments,
        ];

        // Get the last comment by date then ID.
        $firstComment = $sql
            ->orderBy(["DateInserted", "CommentID"])
            ->limit(1)
            ->getWhere("Comment", ["DiscussionID" => $discussionID])
            ->firstRow(DATASET_TYPE_ARRAY);

        $lastComment = $sql
            ->orderBy(["-DateInserted", "-CommentID"])
            ->limit(1)
            ->getWhere("Comment", ["DiscussionID" => $discussionID])
            ->firstRow(DATASET_TYPE_ARRAY);

        $expected += [
            "DateLastComment" => $lastComment["DateInserted"] ?? $discussion["DateInserted"],
            "FirstCommentID" => $firstComment["CommentID"] ?? null,
            "LastCommentID" => $lastComment["CommentID"] ?? null,
            "LastCommentUserID" => $lastComment["InsertUserID"] ?? null,
        ];

        VanillaTestCase::assertDataLike(
            $expected,
            $discussion,
            "discussionID: {$discussionID}, name: {$discussion["Name"]}"
        );
    }
}

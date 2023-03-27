<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use DiscussionModel;
use PHPUnit\Framework\TestCase;
use Vanilla\Forum\Models\DiscussionMergeModel;
use VanillaTests\VanillaTestCase;

/**
 * Useful methods for testing a discussion model.
 */
trait TestDiscussionModelTrait
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
}

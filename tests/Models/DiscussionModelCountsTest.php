<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ModelUtils;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Verify discussion count routines.
 */
class DiscussionModelCountsTest extends AbstractCountsTest {

    use UsersAndRolesApiTestTrait;

    /**
     * Given a discussion row, return a valid category ID, different from the original.
     *
     * @param array $row
     * @return int
     * @throws \Exception If a categoryID could not be determined.
     */
    private function alternateCategoryID(array $row): int {
        foreach ($this->categories as $category) {
            if ($category["CategoryID"] !== $row["CategoryID"]) {
                return $category["CategoryID"];
            }
        }
        throw new \Exception("Unable to determine a new category ID.");
    }

    /**
     * Assert counts for all known records is accurate.
     */
    public function testSetupCounts() {
        $this->assertAllCounts();
    }

    /**
     * Verify counts after multiple discussions across different categories have been deleted.
     */
    public function testDeleteMultipleDiscussions() {
        $toDelete = [];
        $cats = [];

        foreach ($this->discussions as $discussion) {
            if (!in_array($discussion['CategoryID'], $cats)) {
                $cats[] = $discussion['CategoryID'];
                $toDelete[] = $discussion['DiscussionID'];
            }
        }

        $response = $this->api()->deleteWithBody("/discussions/list", ["discussionIDs" => $toDelete]);
        $this->assertEquals(200, $response->getStatusCode());

        foreach ($cats as $catID) {
            $this->assertCategoryCounts($catID);
        }
    }

    /**
     * Verify counts after using DiscussionModel::save to move discussions between categories.
     */
    public function testMoveUsingSave(): void {
        $row = current($this->discussions);

        $originalCategoryID = $row["CategoryID"];
        $newCategoryID = $this->alternateCategoryID($row);

        $row["CategoryID"] = $newCategoryID;
        $this->discussionModel->save($row);

        $this->assertCategoryCounts($originalCategoryID);
        $this->assertCategoryCounts($newCategoryID);
    }
}

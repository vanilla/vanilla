<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use CategoryModel;
use PHPUnit\Framework\TestCase;

trait TestCategoryModelTrait {
    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * Instantiate a fresh model for each
     */
    protected function setupTestCategoryModel() {
        $this->categoryModel = $this->container()->get(CategoryModel::class);
    }

    /**
     * Create a test record.
     *
     * @param array $override
     *
     * @return array
     */
    public function newCategory(array $override): array {
        static $i = 1;

        $r = $override + [
                'Name' => "Category $i?",
                'UrlCode' => "cat-$i",
                'Description' => "Foo $i.",
            ];

        return $r;
    }

    /**
     * Insert test records and return them.
     *
     * @param int $count
     * @param array $overrides An array of row overrides.
     * @return array
     */
    private function insertCategories(int $count, array $overrides = []): array {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->categoryModel->save($this->newCategory($overrides));
        }
        $rows = $this->categoryModel->getWhere(['CategoryID' => $ids, 'Announce' => 'All'])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test categories were inserted.");

        return $rows;
    }
}

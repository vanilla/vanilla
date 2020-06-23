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
    protected $categoryModel;

    private static $categoryIndex = 1;

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
        $i = self::$categoryIndex++;

        $r = $override + [
                'Name' => "Category %s",
                'UrlCode' => "cat-%s",
                'Description' => "Foo %s.",
                'DateInserted' => TestDate::mySqlDate(),
            ];

        foreach (['Name', 'UrlCode', 'Description'] as $field) {
            $r[$field] = sprintf($r[$field], $i);
        }

        return $r;
    }

    /**
     * Insert test records and return them.
     *
     * @param int $count
     * @param array $overrides An array of row overrides.
     * @return array
     */
    protected function insertCategories(int $count, array $overrides = []): array {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $id = $this->categoryModel->save($this->newCategory($overrides));
            if ($id === false) {
                throw new \Exception($this->categoryModel->Validation->resultsText(), 400);
            }
            $ids[] = $id;
        }
        $rows = $this->categoryModel->getWhere(['CategoryID' => $ids])->resultArray();
        TestCase::assertCount($count, $rows, "Not enough test categories were inserted.");

        return $rows;
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\SiteTestTrait;

class CategoryModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var \CategoryModel
     */
    private $model;

    public function setUp(): void {
        $this->setupSiteTestTrait();
        $this->container()->call(function (
            \CategoryModel $categoryModel
        ) {
            $this->model = $categoryModel;
        });
    }

    public function sortFlatCategories(&$categories) {
        $fn = function (&$categories) {
            $this->sortFlatCategories($categories);
        };
        $sort = $fn->bindTo($this->model, $this->model);
        $sort($categories);
    }

    public function testSortFlatCategories(): void {
        $categories = $this->getCategoryFlattenData();
        $sorted = $this->getCategoryFlattenData();
        $this->sortFlatCategories($sorted);

        $this->assertSortedCategories($categories, $sorted);
    }

    public function getCategoryFlattenData(): array {
        return [
            -1 =>
                [
                    'CategoryID' => -1,
                    'TreeLeft' => 1,
                    'TreeRight' => 66,
                    'ParentCategoryID' => NULL,
                    'Name' => 'Root',
                    'DisplayAs' => 'Categories',
                ],
            5 =>
                [
                    'CategoryID' => 5,
                    'TreeLeft' => 2,
                    'TreeRight' => 5,
                    'ParentCategoryID' => -1,
                    'Name' => 'Test Parent Category',
                    'DisplayAs' => 'Discussions',
                ],
            6 =>
                [
                    'CategoryID' => 6,
                    'TreeLeft' => 3,
                    'TreeRight' => 4,
                    'ParentCategoryID' => 5,
                    'Name' => 'Test Child Category',
                    'DisplayAs' => 'Discussions',
                ],
            1 =>
                [
                    'CategoryID' => 1,
                    'TreeLeft' => 6,
                    'TreeRight' => 65,
                    'ParentCategoryID' => -1,
                    'Name' => 'General',
                    'DisplayAs' => 'Discussions',
                ],
            33 =>
                [
                    'CategoryID' => 33,
                    'TreeLeft' => 7,
                    'TreeRight' => 8,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 28',
                    'DisplayAs' => 'Flat',
                ],
            32 =>
                [
                    'CategoryID' => 32,
                    'TreeLeft' => 9,
                    'TreeRight' => 10,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 27',
                    'DisplayAs' => 'Flat',
                ],
            31 =>
                [
                    'CategoryID' => 31,
                    'TreeLeft' => 11,
                    'TreeRight' => 12,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 26',
                    'DisplayAs' => 'Flat',
                ],
            30 =>
                [
                    'CategoryID' => 30,
                    'TreeLeft' => 13,
                    'TreeRight' => 14,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 25',
                    'DisplayAs' => 'Flat',
                ],
            29 =>
                [
                    'CategoryID' => 29,
                    'TreeLeft' => 15,
                    'TreeRight' => 16,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 24',
                    'DisplayAs' => 'Flat',
                ],
            28 =>
                [
                    'CategoryID' => 28,
                    'TreeLeft' => 17,
                    'TreeRight' => 18,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 23',
                    'DisplayAs' => 'Flat',
                ],
            27 =>
                [
                    'CategoryID' => 27,
                    'TreeLeft' => 19,
                    'TreeRight' => 20,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 22',
                    'DisplayAs' => 'Flat',
                ],
            26 =>
                [
                    'CategoryID' => 26,
                    'TreeLeft' => 21,
                    'TreeRight' => 22,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 21',
                    'DisplayAs' => 'Flat',
                ],
            24 =>
                [
                    'CategoryID' => 24,
                    'TreeLeft' => 23,
                    'TreeRight' => 24,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 19',
                    'DisplayAs' => 'Categories',
                ],
            23 =>
                [
                    'CategoryID' => 23,
                    'TreeLeft' => 25,
                    'TreeRight' => 26,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 18',
                    'DisplayAs' => 'Flat',
                ],
            21 =>
                [
                    'CategoryID' => 21,
                    'TreeLeft' => 27,
                    'TreeRight' => 28,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 16 Tue, 09 Jun 2020 21:45:14 +0000',
                    'DisplayAs' => 'Flat',
                ],
            20 =>
                [
                    'CategoryID' => 20,
                    'TreeLeft' => 29,
                    'TreeRight' => 30,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 15',
                    'DisplayAs' => 'Flat',
                ],
            19 =>
                [
                    'CategoryID' => 19,
                    'TreeLeft' => 31,
                    'TreeRight' => 32,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 14',
                    'DisplayAs' => 'Flat',
                ],
            18 =>
                [
                    'CategoryID' => 18,
                    'TreeLeft' => 33,
                    'TreeRight' => 34,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 12',
                    'DisplayAs' => 'Flat',
                ],
            17 =>
                [
                    'CategoryID' => 17,
                    'TreeLeft' => 35,
                    'TreeRight' => 36,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 11',
                    'DisplayAs' => 'Flat',
                ],
            15 =>
                [
                    'CategoryID' => 15,
                    'TreeLeft' => 37,
                    'TreeRight' => 38,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 8',
                    'DisplayAs' => 'Flat',
                ],
            14 =>
                [
                    'CategoryID' => 14,
                    'TreeLeft' => 39,
                    'TreeRight' => 40,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 7',
                    'DisplayAs' => 'Flat',
                ],
            13 =>
                [
                    'CategoryID' => 13,
                    'TreeLeft' => 41,
                    'TreeRight' => 42,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 6',
                    'DisplayAs' => 'Categories',
                ],
            12 =>
                [
                    'CategoryID' => 12,
                    'TreeLeft' => 43,
                    'TreeRight' => 44,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 5',
                    'DisplayAs' => 'Flat',
                ],
            11 =>
                [
                    'CategoryID' => 11,
                    'TreeLeft' => 45,
                    'TreeRight' => 46,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 4',
                    'DisplayAs' => 'Flat',
                ],
            9 =>
                [
                    'CategoryID' => 9,
                    'TreeLeft' => 47,
                    'TreeRight' => 50,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Parent as Child',
                    'DisplayAs' => 'Discussions',
                ],
            10 =>
                [
                    'CategoryID' => 10,
                    'TreeLeft' => 48,
                    'TreeRight' => 49,
                    'ParentCategoryID' => 9,
                    'Name' => 'Test Child as Parent',
                    'DisplayAs' => 'Discussions',
                ],
            8 =>
                [
                    'CategoryID' => 8,
                    'TreeLeft' => 51,
                    'TreeRight' => 52,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Child Parent',
                    'DisplayAs' => 'Discussions',
                ],
            7 =>
                [
                    'CategoryID' => 7,
                    'TreeLeft' => 53,
                    'TreeRight' => 54,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Bad Parent',
                    'DisplayAs' => 'Discussions',
                ],
            4 =>
                [
                    'CategoryID' => 4,
                    'TreeLeft' => 55,
                    'TreeRight' => 56,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 3',
                    'DisplayAs' => 'Discussions',
                ],
            3 =>
                [
                    'CategoryID' => 3,
                    'TreeLeft' => 57,
                    'TreeRight' => 58,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 2',
                    'DisplayAs' => 'Flat',
                ],
            2 =>
                [
                    'CategoryID' => 2,
                    'TreeLeft' => 59,
                    'TreeRight' => 64,
                    'ParentCategoryID' => 1,
                    'Name' => 'Test Category 1',
                    'DisplayAs' => 'Flat',
                ],
            22 =>
                [
                    'CategoryID' => 22,
                    'TreeLeft' => 60,
                    'TreeRight' => 61,
                    'ParentCategoryID' => 2,
                    'Name' => 'Test Category 17',
                    'DisplayAs' => 'Flat',
                ],
            16 =>
                [
                    'CategoryID' => 16,
                    'TreeLeft' => 62,
                    'TreeRight' => 63,
                    'ParentCategoryID' => 2,
                    'Name' => 'Test Category 10 Tue, 09 Jun 2020 21:45:13 +0000',
                    'DisplayAs' => 'Categories',
                ],
        ];
    }

    private function assertSortedCategories(array $categories, array $sorted): void {
        $this->assertSame(count($categories), count($sorted), 'The sorted categories has a different count from the original.');

        $categories = array_column($categories, null, 'CategoryID');
        $sorted = array_column($sorted, null, 'CategoryID');

        // Look for items in categories that are not in sorted.
        $notInSorted = array_diff_key($categories, $sorted);
        if (!empty($notInSorted)) {
            $this->fail("The following categories are missing from sorted: ".implode(', ', array_column($notInSorted, 'Name')));
        }

        $notInSorted = array_diff_key($sorted, $categories);
        if (!empty($notInSorted)) {
            $this->fail("The following categories are missing from original: ".implode(', ', array_column($notInSorted, 'Name')));
        }
    }
}

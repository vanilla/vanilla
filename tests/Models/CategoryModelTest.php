<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use CategoryModel;
use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class CategoryModelTest
 *
 * @package VanillaTests\Models
 */
class CategoryModelTest extends SiteTestCase {
    use CommunityApiTestTrait, TestCategoryModelTrait, ModelTestTrait, UsersAndRolesApiTestTrait;

    /**
     * @var array
     */
    private $category;

    /**
     * @var int
     */
    private $categoryID;

    /**
     * Set up a category model for testing.
     *
     * @throws \Garden\Container\ContainerException Throws container exception.
     */
    public function setUp(): void {
        parent::setUp();
        $this->enableCaching();

        $this->category = $this->insertCategories(1)[0];
        $this->categoryID = $this->category['CategoryID'];
    }

    /**
     * Sort the children of flat categories. This is the method to test.
     *
     * @param array $categories Array of categories.
     */
    public function sortFlatCategories(&$categories): void {
        $fn = function (&$categories) {
            $this->sortFlatCategories($categories);
        };
        $sort = $fn->bindTo($this->categoryModel, $this->categoryModel);
        $sort($categories);
    }

    /**
     * Test sortFlatCategories() for ordering alphabetically.
     */
    public function testSortFlatCategoriesOrdersCorrectly(): void {
        $categories = [
                10 =>
                    [
                        'CategoryID' => 10,
                        'TreeLeft' => 1,
                        'TreeRight' => 27,
                        'ParentCategoryID' => -1,
                        'Name' => 'parent',
                        'DisplayAs' => 'Flat',
                    ],
                1 =>
                    [
                        'CategoryID' => 1,
                        'TreeLeft' => 2,
                        'TreeRight' => 3,
                        'ParentCategoryID' => 10,
                        'Name' => 'foo',
                        'DisplayAs' => 'Discussions',
                    ],
                6 =>
                    [
                        'CategoryID' => 6,
                        'TreeLeft' => 2,
                        'TreeRight' => 3,
                        'ParentCategoryID' => 10,
                        'Name' => 'bar',
                        'DisplayAs' => 'Discussions',
                    ],
        ];
        $unsortedCategories = $categories;
        $sortedCategories = $categories;
        $this->sortFlatCategories($unsortedCategories);

        // Have the 'foo' and 'bar' categories been switched?
        $this->assertSame(array_search(1, array_keys($unsortedCategories)), array_search(6, array_keys($sortedCategories)));
        $this->assertSame(array_search(6, array_keys($unsortedCategories)), array_search(1, array_keys($sortedCategories)));
    }

    /**
     * Test sortFlatCategories() with larger, semi-real world data sets.
     *
     * @param array $categories Array of categories to test.
     * @dataProvider provideTestCategories
     */
    public function testSortFlatLargeSets($categories): void {
        $unsortedCategories = $categories;
        $sortedCategories = $categories;
        $this->sortFlatCategories($sortedCategories);

        $this->assertSortedCategories($unsortedCategories, $sortedCategories);
    }

    /**
     * Provide test data for testSortFlatCategories().
     *
     * @return array Returns an array of category data.
     */
    public function provideTestCategories(): array {
        $r = [
            'localHostData' => [[
                -1 =>
                    [
                        'CategoryID' => -1,
                        'TreeLeft' => 1,
                        'TreeRight' => 40,
                        'ParentCategoryID' => null,
                        'Name' => 'Root',
                        'DisplayAs' => 'Categories',
                    ],
                1 =>
                    [
                        'CategoryID' => 1,
                        'TreeLeft' => 2,
                        'TreeRight' => 3,
                        'ParentCategoryID' => -1,
                        'Name' => 'General',
                        'DisplayAs' => 'Discussions',
                    ],
                18 =>
                    [
                        'CategoryID' => 18,
                        'TreeLeft' => 4,
                        'TreeRight' => 9,
                        'ParentCategoryID' => -1,
                        'Name' => 'German',
                        'DisplayAs' => 'Discussions',
                    ],
                19 =>
                    [
                        'CategoryID' => 19,
                        'TreeLeft' => 5,
                        'TreeRight' => 8,
                        'ParentCategoryID' => 18,
                        'Name' => 'German-Sub',
                        'DisplayAs' => 'Discussions',
                    ],
                20 =>
                    [
                        'CategoryID' => 20,
                        'TreeLeft' => 6,
                        'TreeRight' => 7,
                        'ParentCategoryID' => 19,
                        'Name' => 'German-Sub-Sub',
                        'DisplayAs' => 'Discussions',
                    ],
                15 =>
                    [
                        'CategoryID' => 15,
                        'TreeLeft' => 10,
                        'TreeRight' => 11,
                        'ParentCategoryID' => -1,
                        'Name' => 'Sub-recordGroup',
                        'DisplayAs' => 'Discussions',
                    ],
                12 =>
                    [
                        'CategoryID' => 12,
                        'TreeLeft' => 12,
                        'TreeRight' => 17,
                        'ParentCategoryID' => -1,
                        'Name' => 'Big Category',
                        'DisplayAs' => 'Discussions',
                    ],
                13 =>
                    [
                        'CategoryID' => 13,
                        'TreeLeft' => 13,
                        'TreeRight' => 16,
                        'ParentCategoryID' => 12,
                        'Name' => 'Inner Category',
                        'DisplayAs' => 'Categories',
                    ],
                14 =>
                    [
                        'CategoryID' => 14,
                        'TreeLeft' => 14,
                        'TreeRight' => 15,
                        'ParentCategoryID' => 13,
                        'Name' => 'Innermost Category',
                        'DisplayAs' => 'Discussions',
                    ],
                11 =>
                    [
                        'CategoryID' => 11,
                        'TreeLeft' => 18,
                        'TreeRight' => 19,
                        'ParentCategoryID' => -1,
                        'Name' => 'Reported Posts',
                        'DisplayAs' => 'Discussions',
                    ],
                10 =>
                    [
                        'CategoryID' => 10,
                        'TreeLeft' => 20,
                        'TreeRight' => 25,
                        'ParentCategoryID' => -1,
                        'Name' => 'Social Groups',
                        'DisplayAs' => 'Flat',
                    ],
                16 =>
                    [
                        'CategoryID' => 16,
                        'TreeLeft' => 21,
                        'TreeRight' => 22,
                        'ParentCategoryID' => 10,
                        'Name' => 'Soul Records',
                        'DisplayAs' => 'Discussions',
                    ],
                17 =>
                    [
                        'CategoryID' => 17,
                        'TreeLeft' => 23,
                        'TreeRight' => 24,
                        'ParentCategoryID' => 10,
                        'Name' => 'Metal Records',
                        'DisplayAs' => 'Discussions',
                    ],
                9 =>
                    [
                        'CategoryID' => 9,
                        'TreeLeft' => 26,
                        'TreeRight' => 27,
                        'ParentCategoryID' => -1,
                        'Name' => 'Great New Ideas',
                        'DisplayAs' => 'Discussions',
                    ],
                8 =>
                    [
                        'CategoryID' => 8,
                        'TreeLeft' => 28,
                        'TreeRight' => 29,
                        'ParentCategoryID' => -1,
                        'Name' => 'Top Secret Category',
                        'DisplayAs' => 'Discussions',
                    ],
                2 =>
                    [
                        'CategoryID' => 2,
                        'TreeLeft' => 30,
                        'TreeRight' => 39,
                        'ParentCategoryID' => -1,
                        'Name' => 'Category with Categories',
                        'DisplayAs' => 'Flat',
                    ],
                21 =>
                    [
                        'CategoryID' => 21,
                        'TreeLeft' => 31,
                        'TreeRight' => 32,
                        'ParentCategoryID' => 2,
                        'Name' => 'Another Subcategory',
                        'DisplayAs' => 'Discussions',
                    ],
                7 =>
                    [
                        'CategoryID' => 7,
                        'TreeLeft' => 33,
                        'TreeRight' => 34,
                        'ParentCategoryID' => 2,
                        'Name' => 'Subcategory3',
                        'DisplayAs' => 'Discussions',
                    ],
                6 =>
                    [
                        'CategoryID' => 6,
                        'TreeLeft' => 35,
                        'TreeRight' => 36,
                        'ParentCategoryID' => 2,
                        'Name' => 'Subcategory2',
                        'DisplayAs' => 'Discussions',
                    ],
                3 =>
                    [
                        'CategoryID' => 3,
                        'TreeLeft' => 37,
                        'TreeRight' => 38,
                        'ParentCategoryID' => 2,
                        'Name' => 'Subcategory1',
                        'DisplayAs' => 'Discussions',
                    ],
            ]],
            'testCategoryData' => [[
                -1 =>
                    [
                        'CategoryID' => -1,
                        'TreeLeft' => 1,
                        'TreeRight' => 66,
                        'ParentCategoryID' => null,
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
            ]],
        ];
        return $r;
    }

    /**
     * Assert that the sorted category array and the original category array contain the exact same categories
     * with no categories missing or repeated.
     *
     * @param array $categories
     * @param array $sorted
     */
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

    /**
     * Test searching of categories.
     */
    public function testSearchCategories() {
        \Gdn::sql()->truncate('Category');
        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get(CategoryModel::class);

        $cat1 = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'Category 1',
            'UrlCode' => 'cat1',
            'DisplayAs' => 'Categories',
        ]);

        $cat1_1 = $categoryModel->save([
            'ParentCategoryID' => $cat1,
            'Name' => 'Category 1.1',
            'UrlCode' => 'cat1_1',
            'DisplayAs' => 'Categories',
        ]);

        $cat1_1_1 = $categoryModel->save([
            'ParentCategoryID' => $cat1_1,
            'Name' => 'Category 1.1.1',
            'UrlCode' => 'cat1_1_1',
            'DisplayAs' => 'Categories',
        ]);

        $cat2 = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'Category 2',
            'UrlCode' => 'cat2',
            'DisplayAs' => 'Categories',
        ]);

        $cat2_1_followed = $categoryModel->save([
            'ParentCategoryID' => $cat2,
            'Name' => 'Category 2.1 followed',
            'UrlCode' => 'cat2_1',
            'DisplayAs' => 'Discussions',
        ]);

        $cat2_2_archived = $categoryModel->save([
            'ParentCategoryID' => $cat2,
            'Name' => 'Category 2.2 archived',
            'UrlCode' => 'cat2_2',
            'DisplayAs' => 'Categories',
            'Archived' => 1
        ]);

        $categoryModel->follow(\Gdn::session()->UserID, $cat2_1_followed, true);

        $this->assertIDsEqual([
            -1,
            0,
            $cat1,
            $cat1_1,
            $cat1_1_1,
            $cat2,
            $cat2_1_followed,
            // Archived not included.
        ], $categoryModel->getSearchCategoryIDs());

        $this->assertIDsEqual([
            0,
            $cat2_1_followed,
            // Archived not included.
        ], $categoryModel->getSearchCategoryIDs(null, true));

        $this->assertIDsEqual([
            -1,
            0,
            $cat1,
            $cat1_1,
            $cat1_1_1,
            $cat2,
            $cat2_1_followed,
            $cat2_2_archived
            // Archived not included.
        ], $categoryModel->getSearchCategoryIDs(null, null, null, true));

        $this->assertIDsEqual([
            0,
            $cat1,
            $cat1_1,
            $cat1_1_1,
            // Archived not included.
        ], $categoryModel->getSearchCategoryIDs($cat1, null, true));

        $this->assertIDsEqual([0], $categoryModel->getSearchCategoryIDs(50000));
    }

    /**
     * Test getting multiple items from the collection.
     */
    public function testCollectionGetMulti() {
        \Gdn::sql()->truncate('Category');
        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get(CategoryModel::class);

        $simpleGet = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'simple',
            'UrlCode' => 'simple',
            'DisplayAs' => 'Categories',
        ]);

        $refreshCache = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'refreshCache',
            'UrlCode' => 'refreshCache',
            'DisplayAs' => 'Categories',
        ]);

        $alreadyGetMulti = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'alreadyGetMulti',
            'UrlCode' => 'alreadyGetMulti',
            'DisplayAs' => 'Categories',
        ]);

        $fresh = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'fresh',
            'UrlCode' => 'fresh',
            'DisplayAs' => 'Categories',
        ]);

        // Try and hydrate the in memory cache a bit.
        $collection = $categoryModel->getCollection();
        $this->assertCalculated($collection->get((int) $simpleGet));

        $collection->refreshCache($refreshCache);
        $this->assertCalculated($collection->get((int) $refreshCache));

        // Load a mulit of it's own.
        $this->assertCalculated($collection->getMulti([$alreadyGetMulti]));

        // All together now.
        $this->assertCalculated($collection->getMulti([$simpleGet, $refreshCache, $alreadyGetMulti, $fresh]));
    }

    /**
     * Assert some categories have been calculated.
     *
     * @param array $categoryOrCategories
     */
    public function assertCalculated(array $categoryOrCategories) {
        $categories = $categoryOrCategories;
        if (ArrayUtils::isAssociative($categories)) {
            $categories = [$categories];
        }

        foreach ($categories as $category) {
            $this->assertNotNull($category['PermsDiscussionsView'] ?? null);
            $this->assertNotNull($category['Url'] ?? null);
            $this->assertNotNull($category['CssClass'] ?? null);
        }
    }

    /**
     * Verify backwards-compatible behavior of resetting a category's permissions when updating.
     */
    public function testSaveResetPermissions(): void {
        $this->createRole(["name" => __FUNCTION__]);
        $this->createPermissionedCategory(["parentCategoryID" => CategoryModel::ROOT_ID], [$this->lastRoleID]);

        // Confirm the category is setup for custom permissions.
        $original = $this->categoryModel->getID($this->lastInsertedCategoryID, DATASET_TYPE_ARRAY);
        $this->assertSame($this->lastInsertedCategoryID, $original["PermissionCategoryID"]);

        // Category was root-level, so should fall back to default category permissions.
        $this->categoryModel->save([
            "CategoryID" => $this->lastInsertedCategoryID,
            "Name" => sha1(mt_rand()),
            "Permissions" => null,
        ]);
        $result = $this->categoryModel->getID($this->lastInsertedCategoryID, DATASET_TYPE_ARRAY);
        $this->assertSame(CategoryModel::ROOT_ID, $result["PermissionCategoryID"]);
    }

    /**
     * Verify sparse updating an existing category won't unduly update its permissions.
     */
    public function testSavePermissionsUpdate(): void {
        $this->createRole(["name" => __FUNCTION__]);
        $this->createPermissionedCategory(["parentCategoryID" => CategoryModel::ROOT_ID], [$this->lastRoleID]);

        // Confirm the category is setup for custom permissions.
        $original = $this->categoryModel->getID($this->lastInsertedCategoryID, DATASET_TYPE_ARRAY);
        $this->assertSame($this->lastInsertedCategoryID, $original["PermissionCategoryID"]);

        // Not modifying any permission fields, so the custom-permission status should be unchanged.
        $this->categoryModel->save([
            "CategoryID" => $this->lastInsertedCategoryID,
            "Name" => sha1(mt_rand()),
        ]);
        $result = $this->categoryModel->getID($this->lastInsertedCategoryID, DATASET_TYPE_ARRAY);
        $this->assertSame($this->lastInsertedCategoryID, $result["PermissionCategoryID"]);
    }

    /**
     * Tests for sorting an array of categories as a tree.
     */
    public function testSortCategoriesAsTree() {
        $root = [
            'Name' => 'Root',
            'CategoryID' => -1,
            'ParentCategoryID' => null,
        ];
        $cat1 = [
            'Name' => '1',
            'CategoryID' => 1,
            'ParentCategoryID' => -1,
            'Sort' => 1,
        ];
        $cat1_1 = [
            'Name' => '1.1',
            'CategoryID' => 2,
            'ParentCategoryID' => 1,
            'Sort' => 1,
        ];
        $cat1_2 = [
            'Name' => '1.2',
            'CategoryID' => 3,
            'ParentCategoryID' => 1,
            'Sort' => 2,
        ];
        $cat2 = [
            'Name' => '2',
            'CategoryID' => 4,
            'ParentCategoryID' => -1,
            'Sort' => 2,
        ];
        $catNowhere = [
            'Name' => 'nowhere',
            'CategoryID' => 1000,
            'ParentCategoryID' => 1342,
            'Sort' => -40,
        ];

        $expected = [
            $root,
            $cat1,
            $cat1_1,
            $cat1_2,
            $cat2,
            $catNowhere
        ];

        $in = [
            $cat2,
            $cat1_1,
            $cat1_2,
            $root,
            $catNowhere,
            $cat1,
        ];

        $this->assertSame($expected, CategoryModel::sortCategoriesAsTree($in));

        // Test when a broken tree is passed.
        $expected = [
            $cat2,
            $catNowhere,
            $cat1_1,
            $cat1_2,
        ];

        $in = [
            $cat2,
            $cat1_1,
            $cat1_2,
            $catNowhere,
        ];

        $this->assertSame($expected, CategoryModel::sortCategoriesAsTree($in));
    }

    /**
     * Test setting the local field of a category and making sure it's reflected.
     */
    public function testSetLocalNotFetched(): void {
        $this->assertNull(CategoryModel::$Categories);
        CategoryModel::setLocalField($this->categoryID, 'Name', __FUNCTION__);

        $c1 = CategoryModel::categories($this->categoryID);
        $this->assertSame(__FUNCTION__, $c1['Name']);

        // All categories should not have been fetched just to look at one.
        $this->assertNull(CategoryModel::$Categories);

        // If all categories are fetched then the change should be represented.
        $this->assertSame(__FUNCTION__, CategoryModel::categories()[$this->categoryID]['Name']);
    }

    /**
     * Test setting the local field of a category and making sure it's reflected with a different fetch order
     */
    public function testSetLocalNotFetched2(): void {
        $this->assertNull(CategoryModel::$Categories);
        CategoryModel::setLocalField($this->categoryID, 'Name', __FUNCTION__);

        // If all categories are fetched then the change should be represented.
        $this->assertSame(__FUNCTION__, CategoryModel::categories()[$this->categoryID]['Name']);

        $this->assertSame(__FUNCTION__, CategoryModel::instance()->getCollection()->get($this->categoryID)['Name']);
    }

    /**
     * Test setting the local field of a category and making sure it's reflected with a different fetch order
     */
    public function testSetLocalFetchedGlobally(): void {
        CategoryModel::categories();
        CategoryModel::instance()->getCollection()->get($this->categoryID);
        $this->assertNotNull(CategoryModel::$Categories);
        CategoryModel::setLocalField($this->categoryID, 'Name', __FUNCTION__);

        // If all categories are fetched then the change should be represented.
        $this->assertSame(__FUNCTION__, CategoryModel::categories($this->categoryID)['Name']);

        $this->assertSame(__FUNCTION__, CategoryModel::instance()->getCollection()->get($this->categoryID)['Name']);
    }

    /**
     * This test is to protect against a brain fart in calculation logic.
     */
    public function testDontCorruptOtherCategoriesWithSetLocalField(): void {
        $id = $this->insertCategories(1)[0]['CategoryID'];
        CategoryModel::setLocalField($this->categoryID, 'Name', __FUNCTION__);

        $this->assertNotSame(CategoryModel::categories($id)['Name'], __FUNCTION__);
        $this->assertNotSame(CategoryModel::categories()[$id]['Name'], __FUNCTION__);
    }
}

<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Class CategoryFeaturedTest
 */
class CategoryFeaturedTest extends AbstractAPIv2Test {

    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private static $featuredCategory = [];

    /**
     * Test post featured category.
     */
    public function testPostFeaturedCategory() {
        self::$featuredCategory = $category = $this->createCategory(['featured' => true]);
        $this->assertTrue($category['featured']);
    }

    /**
     * Test patch featured category
     *
     * @depends testPostFeaturedCategory
     */
    public function testPatchFeaturedCategory() {
        $category = $this->api()
            ->patch('/categories/'.self::$featuredCategory['categoryID'], ['featured' => false])
            ->getBody();
        $this->assertFalse($category['featured']);

        $category = $this->api()
            ->patch('/categories/'.self::$featuredCategory['categoryID'], ['featured' => true])
            ->getBody();
        $this->assertTrue($category['featured']);
    }

    /**
     * Test get index all categories
     *
     * @depends testPatchFeaturedCategory
     */
    public function testIndexCategories() {
        $category = $this->createCategory();
        $categories = $this->api()
            ->get('/categories')
            ->getBody();
        $this->assertEquals(3, count($categories));

        $categories = $this->api()
            ->get('/categories', ['featured' => true])
            ->getBody();
        $this->assertEquals(1, count($categories));
        $this->assertEquals(self::$featuredCategory['categoryID'], $categories[0]['categoryID']);
    }

    /**
     * Test fetching featured categories.
     */
    public function testWithOtherFilters() {
        $this->resetTable('Category');
        $cat1 = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Cat 1',
        ]);
        $this->createCategory([
            'parentCategoryID' => $cat1['categoryID'],
            'name' => 'Cat 1.1',
            'featured' => true,
        ]);
        $this->createCategory([
            'parentCategoryID' => $cat1['categoryID'],
            'name' => 'Cat 1.2',
        ]);
        $cat2 = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Cat 2',
        ]);
        $this->createCategory([
            'parentCategoryID' => $cat2['categoryID'],
            'name' => 'Cat 2.1',
            'featured' => true,
        ]);
        $privateFeatured = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Private featured',
            'featured' => true,
        ]);

        // Should not appear.
        $customRole = $this->createRole([
            'name' => 'Custom Role',
            'permissions' => [
                [
                    'type' => 'category',
                    'id' => $this->lastInsertedCategoryID,
                    'permissions' => [
                        'discussions.view' => true,
                    ]
                ]
            ]
        ]);

        // Make ourselves a non-sysadmin.
        $this->api()->setUserID(3);

        $categories = $this->api()->get('/categories', [
            'featured' => true,
        ])->getBody();
        $this->assertEquals([
            'Cat 1.1',
            'Cat 2.1',
        ], array_column($categories, 'name'));

        // Featured with a parentID.

        $categories = $this->api()->get('/categories', [
            'featured' => true,
            'parentCategoryID' => $cat1['categoryID'],
        ])->getBody();
        $this->assertEquals([
            'Cat 1.1',
        ], array_column($categories, 'name'));
    }

    /**
     * Test featured categories get order position.
     */
    public function testFeaturedCategoryGetOrderPosition() {
        $this->resetTable('Category');
        /** @var \CategoryModel $categoryModel */
        $categoryModel = self::container()->get(\CategoryModel::class);
        $category1ID = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'Featured Category 1',
            'UrlCode' => 'featured-cat1',
            'DisplayAs' => 'Discussions',
            'Featured' => true,
        ]);
        $category2ID = $categoryModel->save([
            'ParentCategoryID' => -1,
            'Name' => 'Featured Category 2',
            'UrlCode' => 'featured-cat2',
            'DisplayAs' => 'Discussions',
            'Featured' => true,
        ]);
        $category1 = $categoryModel->getID($category1ID, DATASET_TYPE_ARRAY);
        $category2 = $categoryModel->getID($category2ID, DATASET_TYPE_ARRAY);
        $this->assertEquals(0, $category1['SortFeatured']);
        $this->assertEquals(1, $category2['SortFeatured']);
    }

    /**
     * Test sort featured categories.
     */
    public function testFeaturedCategoryOrder() {
        $this->resetTable('Category');
        // Test order on creation.
        $categories = [];
        $categories[] = $this->createCategory(['featured' => true]);
        $categories[] = $this->createCategory(['featured' => true]);
        $categories[] = $this->createCategory(['featured' => true]);
        $categoryIDs = array_column($categories, 'categoryID');
        $categoriesFeatured = $this->api()->get('/categories', [
            'featured' => true,
        ])->getBody();
        $categoriesFeaturedIDs = array_column($categoriesFeatured, 'categoryID');

        $this->assertEquals($categoryIDs, $categoriesFeaturedIDs);
        // Test order after updating attribute.
        $this->api()
            ->patch('/categories/'.$categoriesFeatured[1]['categoryID'], ['featured' => false])
            ->getBody();
        $this->api()
            ->patch('/categories/'.$categoriesFeatured[1]['categoryID'], ['featured' => true])
            ->getBody();
        $newCategoriesFeatured = $this->api()->get('/categories', [
            'featured' => true,
        ])->getBody();
        $newCategoriesFeaturedIDs = array_column($newCategoriesFeatured, 'categoryID');
        $newCategories = $categories;
        $newCategories[1] = $categories[2];
        $newCategories[2] = $categories[1];
        $newCategoryIDs = array_column($newCategories, 'categoryID');
        $this->assertEquals($newCategoryIDs, $newCategoriesFeaturedIDs);
        // Test order after updating attribute with same value.
        $this->api()
            ->patch('/categories/'.$categoriesFeatured[1]['categoryID'], ['featured' => true])
            ->getBody();
        $categoriesFeaturedState = $this->api()->get('/categories', [
            'featured' => true,
        ])->getBody();
        $newCategoryIDs = array_column($categoriesFeaturedState, 'categoryID');
        $this->assertEquals($newCategoriesFeaturedIDs, $newCategoryIDs);
    }
}

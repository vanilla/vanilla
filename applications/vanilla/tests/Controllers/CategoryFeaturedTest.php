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
}

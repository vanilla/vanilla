<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Controllers;

use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Class CategoryMaxDepthTest
 */
class CategoryMaxDepthTest extends AbstractAPIv2Test {
    use CommunityApiTestTrait;

    public static $data = [];

    /**
     * Test get categories api index + maxDepth
     *
     * @param array $params
     * @param array $expectedResults
     * @depends testPrepareCategories
     * @dataProvider categoriesProvider
     */
    public function testMaxDepthCategories(array $params, array $expectedResults) {
        $categories = $this->api()->get('/categories', $params)->getBody();
        foreach ($expectedResults as $catKey => $children) {
            $this->assertChildren($categories, $catKey, $children);
        }
    }

    /**
     * Assert expected children recursively
     *
     * @param array $categories Actual categories
     * @param string $catKey Expected category to exist
     * @param array $children Expected children for expected category
     */
    private function assertChildren(array $categories, string $catKey, array $children) {
        $found = false;
        foreach ($categories as $category) {
            if ($category['categoryID'] === self::$data[$catKey]['categoryID']) {
                $found = true;
                $this>self::assertEquals(count($children), count($category['children']));
                foreach ($children as $childKey => $childChildren) {
                    $this->assertChildren($category['children'], $childKey, $childChildren);
                }
                break;
            }
        }
        $this->assertTrue($found, 'Category: '.self::$data[$catKey]['name'].' not found in api response');
    }

    /**
     * Data provider for testMaxDepthCategories
     *
     * @return array
     */
    public function categoriesProvider(): array {
        return [
            'No params' => [
                [],
                [
                    '1' => [
                        '1.1' => [],
                        '1.2' => [],
                        '1.3' => [],
                    ],
                    '2' => [
                        '2.1' => [],
                        '2.2' => []
                    ],
                    '3' => []
                ]
            ],
            'maxDepth = 1' => [
                ['maxDepth' => 1],
                [
                    '1' => [],
                    '2' => [],
                    '3' => [],
                ]
            ],
            'maxDepth = 2' => [
                ['maxDepth' => 2],
                [
                    '1' => [
                        '1.1' => [],
                        '1.2' => [],
                        '1.3' => [],
                        ],
                    '2' => [
                        '2.1' => [],
                        '2.2' => []
                    ],
                    '3' => []
                ]
            ],
            'maxDepth = 3' => [
                ['maxDepth' => 3],
                [
                    '1' => [
                        '1.1' => [],
                        '1.2' => [],
                        '1.3' => []
                    ],
                    '2' => [
                        '2.1' => [
                            '2.1.1' => [],
                            '2.1.2' => []
                        ],
                        '2.2' => [
                            '2.2.1' => []
                        ]
                    ],
                    '3' => []
                ]
            ],
        ];
    }

    /**
     * Prepare categories.
     */
    public function testPrepareCategories() {
        $this->resetTable('Category');
        self::$data['1'] = $cat1 = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Cat 1',
        ]);

        self::$data['1.1'] = $cat1_1 = $this->createCategory([
            'parentCategoryID' => $cat1['categoryID'],
            'name' => 'Cat 1.1',
        ]);

        self::$data['1.2'] = $cat1_2 = $this->createCategory([
            'parentCategoryID' => $cat1['categoryID'],
            'name' => 'Cat 1.2',
        ]);

        self::$data['1.3'] = $cat1_3 = $this->createCategory([
            'parentCategoryID' => $cat1['categoryID'],
            'name' => 'Cat 1.3',
        ]);

        self::$data['2'] = $cat2 = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Cat 2',
        ]);

        self::$data['2.1'] = $cat2_1 = $this->createCategory([
            'parentCategoryID' => $cat2['categoryID'],
            'name' => 'Cat 2.1',
        ]);

        self::$data['2.2'] = $cat2_2 = $this->createCategory([
            'parentCategoryID' => $cat2['categoryID'],
            'name' => 'Cat 2.2',
        ]);

        self::$data['2.1.1'] = $cat2_1_1 = $this->createCategory([
            'parentCategoryID' => $cat2_1['categoryID'],
            'name' => 'Cat 2.1.1',
        ]);

        self::$data['2.1.2'] = $cat2_1_2 = $this->createCategory([
            'parentCategoryID' => $cat2_1['categoryID'],
            'name' => 'Cat 2.1.2',
        ]);

        self::$data['2.2.1'] = $cat2_2_1 = $this->createCategory([
            'parentCategoryID' => $cat2_2['categoryID'],
            'name' => 'Cat 2.2.1',
        ]);

        self::$data['2.1.2.1'] = $cat2_1_2_1 = $this->createCategory([
            'parentCategoryID' => $cat2_1_2['categoryID'],
            'name' => 'Cat 2.1.2.1',
        ]);

        self::$data['3'] = $cat3 = $this->createCategory([
            'parentCategoryID' => -1,
            'name' => 'Cat 3',
        ]);

        $categories = $this->api()->get('/categories')->getBody();
        $this->assertEquals(3, count($categories));
    }
}

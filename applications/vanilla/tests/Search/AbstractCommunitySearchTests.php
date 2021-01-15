<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum\Search;

use CategoryModel;
use DiscussionModel;
use Gdn;
use TagModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use VanillaTests\Search\AbstractSearchTest;

/**
 * Test cases for the community search.
 */
abstract class AbstractCommunitySearchTests extends AbstractSearchTest {

    /** @var array */
    protected static $data;

    /**
     * @inheritdoc
     */
    protected static function getSearchTypeClasses(): array {
        return [
            DiscussionSearchType::class,
            CommentSearchType::class
        ];
    }

    /**
     * @param ConfigurationInterface $config
     */
    public static function applyConfigurationBeforeSetup(ConfigurationInterface $config) {
        $config->saveToConfig('Vanilla.EnableCategoryFollowing', '1');
    }

    /**
     * Setup for tests.
     */
    public function testSetup(): void {

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');
        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        $tmp = uniqid('category_');
        self::$data['category1'] = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$data['discussion1'] = $discussionsAPIController->post([
            'name' => $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$data['category1']['categoryID'],
        ]);
        self::$data['discussion1']['rawBody'] = $tmp;

        self::$data['discussion2'] = $discussionsAPIController->post([
            'name' => 'August test 2019',
            'body' => 'August test 2019',
            'format' => 'markdown',
            'categoryID' => self::$data['category1']['categoryID'],
        ]);

        $tmp = $tmp . ' ' . uniqid('comment_');
        self::$data['comment1'] = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$data['discussion1']['discussionID'],
        ]);
        self::$data['comment1']['rawBody'] = $tmp;

        $tmp = uniqid('category_');
        self::$data['category2'] = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$data['discussion3'] = $discussionsAPIController->post([
            'name' => $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$data['category2']['categoryID'],
        ]);
        self::$data['discussion3']['rawBody'] = $tmp;

        self::$data['discussion4'] = $discussionsAPIController->post([
            'name' => 'December test 2020',
            'body' => 'Decemberj  test 2020',
            'format' => 'markdown',
            'categoryID' => self::$data['category2']['categoryID'],
        ]);

        $tmp = $tmp . ' ' . uniqid('comment_');
        self::$data['comment2'] = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$data['discussion3']['discussionID'],
        ]);
        self::$data['comment2']['rawBody'] = $tmp;

        self::$data['non_existing']['discussionID'] = 999999;
        self::$data['non_existing']['categoryID'] = 999999;

        $this->ensureIndexed();
        $this->assertTrue(true);
    }

    /**
     * Tests for searching with a query param.
     *
     * @param array $searchParams
     * @param array $expectedResults
     * @depends testSetup
     * @dataProvider queryDataProvider
     */
    public function testCases(array $searchParams, array $expectedResults) {
        $searchParams = $this->calculateParams($searchParams);
        $expectedResults = $this->calculateResults($expectedResults);
        $this->assertSearchResults($searchParams, $expectedResults, false, count($expectedResults['name']));
    }

    /**
     * Calculate param values from their pseudo keys if need
     *
     * @param array $params
     * @return array
     */
    private function calculateParams(array $params) {
        if (array_key_exists('query', $params) && is_array($params['query'])) {
            $query = '';
            foreach ($params['query'] as $key => $field) {
                $query .= self::$data[$key][$field];
            }
            $params['query'] = $query;
        }
        if (!empty($params['discussionID'] ?? '')) {
            $params['discussionID'] = self::$data[$params['discussionID']]['discussionID'];
        }
        if (!empty($params['categoryID'] ?? '')) {
            $params['categoryID'] = self::$data[$params['categoryID']]['categoryID'];
        }
        if (!empty($params['categoryIDs'] ?? '')) {
            $categoryIDs = [];
            foreach ($params['categoryIDs'] as $categoryKey) {
                $categoryIDs[] = self::$data[$categoryKey]['categoryID'];
            }
            $params['categoryIDs'] = $categoryIDs;
        }
        return $params;
    }

    /**
     * Calculate result set values from their pseudo keys if need
     *
     * @param array $results
     * @return array
     */
    private function calculateResults(array $results) {
        if (array_key_exists('name', $results) && is_array($results['name'])) {
            $res = [];
            foreach ($results['name'] as $key => $field) {
                $res[] = self::$data[$key][$field];
            }
            $results['name'] = $res;
        }
        return $results;
    }

    /**
     * @depends testSetup
     * @return array
     */
    public function queryDataProvider() {
        $types = ['discussion', 'comment', 'category'];
        return [
            'recordTypes => discussion' => [
                [
                    'query' => ['discussion1' => 'name'],
                    'recordTypes' => 'discussion',
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                    ]
                ]
            ],
            'recordTypes => comment' => [
                [
                    'query' => ['comment1' => 'rawBody'],
                    'recordTypes' => 'comment',
                ],
                [
                    'name' => [
                        'comment1' => 'name',
                    ]
                ]
            ],
            'discussionID => discussion1' => [
                [
                    'recordTypes' => ['comment', 'discussion'],
                    'discussionID' => 'discussion1'
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                        'comment1' => 'name',
                    ]
                ]
            ],
            'discussionID => non_existing' => [
                [
                    'recordTypes' => ['comment', 'discussion'],
                    'discussionID' => 'non_existing'
                ],
                [
                    'name' => []
                ]
            ],
            'categoryID => category1' => [
                [
                    'recordTypes' => ['comment', 'discussion'],
                    'categoryID' => 'category1'
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                        'discussion2' => 'name',
                        'comment1' => 'name',
                    ]
                ]
            ],
            'categoryID => non_existing' => [
                [
                    'recordTypes' => ['comment', 'discussion'],
                    'categoryID' => 'non_existing'
                ],
                [
                    'name' => []
                ]
            ],
            'categoryIDs => category1, category2' => [
                [
                    'recordTypes' => ['comment', 'discussion'],
                    'categoryIDs' => ['category1', 'category2']
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                        'discussion2' => 'name',
                        'comment1' => 'name',
                        'discussion3' => 'name',
                        'discussion4' => 'name',
                        'comment2' => 'name',
                    ]
                ]
            ],
            'insertUserNames => circleci, daffewfega' => [
                [
                    'query' => ['discussion1' => 'name'],
                    'insertUserNames' => 'circleci, daffewfega'
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                        'comment1' => 'name',
                    ]
                ]
            ],
            'insertUserIDs => 1,2' => [
                [
                    'query' => ['discussion1' => 'name'],
                    'insertUserIDs' => '1,2'
                ],
                [
                    'name' => [
                        'discussion1' => 'name',
                        'comment1' => 'name',
                    ]
                ]
            ],
        ];
    }

    /**
     * Test validation conflict categoryID vs categoryIDs
     *
     * @depends testSetup
     */
    public function testValidationConflict() {
        $this->expectExceptionMessage('Only one of categoryID, categoryIDs are allowed.');
        $this->api()->get('/search', ['categoryID' => 1, 'categoryIDs' => [1,2]]);
    }

    /**
     * Test searching in the title field.
     *
     * @depends testSetup
     */
    public function testSearchTitle() {
        /** @var DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(DiscussionModel::class);
        $discussionTitle = $discussionModel->save([
            'Name' => 'Title Query',
            'Body' => 'Body',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
        ]);

        $discussionWrongTitle = $discussionModel->save([
            'Name' => 'Wrong',
            'Body' => 'Title Query in body',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
        ]);

        $this->ensureIndexed();

        $this->assertSearchResultIDs(
            [
                'name' => 'Title Query',
            ],
            [$discussionTitle]
        );
    }

    /**
     * Test date ranges for the search query.
     *
     * @depends testSetup
     */
    public function testDates() {
        /** @var DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(DiscussionModel::class);
        $discussion7_1 = $discussionModel->save([
            'Name' => 'date test filter ananas 7 1',
            'Body' => '7 1',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00"
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => 'date test filter ananas 7 2',
            'Body' => '7 2',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00"
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => 'date test filter ananas 7 3',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-03 12:00:00"
        ]);

        $this->ensureIndexed();

        $this->assertSearchResultIDs(
            [
                'name' => 'filter ananas',
            ],
            [$discussion7_1, $discussion7_2, $discussion7_3]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '2019-07-03'
            ],
            [$discussion7_3]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '[2019-07-01, 2019-07-02]'
            ],
            [$discussion7_1, $discussion7_2]
        );

        $this->assertSearchResultIDs(
            [
                'query' => 'date test',
                'dateInserted' => '[2019-07-01, 2019-07-02)'
            ],
            [$discussion7_1]
        );
    }

    /**
     * Assert a search results in some particular result IDs.
     *
     * @param array $query
     * @param array $expectedIDs
     *
     * @depends testSetup
     */
    protected function assertSearchResultIDs(array $query, array $expectedIDs) {
        $response = $this->api()->get('/search', $query);
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $actualIDs = array_column($results, 'recordID');
        $this->assertIDsEqual($expectedIDs, $actualIDs);
    }

    /**
     * Test searching for discussion in followed categories.
     *
     * @depends testSetup
     */
    public function testFollowCategorySearch() {
        Gdn::database()->sql()->truncate('Discussion');
        Gdn::database()->sql()->truncate('Comment');
        $followedCategory = $this->createCategory(['name' => 'followed', 'urlCode' => 'followed']);
        $follow = $this->api()->put("categories/{$followedCategory['categoryID']}/follow", ['followed' => true]);
        $followBody = $follow->getBody();
        $this->assertTrue($followBody['followed']);

        $category = $this->createCategory(['name' => 'not followed', 'urlCode' => 'notFollowed']);

        $discussionInFollowed = $this->createDiscussion(
            [
                'name' => 'discussion 1',
                'categoryID' => $followedCategory['categoryID'],
            ]
        );

        $discussion2 = $this->createDiscussion(
            [
                'name' => 'discussion 2',
                'categoryID' => $category['categoryID']
            ]
        );

        $discussion3 = $this->createDiscussion(
            [
                'name' => 'discussion 3',
                'categoryID' => $category['categoryID']
            ]
        );

        $this->ensureIndexed();

        $params = [
            'query' => 'discussion',
            'followedCategories' => true
        ];

        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(1, count($results));
        $this->assertEquals('discussion 1', $results[0]['name']);
    }

    /**
     * Test searching for discussion in child categories.
     *
     * @depends testSetup
     */
    public function testSearchWithIncludeChildCategories() {
        Gdn::database()->sql()->truncate('Discussion');
        Gdn::database()->sql()->truncate('Comment');
        $parentCategory = $this->createCategory(
            [
                'name' => 'parentCategory',
                'urlCode' => 'parentCategory',
                'parentCategoryID' => -1
            ]
        );

        $discussionParentCategory = $this->createDiscussion(
            [
                'name' => 'discussion 1',
                'categoryID' => $parentCategory['categoryID'],
            ]
        );
        $childCategory = $this->createCategory(
            [
                'name' => 'childCategory',
                'urlCode' => 'childCategory',
                'parentCategoryID' => $parentCategory['categoryID']
            ]
        );

        $discussionInChildCategory = $this->createDiscussion(
            [
                'name' => 'discussion 2',
                'categoryID' => $childCategory['categoryID']
            ]
        );

        $this->ensureIndexed();

        $params = [
            'query' => 'discussion',
            'categoryID' =>  $parentCategory['categoryID'],
            'includeChildCategories' => true,
        ];

        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(2, count($results));
        $this->assertEquals(true, 'discussion 2' === $results[1]['name'] || 'discussion 2' === $results[0]['name']);
    }

    /**
     * Test searching for discussion in child categories.
     *
     * @depends testSetup
     */
    public function testSearchWithIncludeArchivedCategories() {
        Gdn::database()->sql()->truncate('Discussion');
        Gdn::database()->sql()->truncate('Comment');
        $archivedCategory = $this->createCategory(
            [
                'name' => 'archivedCategory',
                'urlCode' => 'archivedCategory'
            ]
        );

        $discussionArchivedCategory = $this->createDiscussion(
            [
                'name' => 'discussion 1',
                'categoryID' => $archivedCategory['categoryID'],
            ]
        );

        $nonArchivedCategory = $this->createCategory(
            [
                'name' => 'nonArchivedCategory',
                'urlCode' => 'nonArchivedCategory'
            ]
        );

        $discussionNonArchivedCategory = $this->createDiscussion(
            [
                'name' => 'discussion 2',
                'categoryID' => $nonArchivedCategory['categoryID'],
            ]
        );

        /** @var CategoryModel $categoryModel */
        $categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        $categoryModel->setField($archivedCategory['categoryID'], ['Archived' => 1]);

        $this->ensureIndexed();

        $params = [
            'query' => 'discussion',
            'includeArchivedCategories' => true,
        ];

        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(2, count($results));
    }

    /**
     * Test searching for discussion by tag
     */
    public function testSearchDiscussionTags() {
        Gdn::sql()->truncate('Discussion');
        Gdn::sql()->truncate('Comment');

        $category = $this->createCategory(
            [
                'name' => 'taggedCategory',
                'urlCode' => 'taggedCategory'
            ]
        );

        /** @var TagModel $tagModel */
        $tagModel = Gdn::getContainer()->get(TagModel::class);

        $tag1 = $tagModel->save([
            'Name' => 'tag1',
            'FullName' => 'tag for discussion 1',
        ]);

        $tag2 = $tagModel->save([
            'Name' => 'tag2',
            'FullName' => 'tag for discussion 2',
        ]);


        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);

        $discussion1 = $discussionModel->save([
            'Name' => 'tagged discussion 1',
            'Body' => 'discussion 2',
            'Format' => 'markdown',
            'CategoryID' => $category['categoryID'],
            'Tags' => 'tag1'
        ]);

        $discussion2 = $discussionModel->save([
            'Name' => 'tagged discussion 2',
            'Body' => 'discussion 2',
            'Format' => 'markdown',
            'CategoryID' => $category['categoryID'],
            'Tags' => 'tag2'
        ]);

        $discussion3 = $discussionModel->save([
            'Name' => 'tagged discussion 3',
            'Body' => 'discussion 3',
            'Format' => 'markdown',
            'CategoryID' => $category['categoryID'],
            'Tags' => 'random'
        ]);

        $this->ensureIndexed();

        $params = [
            'query' => 'discussion',
            'recordTypes' => ['discussion'],
            'tags' => ['tag1', 'tag5'],
        ];

        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(1, count($results));
        $this->assertEquals('tagged discussion 1', $results[0]['name']);

        $discussionModel->save([
            "CategoryID" => $category["categoryID"],
            "DiscussionID" => $discussion1,
            "Tags" => 'tag1, tag2',
        ]);

        $this->ensureIndexed();

        $params = [
            'tags' => ['tag1', 'tag2'],
            'tagOperator' => 'and',
            'types' => ['discussion'],
        ];

        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(1, count($results));
        $this->assertEquals('tagged discussion 1', $results[0]['name']);
    }

    /**
     * Test that our expansion of records works correctly.
     *
     * @depends testSetup
     */
    public function testExpandExcerpts() {
        $response = $this->api()->get('/search', ['expand' => ['excerpt', '-body']]);
        $body = $response->getBody();
        $this->assertTrue(count($body) > 0);
        foreach ($body as $row) {
            $this->assertArrayHasKey('excerpt', $row);
            $this->assertArrayNotHasKey('body', $row);
        }
    }

    /**
     * Test that explicit sorting works.
     */
    public function testSorts() {
        $this->clearIndexes();
        /** @var \DiscussionModel $discussionModel */
        $discussionModel = self::container()->get(\DiscussionModel::class);
        $discussion7_1 = $discussionModel->save([
            'Name' => '3 sort test 7 1 relevance',
            'Body' => '7 1',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00",
            'Points' => 1,
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => '1 sort test 7 2 relevance important',
            'Body' => '7 2 sort test',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00",
            'Points' => 3,
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => '2 sort test 7 3 relevance',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$data['category1']['categoryID'],
            'DateInserted' => "2019-07-03 12:00:00",
            'Points' => 2,
        ]);

        $this->ensureIndexed();

        $this->assertSearchResultIDs([
            'query' => 'sort test',
            'sort' => 'dateInserted'
        ], [$discussion7_3, $discussion7_2, $discussion7_1]);

        $this->assertSearchResultIDs([
            'query' => 'sort test',
            'sort' => '-dateInserted'
        ], [$discussion7_1, $discussion7_2, $discussion7_3]);

        $this->assertSearchResultIDs([
            'query' => 'sort test',
            'sort' => 'relevance'
        ], [$discussion7_2, $discussion7_3, $discussion7_1]);

        $this->assertSearchResultIDs([
            'query' => 'sort test',
        ], [$discussion7_2, $discussion7_3, $discussion7_1]);
    }
}

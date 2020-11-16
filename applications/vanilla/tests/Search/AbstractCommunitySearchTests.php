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
    protected static $category;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $discussionAugust;

    /** @var array */
    protected static $comment;

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
//        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');
        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        $tmp = uniqid('category_');
        self::$category = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$discussion = $discussionsAPIController->post([
            'name' => $tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        self::$discussionAugust = $discussionsAPIController->post([
            'name' => 'August test 2019',
            'body' => 'August test 2019',
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);

        $tmp = $tmp . ' ' . uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

//        $session->end();
        $this->ensureIndexed();
        $this->assertTrue(true);
    }

    /**
     * Test search scoped to discussions.
     *
     * @depends testSetup
     */
    public function testRecordTypesDiscussion() {
        $params = [
            'query' => self::$discussion['name'],
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }

    /**
     * Test search scoped to comments.
     *
     * @depends testSetup
     */
    public function testRecordTypesComment() {
        $params = [
            'query' => self::$comment['rawBody'],
            'recordTypes' => 'comment',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     *
     * @depends testSetup
     */
    public function testExistingDiscussionID() {
        $params = [
            'query' => self::$discussion['name'],
            'discussionID' => self::$discussion['discussionID'],
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Should return 2 both: discussion and comment for it
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search scoped to a non existing discussion.
     *
     * @depends testSetup
     */
    public function testNonExistingDiscussionID() {
        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => 999999,
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertTrue(count($results) === 0);
    }

    /**
     * Test search scoped to a category.
     *
     * @depends testSetup
     */
    public function testExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => self::$category['categoryID'],
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search scoped to a non existing category.
     *
     * @depends testSetup
     */
    public function testNonExistingCategoryID() {
        $params = [
            'query' => self::$discussion['name'],
            'categoryID' => 777,
        ];
        $api = $this->api();
        $response = $api->get('/search?' . http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(0, count($results));
    }


    /**
     * Test search by user names.
     *
     * @depends testSetup
     */
    public function testInsertUserNames() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserNames' => 'circleci, daffewfega',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
    }

    /**
     * Test search by user IDs
     *
     * @depends testSetup
     */
    public function testInsertUserIDs() {
        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => '1,2',
        ];
        $response = $this->api()->get('/search?' . http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        // Correct value is 2.
        // Partially fixed https://github.com/vanilla/internal/issues/1963
        $this->assertEquals(2, count($results));
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
            'CategoryID' => self::$category['categoryID'],
        ]);

        $discussionWrongTitle = $discussionModel->save([
            'Name' => 'Wrong',
            'Body' => 'Title Query in body',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
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
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00"
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => 'date test filter ananas 7 2',
            'Body' => '7 2',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00"
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => 'date test filter ananas 7 3',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
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
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-01 12:00:00",
            'Points' => 1,
        ]);

        $discussion7_2 = $discussionModel->save([
            'Name' => '1 sort test 7 2 relevance important',
            'Body' => '7 2 sort test',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
            'DateInserted' => "2019-07-02 12:00:00",
            'Points' => 3,
        ]);

        $discussion7_3 = $discussionModel->save([
            'Name' => '2 sort test 7 3 relevance',
            'Body' => '7 3',
            'Format' => 'markdown',
            'CategoryID' => self::$category['categoryID'],
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

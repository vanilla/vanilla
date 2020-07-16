<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Garden\Schema\Schema;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Class SearchQuestionTest
 */
class SearchQuestionTest extends AbstractAPIv2Test {

    /** @var array */
    protected static $category;

    /** @var array */
    protected static $questionCategory;

    /** @var array */
    protected static $discussion;

    /** @var array */
    protected static $question;

    /** @var array */
    protected static $discussionAugust;

    /** @var array */
    protected static $comment;

    /** @var Schema */
    protected static $searchResultSchema;

    /** @var bool */
    protected static $sphinxReindexed;

    /** @var bool */
    protected static $dockerResponse;

    protected static $addons = ['vanilla', 'advancedsearch', 'sphinx', 'qna'];

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        saveToConfig('Plugins.Sphinx.UseDeltas', true);

        /** @var \Gdn_Session $session */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');
        /** @var \DiscussionsApiController $discussionsAPIController */
        $discussionsAPIController = static::container()->get('DiscussionsApiController');
        /** @var \CommentsApiController $commentAPIController */
        $commentAPIController = static::container()->get('CommentsApiController');

        /** @var PostController $postController */
        $postController = static::container()->get(PostController::class);

        $tmp = uniqid('category_');
        self::$category = $categoriesAPIController->post([
            'name' => $tmp,
            'urlcode' => $tmp,
        ]);

        $tmp = uniqid('discussion_');
        self::$discussion = $discussionsAPIController->post([
            'name' => 'Discussion for tests '.$tmp,
            'body' => $tmp,
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);
        self::$discussion['rawBody'] = $tmp;

        self::$discussionAugust = $discussionsAPIController->post([
            'name' => 'discussion for tests '.'August test 2019',
            'body' => 'August test 2019',
            'format' => 'markdown',
            'categoryID' => self::$category['categoryID'],
        ]);

        $tmp = uniqid('comment_');
        self::$comment = $commentAPIController->post([
            'body' => $tmp,
            'format' => 'markdown',
            'discussionID' => self::$discussion['discussionID'],
        ]);
        self::$comment['rawBody'] = $tmp;

        self::$questionCategory = $categoriesAPIController->post([
            'name' => 'Question Test',
            'urlcode' => 'questiontest',
        ]);


        /** @var SearchApiController $searchAPIController */
        $searchAPIController = static::container()->get('SearchApiController');
        self::$searchResultSchema = $searchAPIController->fullSchema();

        $session->end();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
        parent::setUp();

        if (empty(self::$question)) {
            $tmp = uniqid('question_discussion_');

            self::$question = $this->api()->post('discussions/question', [
                'categoryID' => self::$questionCategory['categoryID'],
                'name' => 'discussion Test Question '.$tmp,
                'body' => 'Hello world!',
                'format' => 'markdown',
            ]);

            self::$question['rawBody'] = $tmp;
            self::SphinxReindex();
        }
    }

    /**
     * Call terminal sphinx reindex command line
     */
    public static function sphinxReindex() {
        $sphinxHost = c('Plugins.Sphinx.Server');
        $r = exec('curl -s '.$sphinxHost.':9399', $dockerResponse);
        self::$dockerResponse = $dockerResponse;
        self::$sphinxReindexed = ('Sphinx reindexed.' === end($dockerResponse));
        sleep(1);
    }

    /**
     * Test search scoped to discussions.
     */
    public function testRecordTypesDiscussion() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => 'discussion',
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }

    /**
     * Test search scoped to discussion original type (not a question or poll).
     */
    public function testTypesDiscussion() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => 'discussion',
            'recordTypes' => 'discussion',
            'types' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(2, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
    }


    /**
     * Test search scoped to comments.
     */
    public function testRecordTypesComment() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => 'discussion',
            'recordTypes' => 'comment',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to question.
     */
    public function testRecordTypesQuestion() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => 'discussion',
            'recordTypes' => 'discussion',
            'types' => 'question'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
        $this->assertRowsEqual(['type' => 'question'], $results[0]);
    }

    /**
     * Test search scoped to a discussion.
     */
    public function testExistingDiscussionID() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$comment['rawBody'],
            'discussionID' => self::$discussion['discussionID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(1, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
    }

    /**
     * Test search scoped to a category.
     */
    public function testExistingCategoryID() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => 'discussion',
            'categoryID' => self::$category['categoryID'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));

        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(3, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search by user IDs
     */
    public function testInsertUserIDs() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => '1, 2',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(4, count($results));
        foreach ($results as $result) {
            self::$searchResultSchema->validate($result);
        }
    }

    /**
     * Test search by non-existing user IDs
     */
    public function testWrongInsertUserIDs() {
        if (!self::$sphinxReindexed) {
            $this->fail('Can\'t reindex Sphinx indexes!' . "\n" . end(self::$dockerResponse));
        }

        $params = [
            'query' => self::$discussion['name'],
            'insertUserIDs' => 404,
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        $this->assertEquals(0, count($results));
    }
}

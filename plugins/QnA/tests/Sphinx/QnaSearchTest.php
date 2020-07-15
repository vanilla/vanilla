<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Search\CommentSearchType;
use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\QnA\Models\AnswerSearchType;
use Vanilla\QnA\Models\QuestionSearchType;
use Vanilla\Search\SearchService;
use Vanilla\Sphinx\Search\SphinxSearchDriver;
use Vanilla\Sphinx\Tests\Utils\SphinxTestTrait;
use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Tests for Question and Answer Search types.
 */
class QnaSearchTest extends AbstractAPIv2Test {

    use SphinxTestTrait;

    /**
     * @var $lastInsertedCategoryID
     */
    private $lastInsertedCategoryID;

    protected static $addons = ['vanilla', 'advancedsearch', 'sphinx', 'qna'];

    /**
     * @inheritdoc
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        \Gdn::config()->saveToConfig('Feature.useSearchService.Enabled', true);
        saveToConfig('Plugins.Sphinx.UseDeltas', true);

        self::container()
            ->rule(SearchService::class)
            ->addCall('registerActiveDriver', [new \Garden\Container\Reference(SphinxSearchDriver::class)])
            ->rule(SphinxSearchDriver::class)
            ->addCall('registerSearchType', [new \Garden\Container\Reference(DiscussionSearchType::class)])
            ->addCall('registerSearchType', [new \Garden\Container\Reference(CommentSearchType::class)])
            ->addCall('registerSearchType', [new \Garden\Container\Reference(QuestionSearchType::class)])
            ->addCall('registerSearchType', [new \Garden\Container\Reference(AnswerSearchType::class)])
        ;
    }

    /**
     * Clear info between tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->lastInsertedCategoryID = null;
    }
    /**
     * @inheritdoc
     */
    public static function teardownAfterClass(): void {
        FeatureFlagHelper::clearCache();
    }


    /**
     * Test searching for only questions
     */
    public function testSearchQuestion() {
        $this->resetTables();
        $category = $this->createCategory(['name' => 'category 1', 'urlCode' => 'category1']);

        $question1 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $question2 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $discussion = $this->createDiscussion([
            'categoryID' => $category['categoryID'],
            'name' => 'Discussion',
            'body' => 'Discussion'
        ]);
        self::SphinxReindex();

        $params = [
            'query' => 'Question 1',
            'recordTypes' => 'discussion',
            'types' => 'question'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(2, count($results));

        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
        $this->assertRowsEqual(['type' => 'question'], $results[0]);
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[1]);
        $this->assertRowsEqual(['type' => 'question'], $results[1]);
    }

    /**
     * Test searching for discussions with questions.
     */
    public function testSearchAnswers() {
        $this->resetTables();

        $category = $this->createCategory(['name' => 'category 2', 'urlCode' => 'category2']);

        $question = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $answer1 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 1',
            'body' => 'Answer 1'
        ]);

        $answer2 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 2',
            'body' => 'Answer 2'
        ]);

        $answer3 = $this->createAnswer([
            'discussionID' => $question['discussionID'],
            'name' => 'Answer 3',
            'body' => 'Answer 3'
        ]);

        self::SphinxReindex();

        $params = [
            'query' => 'Question',
            'recordTypes' => 'comment',
            'types' => 'answer'
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(3, count($results));

        $this->assertRowsEqual(['recordType' => 'comment'], $results[0]);
        $this->assertRowsEqual(['type' => 'answer'], $results[0]);
        $this->assertRowsEqual(['recordType' => 'comment'], $results[1]);
        $this->assertRowsEqual(['type' => 'answer'], $results[1]);
        $this->assertRowsEqual(['recordType' => 'comment'], $results[2]);
        $this->assertRowsEqual(['type' => 'answer'], $results[2]);
    }

    /**
     * Test searching for discussions with questions.
     */
    public function testSearchDiscussionsWithQuestions() {
        $this->resetTables();
        $category = $this->createCategory(['name' => 'category 3', 'urlCode' => 'category3']);

        $question1 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $question2 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $discussion = $this->createDiscussion([
            'categoryID' => $category['categoryID'],
            'name' => 'Not A Question',
            'body' => 'Discussion'
        ]);

        self::SphinxReindex();

        $params = [
            'query' => 'question',
            'recordTypes' => 'discussion',
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(3, count($results));

        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
        $this->assertRowsEqual(['type' => 'question'], $results[0]);
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[1]);
        $this->assertRowsEqual(['type' => 'question'], $results[1]);
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[2]);
        $this->assertRowsEqual(['type' => 'discussion'], $results[2]);
    }

    /**
     * Test searching for discussions with questions.
     */
    public function testSearchAllRecordTypes() {
        $this->resetTables();
        $category = $this->createCategory(['name' => 'category 4', 'urlCode' => 'category4']);

        $question1 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $question2 = $this->createQuestion([
            'categoryID' => $category['categoryID'],
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $discussion = $this->createDiscussion([
            'categoryID' => $category['categoryID'],
            'name' => 'Not A Question',
            'body' => 'Discussion'
        ]);

        $answer = $this->createAnswer([
            'discussionID' => $question1['discussionID'],
            'name' => 'Answer 1',
            'body' => 'Answer 1'
        ]);

        self::SphinxReindex();

        $params = [
            'query' => 'question',
            'recordTypes' => ['discussion', 'comment'],
            'types' => ['discussion', 'question', 'answer'],
        ];
        $response = $this->api()->get('/search?'.http_build_query($params));
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();
        $this->assertEquals(4, count($results));

        $this->assertRowsEqual(['recordType' => 'discussion'], $results[0]);
        $this->assertRowsEqual(['type' => 'question'], $results[0]);
        $this->assertRowsEqual(['recordType' => 'comment'], $results[1]);
        $this->assertRowsEqual(['type' => 'answer'], $results[1]);
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[2]);
        $this->assertRowsEqual(['type' => 'question'], $results[2]);
        $this->assertRowsEqual(['recordType' => 'discussion'], $results[3]);
        $this->assertRowsEqual(['type' => 'discussion'], $results[3]);
    }

    /**
     * Create a test category.
     *
     * @param array $body
     * @return array
     */
    public function createCategory(array $body = []): array {
        /** @var \CategoriesApiController $categoriesAPIController */
        $categoriesAPIController = static::container()->get('CategoriesApiController');

        $tmp = uniqid('category_');
        $category = $categoriesAPIController->post([
            'name' => $body['name'] ?? $tmp,
            'urlcode' => $body['urlCode'] ?? $tmp,
        ]);

        $this->lastInsertedCategoryID = $category['categoryID'];
        return $category;
    }

    /**
     * Create a Question.
     *
     * @param array $body
     * @return array
     */
    public function createQuestion(array $body = []): array {
        $categoryID = $body['categoryID'] ?? $this->lastInsertedCategoryID;
        if (!$categoryID) {
            $category = $this->createCategory();
            $categoryID = $category['categoryID'];
        }

        return $this->api()->post('discussions/question', [
            'categoryID' => $categoryID ?? $this->lastInsertedCategoryID,
            'name' => $body['name'] ?? 'Question',
            'body' => $body['body'] ?? 'Question being asked!',
            'format' => 'markdown',
        ])->getBody();
    }

    /**
     * Create a Question.
     *
     * @param array $body
     * @return array
     */
    public function createAnswer(array $body): array {
        if (!$body['discussionID']) {
            $question = $this->createQuestion();
            $body['discussionID'] = $question['discussionID'];
        }

        $record = [
            'discussionID' => $body['discussionID'],
            'body' => 'Answering',
            'format' => 'markdown',
        ];
        return $this->api()->post('comments', $record)->getBody();
    }

    /**
     * Create a Discussion.
     *
     * @param array $body
     * @return array
     */
    public function createDiscussion(array $body = []): array {
        $categoryID = $body['categoryID'] ?? $this->lastInsertedCategoryID;
        if (!$categoryID) {
            $category = $this->createCategory();
            $categoryID = $category['categoryID'];
        }
        return $this->api()->post('discussions', [
            'categoryID' => $categoryID ?? $this->lastInsertedCategoryID,
            'name' => $body['name'] ?? 'Discussion',
            'body' => $body['body'] ?? 'Discussion',
            'format' => 'markdown',
        ])->getBody();
    }

    /**
     * Reset necessary tables.
     */
    protected function resetTables(): void {
        Gdn::database()->sql()->truncate('Category');
        Gdn::database()->sql()->truncate('Discussion');
        Gdn::database()->sql()->truncate('Comment');
    }
}

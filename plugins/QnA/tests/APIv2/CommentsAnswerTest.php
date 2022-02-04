<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use Vanilla\CurrentTimeStamp;

/**
 * Test managing answers with the /api/v2/comments endpoint.
 */
class CommentsAnswerTest extends AbstractAPIv2Test {

    use QnaApiTestTrait;

    private static $category;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::$addons = ['vanilla', 'qna'];
        parent::setupBeforeClass();

        /**
         * @var \Gdn_Session $session
         */
        $session = self::container()->get(\Gdn_Session::class);
        $session->start(self::$siteInfo['adminUserID'], false, false);

        /** @var \CategoriesApiController $categoryAPIController */
        $categoryAPIController = static::container()->get('CategoriesApiController');

        self::$category = $categoryAPIController->post([
            'name' => 'answerTest',
            'urlcode' => 'answertest',
        ]);

        $session->end();
    }

    /**
     * Create a set of QnA discussions covering the range of answered statuses ('unanswered', 'answered', 'accepted', 'rejected').
     *
     * @return array The set of questions
     */
    public function createQuestionSet() {
        $category = $this->createCategory();
        $questionSet = [
            $this->createQuestion(['categoryID' => $category['categoryID']]),
            $this->testResetAnswer(),
            $this->testAcceptAnswer(),
            $this->testRejectAnswer(),
        ];

        return $questionSet;
    }

    /**
     * Get a question.
     *
     * @param int $discussionID
     * @return array The question.
     */
    protected function getQuestion($discussionID) {
        $response = $this->api()->get("discussions/$discussionID");
        $this->assertEquals(200, $response->getStatusCode());

        return $response->getBody();
    }

    /**
     * Test answer creation.
     *
     * @param int $discussionID If omitted the answer will be created on a new Question.
     * @return mixed
     */
    public function testPostAnswer($discussionID = null) {
        $category = $this->createCategory();
        if ($discussionID === null) {
            $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
            $discussionID = $question['discussionID'];
        }

        $record = [
            'discussionID' => $discussionID,
            'body' => 'Hello world!',
            'format' => 'markdown',
        ];
        $response = $this->api()->post('comments', $record);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();

        $this->assertTrue(is_int($body['commentID']));
        $this->assertTrue($body['commentID'] > 0);

        $this->assertRowsEqual($record, $body);

        $this->assertIsAnswer($body);

        $question = $this->getQuestion($discussionID);
        $this->assertIsQuestion($question, ['status' => 'answered']);

        return $body;
    }

    /**
     * Test getting an answer.
     *
     * @depends testPostAnswer
     */
    public function testGetAnswer() {
        $answer = $this->testPostAnswer();
        $commentID = $answer['commentID'];

        $response = $this->api()->get("comments/{$commentID}");
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsAnswer($body, ['status' => 'pending']);
    }

    /**
     * Test accepting an answer.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testAcceptAnswer() {
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'accepted',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ['status' => 'accepted']);

        $updatedQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($updatedQuestion, ['status' => 'accepted']);

        return $updatedQuestion;
    }

    /**
     * Test rejecting an answer.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testRejectAnswer() {
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'rejected',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ['status' => 'rejected']);

        $updatedQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($updatedQuestion, ['status' => 'rejected']);

        return $updatedQuestion;
    }

    /**
     * Test accepting and then setting back an answer to pending.
     *
     * @return array Returns the updated question.
     * @depends testPostAnswer
     */
    public function testResetAnswer() {
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'accepted',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'pending',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ['status' => 'pending']);

        $updatedQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($updatedQuestion, ['status' => 'answered']);

        return $updatedQuestion;
    }

    /**
     * Test accepting and then rejecting an answer.
     *
     * @depends testPostAnswer
     */
    public function testAcceptRejectAnswer() {
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'accepted',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'rejected',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertIsAnswer($response->getBody(), ['status' => 'rejected']);

        $updatedQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($updatedQuestion, ['status' => 'rejected']);
    }

    /**
     * Test rejecting an answer and then resubmitting an answer.
     *
     * @depends testPostAnswer
     */
    public function testResetRejectedQuestionStatus() {
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'rejected',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $updatedQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($updatedQuestion, ['status' => 'rejected']);

        $answerAgain = $this->testPostAnswer($question['discussionID']);
        $commentID = $answerAgain['commentID'];

        $response = $this->api()->get("comments/{$commentID}");
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody();
        $this->assertIsAnswer($body, ['status' => 'pending']);

        $answeredQuestion = $this->getQuestion($question['discussionID']);
        $this->assertIsQuestion($answeredQuestion, ['status' => 'answered']);
    }

    /**
     * Test dateAccepted and dateAnswered correspond with the accepted answer.
     *
     * @depends testPostAnswer
     */
    public function testAnsweredQuestionDates() {

        // Mock the current time.
        CurrentTimeStamp::mockTime('Dec 1 2010');
        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $this->assertIsQuestion($question, ['dateAccepted' => null]);

        $answer = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answer['commentID'], [
            'status' => 'accepted',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();

        $answeredQuestion = $this->getQuestion($question['discussionID']);
        $dateAnswered = $answeredQuestion['attributes']['question']['dateAnswered'];
        $this->assertEquals($answer['dateInserted'], $dateAnswered);
        $this->assertIsQuestion($answeredQuestion, ['dateAccepted' => $body['dateInserted']]);
        $this->assertIsQuestion($answeredQuestion, ['dateAnswered' => $body['dateInserted']]);
    }

    /**
     * Test dateAccepted and dateAnswered when answer is rejected.
     */
    public function testUnAnsweredQuestionDates() {

        $category = $this->createCategory();
        $question = $this->createQuestion(['categoryID' => $category['categoryID']]);
        $this->assertIsQuestion($question, ['dateAccepted' => null]);

        $answerNumberOne = $this->testPostAnswer($question['discussionID']);

        $response = $this->api()->patch('comments/answer/'.$answerNumberOne['commentID'], [
            'status' => 'rejected',
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $unansweredQuestion = $this->getQuestion($question['discussionID']);

        $this->assertIsQuestion($unansweredQuestion, ['dateAccepted' => null]);
        $this->assertIsQuestion($unansweredQuestion, ['dateAnswered' => null]);
    }

    /**
     * Test getting all unanswered questions as we do for the 'discussions/unanswered' endpoint.
     * This should get all (and only) questions with a status of 'unanswered' and 'rejected'.
     */
    public function testGetAllUnansweredQuestions() {
        $this->createQuestionSet();
        $unansweredQuestions = $this->bessy()->get("discussions/unanswered")->data("Discussions")->resultArray();

        foreach ($unansweredQuestions as $question) {
            $this->assertContains(strtolower($question['QnA']), ["unanswered", "rejected"]);
        }
    }
}

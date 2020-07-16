<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

require_once(__DIR__.'/QnaTestHelperTrait.php');

/**
 * Test managing questions with the /api/v2/discussions endpoint.
 */
class DiscussionsQuestionTest extends AbstractAPIv2Test {

    use QnaTestHelperTrait;

    /** @var int Category containing questions. */
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
            'name' => 'QuestionTest',
            'urlcode' => 'questiontest',
        ]);

        $session->end();
    }

    /**
     * Test /discussion/<id> includes question metadata.
     */
    public function testGetQuestion() {
        $row = $this->testPostQuestion();
        $discussionID = $row['discussionID'];

        $response = $this->api()->get("discussions/{$discussionID}");

        $this->assertEquals(200, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertIsQuestion($body, ['status' => 'unanswered']);

        return $body;
    }

    /**
     * Verify an question can be created with the discussions endpoint.
     */
    public function testPostQuestion() {
        $record = [
            'categoryID' => self::$category['categoryID'],
            'name' => 'Test Question',
            'body' => 'Hello world!',
            'format' => 'markdown',
        ];
        $response = $this->api()->post('discussions/question', $record);
        $this->assertEquals(201, $response->getStatusCode());

        $body = $response->getBody();
        $this->assertEquals('question', $body['type']);
        $this->assertIsQuestion($body, ['status' => 'unanswered']);

        $this->assertTrue(is_int($body['discussionID']));
        $this->assertTrue($body['discussionID'] > 0);

        $this->assertRowsEqual($record, $body);

        return $body;
    }

    /**
     * Verify questions can be queried from the discussions index.
     */
    public function testDiscussionsIndexQuestion() {
        // Add one discussion normal discussion to make sure that the index is properly filtered.
        $this->api()->post('discussions', [
            'categoryID' => self::$category['categoryID'],
            'name' => 'Test Discussion',
            'body' => 'Hello world!',
            'format' => 'markdown',
        ]);

        $indexPosts = 5;
        for ($i = 1; $i <= $indexPosts; $i++) {
            $this->testPostQuestion();
        }

        $response = $this->api()->get('discussions', ['type' => 'question']);
        $this->assertEquals(200, $response->getStatusCode());

        $questions = $response->getBody();
        $this->assertNotEmpty($questions);
        foreach ($questions as $question) {
            $this->assertIsQuestion($question);
        }
    }

    /**
     * Verify accepted answer comment IDs are returned in a discussion response.
     */
    public function testAcceptedAnswersInDiscussion() {
        // Create the question.
        $question = $this->testGetQuestion();

        // Create a few answers.
        $answers = [];
        for ($i = 1; $i <= 5; $i++) {
            $answers[] = $this->api()->post("comments", [
                "body" => "Hello world.",
                "discussionID" => $question["discussionID"],
                "format" => "Markdown",
            ])->getBody();
        }

        // FLag the first two answers as accepted.
        $this->api()->patch("comments/answer/".$answers[0]["commentID"], ["status" => "accepted"]);
        $this->api()->patch("comments/answer/".$answers[1]["commentID"], ["status" => "accepted"]);

        $discussion = $this->api()->get("discussions/".$question['discussionID'])->getBody();

        // Verify we have accepted answers.
        $this->assertArrayHasKey("acceptedAnswers", $discussion["attributes"]["question"], "No accepted answers.");
        $acceptedAnswers = $discussion["attributes"]["question"]["acceptedAnswers"];

        // Verify we have exactly two accepted answers.
        $this->assertEquals(2, count($acceptedAnswers), "Unexpected number of answers.");

        // Verify we have the correct two answers.
        $commentIDs = array_column($acceptedAnswers, "commentID");
        $this->assertContains($answers[0]["commentID"], $commentIDs);
        $this->assertContains($answers[1]["commentID"], $commentIDs);
    }
}

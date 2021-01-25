<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\APIv2;

use Gdn;

/**
 * Test managing questions with the /api/v2/discussions endpoint.
 */
class DiscussionsQuestionTest extends AbstractAPIv2Test {

    use QnaApiTestTrait;

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

        self::setupQnAFollowUpFeature();

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
     * Run a basic test of a question's HTML.
     */
    public function testGetQuestionHtml() {
        $row = $this->testPostQuestion();
        $discussionID = $row['discussionID'];

        $dom = $this->bessy()->getHtml("/discussion/{$discussionID}/xxx", [], ['deliveryType' => DELIVERY_TYPE_ALL]);
        $dom->assertCssSelectorExists('.dropdown-menu-link[href*="/discussion/qnaoptions"]');
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

    /**
     * Verifies if the number of answered questions is the same as notifications sent
     * In real life this endpoint is supposed to be called by a cron. But the test only calls the endpoint once
     * and it always blows the email time out threshold because tests are slow.
     * So for now we are testing with only one discussion.
     */
    public function testQuestionNotifications() {
        //make a few questions
        $questionsCount = 1;
        for ($i = 1; $i <= $questionsCount; $i++) {
            $discussion = $this->api()->post('discussions/question', [
                'categoryID' => self::$category['categoryID'],
                'name' => 'Test question '.$i,
                'body' => 'Content of question '.$i,
                'format' => 'markdown',
            ])->getBody();

            // create an answer
            $this->api()->post("comments", [
                "body" => "Here's some answer ".$i,
                "discussionID" => $discussion["discussionID"],
                "format" => "Markdown",
            ]);
        }

        $followUp = $this->api()->post('discussions/question-notifications')->getBody();
        $this->assertEquals($questionsCount, $followUp['notificationsSent'], "Asserts if notificationsSent equals discussions created.");

        // make a second call to make sure we are not spamming the user, this time it should not send notifications.
        $followUpNoNotifications = $this->api()->post('discussions/question-notifications')->getBody();
        $this->assertEquals(0, $followUpNoNotifications['notificationsSent']);
    }

    /**
     * Verifies if notifications is sent when posting a discussionID
     */
    public function testQuestionNotificationsWithDiscussionID() {
        //make a question
        $discussion = $this->api()->post('discussions/question', [
            'categoryID' => self::$category['categoryID'],
            'name' => 'Test question',
            'body' => 'Content of question',
            'format' => 'markdown',
        ])->getBody();

        // create an answer
        $this->api()->post("comments", [
            "body" => "Here's some answer",
            "discussionID" => $discussion["discussionID"],
            "format" => "Markdown",
        ]);

        $followUp = $this->api()->post('discussions/question-notifications', ['discussionID' => $discussion["discussionID"]])->getBody();
        $this->assertEquals(1, $followUp['notificationsSent'], "Asserts notificationsSent equals 1.");

        // make a second call to make sure we are not spamming the user, this time it should not send notifications.
        $followUpNoNotifications = $this->api()->post(
            'discussions/question-notifications',
            ['discussionID' => $discussion["discussionID"]]
        )->getBody();
        $this->assertEquals(0, $followUpNoNotifications['notificationsSent']);
    }

    /**
     * Perform the setup done by QnAPlugin->structure() once the feature flag is enabled
     */
    private static function setupQnAFollowUpFeature() {
        // enable feature flag
        /** @var \Gdn_Configuration $config */
        $config = static::container()->get(\Gdn_Configuration::class);
        $config->set('Feature.'.\QnAPlugin::FOLLOWUP_FLAG.'.Enabled', true, true, false);

        // add user preference
        $config->touch(['Preferences.Email.QuestionFollowUp' => 1]);

        // add category DB column
        // by default this is set to 0 and enabled per category, but just for testing purpose I'm setting the default to 1
        Gdn::structure()
            ->table('Category')
            ->column('QnaFollowUpNotification', 'tinyint(1)', ['Null' => false, 'Default' => 1])
            ->set();
    }
}

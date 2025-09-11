<?php

use Vanilla\OpenAI\OpenAIClient;
use VanillaTests\Fixtures\OpenAI\MockOpenAIClient;

/**
 * Test the AI suggestions cron job.
 */
class AiSuggestionsJobTest extends \VanillaTests\SiteTestCase
{
    use \VanillaTests\Dashboard\AiSuggestionsTestTrait;
    use \VanillaTests\SchedulerTestTrait;
    use \VanillaTests\Forum\Utils\CommunityApiTestTrait;
    use \VanillaTests\APIv2\QnaApiTestTrait;

    private \Vanilla\Dashboard\AiSuggestionModel $aiSuggestionModel;

    protected static $addons = ["vanilla", "dashboard", "qna"];

    const VALID_SETTINGS = [
        "enabled" => true,
        "name" => "JarJarBinks",
        "icon" => "https://www.example.com/icon.png",
        "toneOfVoice" => "professional",
        "levelOfTech" => "advanced",
        "useBrEnglish" => true,
        "sources" => [
            "mockSuggestion" => [
                "enabled" => true,
            ],
        ],
        "delay" => [
            "unit" => "hour",
            "length" => 6,
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $mockOpenAIClient = \Gdn::getContainer()->get(MockOpenAIClient::class);
        $mockOpenAIClient->addMockResponse("/This is how you do this./", [
            "answerSource" => "This is how you do this.",
        ]);
        $mockOpenAIClient->addMockResponse("/This is how you do this a different way./", [
            "answerSource" => "This is how you do this a different way.",
        ]);
        $mockOpenAIClient->addMockResponse("/This is how you do this, a third way./", [
            "answerSource" => "This is how you do this, a third way.",
        ]);
        \Gdn::getContainer()->setInstance(OpenAIClient::class, $mockOpenAIClient);
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->aiSuggestionModel = $this->container()->get(\Vanilla\Dashboard\AiSuggestionModel::class);
        parent::setUp();
        \Vanilla\CurrentTimeStamp::mockTime("Jan 1 2022");
        self::enableFeature("AISuggestions");
        self::enableFeature("aiFeatures");
        \Gdn::config()->saveToConfig("VanillaAnalytics.AnonymizeData", false);
        self::enableFeature("customLayout.post");
        $this->api()->patch("/ai-suggestions/settings", self::VALID_SETTINGS);
    }

    /**
     * Test the AI suggestions cron job.
     *
     * @return void
     */
    public function testAiSuggestionsJob(): void
    {
        // We have a 6-hour delay on creating ai suggestions to unanswered questions. We'll create
        // three questions at different times.
        $earliestQuestion = $this->createQuestion(); // created Jan 1 2022

        \Vanilla\CurrentTimeStamp::mockTime("Jan 2 2022 13:00");
        $moreRecentQuestion = $this->createQuestion();
        $questionWithAnswer = $this->createQuestion();
        $this->createAnswer();

        \Vanilla\CurrentTimeStamp::mockTime("Jan 2 2022 16:00");
        $mostRecentQuestion = $this->createQuestion();

        \Vanilla\CurrentTimeStamp::mockTime("Jan 2 2022 19:30");

        // When we run this job, the `moreRecentQuestion` has exceeded the 6-hour delay and is
        // within out 1-hour lookback window. The `earliestQuestion` is outside the lookback window
        // and the `mostRecentQuestion` has not exceeded the delay.
        $this->getScheduler()->addJob(AiSuggestionJob::class);

        // We shouldn't have any suggestions for the earliest question -- it's outside the lookback window.
        $earliestQuestionSuggestions = $this->aiSuggestionModel->getByDiscussionID($earliestQuestion["discussionID"]);
        $this->assertEmpty($earliestQuestionSuggestions);

        // We should have suggestions for the more recent question.
        $moreRecentQuestionSuggestions = $this->aiSuggestionModel->getByDiscussionID(
            $moreRecentQuestion["discussionID"]
        );
        $this->assertNotEmpty($moreRecentQuestionSuggestions);
        $this->assertSame("mockSuggestion", $moreRecentQuestionSuggestions[0]["type"]);

        // We shouldn't have any suggestions for the question with an answer. It meets the time-based
        // criteria, but it has an answer.
        $questionWithAnswerSuggestions = $this->aiSuggestionModel->getByDiscussionID(
            $questionWithAnswer["discussionID"]
        );
        $this->assertEmpty($questionWithAnswerSuggestions);

        // We shouldn't have any suggestions for the most recent question -- it hasn't exceeded the delay.
        $mostRecentQuestionSuggestions = $this->aiSuggestionModel->getByDiscussionID(
            $mostRecentQuestion["discussionID"]
        );
        $this->assertEmpty($mostRecentQuestionSuggestions);

        // Jump ahead 3 hours, and the most recent question is 6.5 hours old.
        \Vanilla\CurrentTimeStamp::mockTime("Jan 2 2022 22:30");

        $this->getScheduler()->addJob(AiSuggestionJob::class);

        // Now we should have suggestions for the most recent question -- it's older than the delay and
        // within our lookback window.
        $mostRecentQuestionSuggestions = $this->aiSuggestionModel->getByDiscussionID(
            $mostRecentQuestion["discussionID"]
        );
        $this->assertNotEmpty($mostRecentQuestionSuggestions);
        $this->assertSame("mockSuggestion", $mostRecentQuestionSuggestions[0]["type"]);
    }
}

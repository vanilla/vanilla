<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\QnA;

use Garden\Events\ResourceEvent;
use QnAPlugin;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\QnA\Events\AnswerEvent;
use Vanilla\QnA\Models\AnswerModel;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\VanillaTestCase;

/**
 * Test QnA events are working.
 */
class QnAEventsTest extends VanillaTestCase
{
    use SiteTestTrait, SetupTraitsTrait, EventSpyTestTrait, QnaApiTestTrait;

    /** @var AnswerModel */
    private $answerModel;

    /** @var int */
    private $categoryID;

    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var \QnAPlugin */
    private $plugin;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        return ["vanilla", "qna"];
    }

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpTestTraits();
        $this->enableCaching();

        $this->categoryID = $this->createCategory()["categoryID"];

        $this->container()->call(function (
            \QnAPlugin $plugin,
            AnswerModel $answerModel,
            \DiscussionModel $discussionModel
        ) {
            $this->answerModel = $answerModel;
            $this->discussionModel = $discussionModel;
            $this->plugin = $plugin;
        });
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::setUpBeforeClassTestTraits();
    }

    /**
     * Tests events dispatched when submitting and updating answers to a question:
     * - Submitting a question should show an inserted discussion as "unanswered"
     * - First answer submitted should show the discussion as "answered"
     * - Rejecting first answer should show the discussion is "rejected"
     * - Second answer should show the discussion as "answered"
     * - Once second answer is accepted, should show the discussion is "accepted"
     */
    public function testQnaChosenAnswerEvent()
    {
        // Ask a question, verify discussion insert event dispatched
        $question = $this->createQuestion([
            "categoryID" => $this->categoryID,
            "name" => "Question 1",
            "body" => "Question 1",
        ]);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, ResourceEvent::ACTION_INSERT, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "name" => "Question 1",
                "categoryID" => $this->categoryID,
                "statusID" => QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
            ])
        );

        // First answer submitted - discussion is "answered"
        $answer1 = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 1",
            "body" => "Answer 1",
        ]);

        $matchingDispatchedEvent = $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, DiscussionStatusEvent::ACTION_DISCUSSION_STATUS, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "name" => "Question 1",
                "categoryID" => $this->categoryID,
                "statusID" => QnAPlugin::DISCUSSION_STATUS_ANSWERED,
            ])
        );

        $assertStatusPayload = function (ResourceEvent $event, int $expectedStatusID, string $expectedStatusName) {
            $payload = $event->getPayload();
            $this->assertArrayHasKey("status", $payload);
            $this->assertThat(
                $payload["status"],
                $this->logicalAnd($this->arrayHasKey("statusID"), $this->arrayHasKey("name"))
            );
            $this->assertEquals($expectedStatusID, $payload["status"]["statusID"]);
            $this->assertEquals($expectedStatusName, $payload["status"]["name"]);
        };

        $assertStatusPayload($matchingDispatchedEvent, QnAPlugin::DISCUSSION_STATUS_ANSWERED, "Answered");

        // First answer is rejected - both answer and discussion "rejected"
        $patchResponse = $this->api()->patch("/comments/{$answer1["commentID"]}/answer", ["status" => "rejected"]);
        $this->assertTrue($patchResponse->isSuccessful());

        $this->assertEventDispatched(
            $this->expectedResourceEvent(AnswerEvent::class, AnswerEvent::ACTION_ANSWER_REJECTED, [
                "commentID" => $answer1["commentID"],
                "discussionID" => $question["discussionID"],
                "qnA" => "Rejected",
            ])
        );

        $matchingDispatchedEvent = $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, DiscussionStatusEvent::ACTION_DISCUSSION_STATUS, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "name" => "Question 1",
                "categoryID" => $this->categoryID,
                "statusID" => QnAPlugin::DISCUSSION_STATUS_REJECTED,
            ])
        );
        $assertStatusPayload($matchingDispatchedEvent, QnAPlugin::DISCUSSION_STATUS_REJECTED, "Rejected");

        // Second answer submitted - discussion status back to answered
        $answer2 = $this->createAnswer([
            "discussionID" => $question["discussionID"],
            "name" => "Answer 2",
            "body" => "Answer 2",
        ]);

        $matchingDispatchedEvent = $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, DiscussionStatusEvent::ACTION_DISCUSSION_STATUS, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "name" => "Question 1",
                "categoryID" => $this->categoryID,
                "statusID" => QnAPlugin::DISCUSSION_STATUS_ANSWERED,
            ])
        );
        $assertStatusPayload($matchingDispatchedEvent, QnAPlugin::DISCUSSION_STATUS_ANSWERED, "Answered");

        // Accept second answer - both answer and discussion updated as "accepted"
        $patchResponse = $this->api()->patch("/comments/{$answer2["commentID"]}/answer", ["status" => "accepted"]);
        $this->assertTrue($patchResponse->isSuccessful());

        $this->assertEventDispatched(
            $this->expectedResourceEvent(AnswerEvent::class, AnswerEvent::ACTION_ANSWER_ACCEPTED, [
                "commentID" => $answer2["commentID"],
                "discussionID" => $question["discussionID"],
                "qnA" => "Accepted",
            ])
        );

        $matchingDispatchedEvent = $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, DiscussionStatusEvent::ACTION_DISCUSSION_STATUS, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "name" => "Question 1",
                "categoryID" => $this->categoryID,
                "statusID" => QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
            ])
        );
        $assertStatusPayload($matchingDispatchedEvent, QnAPlugin::DISCUSSION_STATUS_ACCEPTED, "Accepted");
    }

    /**
     * Verify basic functionality of count limiting for unanswered questions.
     */
    public function testUnansweredLimit(): void
    {
        $previousLimit = $this->plugin->getUnansweredCountLimit();
        try {
            $limit = 3;
            $this->plugin->setUnansweredCountLimit($limit);
            $this->createQuestion();
            $this->createQuestion();
            $this->createQuestion();
            $result = $this->bessy()->getJsonData("/discussions/unansweredcount");
            $this->assertSame("{$limit}+", $result["UnansweredCount"]);
        } finally {
            $this->plugin->setUnansweredCountLimit($previousLimit);
        }
    }
}

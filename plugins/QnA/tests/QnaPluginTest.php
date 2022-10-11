<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA;

use Garden\Events\ResourceEvent;
use QnAPlugin;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Community\Events\DiscussionStatusEvent;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test QnA plugin class.
 */
class QnAPluginTest extends SiteTestCase
{
    use QnaApiTestTrait;
    use EventSpyTestTrait;

    public static $addons = ["vanilla", "QnA"];

    /**
     * Test discussionModel_afterStatusUpdate_handler
     *
     * @param int $statusID New status ID of the
     * @param string $qnaStatus Expected QnA status.
     * @dataProvider discussionStatusUpdates
     */
    public function testHandleDiscussionStatusUpdateEvent(int $statusID, string $qnaStatus): void
    {
        $question = $this->createQuestion();
        $answer = $this->createAnswer();
        $this->acceptAnswer($question, $answer);
        $discussionStatusModel = $this->container()->get(\DiscussionStatusModel::class);

        $discussionStatusModel->updateDiscussionStatus($question["discussionID"], $statusID, $qnaStatus);

        $matchingEvent = $this->assertEventDispatched(
            $this->expectedResourceEvent(DiscussionEvent::class, DiscussionStatusEvent::ACTION_DISCUSSION_STATUS, [
                "discussionID" => $question["discussionID"],
                "type" => "question",
                "statusID" => $statusID,
            ])
        );
        $this->assertStatusEventPayload($matchingEvent, $statusID);
    }

    /**
     * Set of assertions related to the status included in a discussion event when a status is updated
     *
     * @param ResourceEvent $event
     * @param int $expectedStatusID
     */
    private function assertStatusEventPayload(ResourceEvent $event, int $expectedStatusID): void
    {
        $payload = $event->getPayload();
        $this->assertArrayHasKey("status", $payload);
        $this->assertThat(
            $payload["status"],
            $this->logicalAnd($this->arrayHasKey("statusID"), $this->arrayHasKey("name"))
        );
        $this->assertEquals($expectedStatusID, $payload["status"]["statusID"]);
    }

    /**
     * Data provider for testUpdateDiscussionStatus method
     * @return array
     */
    public function discussionStatusUpdates(): array
    {
        $result = [
            "change Status to StatusID " . QnAPlugin::DISCUSSION_STATUS_ACCEPTED => [
                QnAPlugin::DISCUSSION_STATUS_ACCEPTED,
                "Accepted",
            ],
            "change Status to StatusID " . QnAPlugin::DISCUSSION_STATUS_ANSWERED => [
                QnAPlugin::DISCUSSION_STATUS_ANSWERED,
                "Answered",
            ],
            "change Status to StatusID " . QnAPlugin::DISCUSSION_STATUS_REJECTED => [
                QnAPlugin::DISCUSSION_STATUS_REJECTED,
                "Rejected",
            ],
            "change Status to StatusID " . QnAPlugin::DISCUSSION_STATUS_UNANSWERED => [
                QnAPlugin::DISCUSSION_STATUS_UNANSWERED,
                "Unanswered",
            ],
        ];

        return $result;
    }

    /**
     * Test status change when converting question type w/o accepted answers to discussion.
     * @return array
     */
    public function testConvertQuestionWithoutAcceptedAnswersToDiscussion(): array
    {
        $discussionID = $this->createQuestion()["discussionID"];
        $answer = $this->createAnswer();
        $this->bessy()->post("/discussion/qnaoptions?discussionid={$discussionID}", ["Type" => "Discussion"]);
        $expectedStatusID = 0;
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $this->assertEquals($expectedStatusID, $discussion["statusID"]);
        return $discussion;
    }

    /**
     *  Test status change when converting question type with accepted answers to discussion.
     */
    public function testConvertAcceptedAnswerQuestionToDiscussion()
    {
        $question = $this->createQuestion();
        $answer = $this->createAnswer();
        $this->acceptAnswer($question, $answer);
        $discussionID = $question["discussionID"];
        $this->bessy()->post("/discussion/qnaoptions?discussionid={$discussionID}", ["Type" => "Discussion"]);
        $expectedStatusID = 0;
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $this->assertEquals($expectedStatusID, $discussion["statusID"]);
        return $discussion;
    }

    /**
     *  Test status is moved back to accepted when the discussion is converted back to Question
     *  @param array $discussion  Question converted to a discussion to be converted back into Question.
     *  @depends testConvertAcceptedAnswerQuestionToDiscussion
     */
    public function testDiscussionBackToQna(array $discussion)
    {
        $discussionID = $discussion["DiscussionID"];
        $this->bessy()->post("/discussion/qnaoptions?discussionid={$discussionID}", ["Type" => "Question"]);
        $discussionModel = $this->container()->get(\DiscussionModel::class);
        $question = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $this->assertEquals(QnAPlugin::DISCUSSION_STATUS_ACCEPTED, $question["statusID"]);
    }
}

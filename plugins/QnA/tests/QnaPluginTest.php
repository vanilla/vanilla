<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace QnA\Tests;

use Garden\Events\ResourceEvent;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Dashboard\Models\RecordStatusModel;
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
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test discussionModel_afterStatusUpdate_handler
     *
     * @param int $statusID New status ID of the
     * @param string $qnaStatus Expected QnA status.
     * @dataProvider DiscussionStatusUpdates
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
        $this->assertIdeationStatusEventPayload($matchingEvent, $statusID);
    }

    /**
     * Set of assertions related to the status included in a discussion event when an ideation status is updated
     *
     * @param ResourceEvent $event
     * @param int $expectedStatusID
     */
    private function assertIdeationStatusEventPayload(ResourceEvent $event, int $expectedStatusID): void
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
    public function DiscussionStatusUpdates(): array
    {
        $result = [
            "change Status to StatusID " . RecordStatusModel::DISCUSSION_STATUS_ACCEPTED => [
                RecordStatusModel::DISCUSSION_STATUS_ACCEPTED,
                "Accepted",
            ],
            "change Status to StatusID " . RecordStatusModel::DISCUSSION_STATUS_ANSWERED => [
                RecordStatusModel::DISCUSSION_STATUS_ANSWERED,
                "Answered",
            ],
            "change Status to StatusID " . RecordStatusModel::DISCUSSION_STATUS_REJECTED => [
                RecordStatusModel::DISCUSSION_STATUS_REJECTED,
                "Rejected",
            ],
            "change Status to StatusID " . RecordStatusModel::DISCUSSION_STATUS_UNANSWERED => [
                RecordStatusModel::DISCUSSION_STATUS_UNANSWERED,
                "Unanswered",
            ],
        ];

        return $result;
    }
}

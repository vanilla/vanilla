<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Events;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Events\ResourceEvent;
use Garden\Schema\ValidationException;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test for the report resource event.
 */
class ReportEventTest extends SiteTestCase
{
    use CommunityApiTestTrait, EventSpyTestTrait;

    /**
     * Test the report resource events on a discussion.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNewReportOnDiscussion(): void
    {
        // Create a new report.
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $report = $this->createReport($discussion);
        $event = $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "report",
                ResourceEvent::ACTION_INSERT,
                self::getReportResourceEventData($report)
            )
        );

        // Make sure we exclude the bodies of the report and record.
        $payload = $event->getPayload();
        $this->assertArrayNotHasKey("recordHtml", $payload["report"]);
        $this->assertArrayNotHasKey("recordBody", $payload["report"]);
        $this->assertArrayNotHasKey("recordFormat", $payload["report"]);
        $this->assertArrayNotHasKey("noteBody", $payload["report"]);
        $this->assertArrayNotHasKey("noteFormat", $payload["report"]);
        $this->assertArrayNotHasKey("premoderatedRecord", $payload["report"]);

        // Update the report.
        $report = $this->api()
            ->patch("reports/{$report["reportID"]}", ["status" => "escalated"])
            ->getBody();
        $this->assertEventDispatched(
            event: $this->expectedResourceEvent(
                "report",
                ResourceEvent::ACTION_UPDATE,
                self::getReportResourceEventData($report)
            )
        );
    }

    /**
     * Test the report resource events on a comment.
     *
     * @return void
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function testNewReportOnComment(): void
    {
        // Create a new report.
        $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment = $this->createComment($discussion);
        $report = $this->createReport($comment);
        $event = $this->assertEventDispatched(
            $this->expectedResourceEvent(
                "report",
                ResourceEvent::ACTION_INSERT,
                self::getReportResourceEventData($report)
            )
        );

        // Make sure we exclude the bodies of the report and record.
        $payload = $event->getPayload();
        $this->assertArrayNotHasKey("recordHtml", $payload["report"]);
        $this->assertArrayNotHasKey("recordBody", $payload["report"]);
        $this->assertArrayNotHasKey("recordFormat", $payload["report"]);
        $this->assertArrayNotHasKey("noteBody", $payload["report"]);
        $this->assertArrayNotHasKey("noteFormat", $payload["report"]);
        $this->assertArrayNotHasKey("premoderatedRecord", $payload["report"]);

        // Make sure the record name is properly formatted for comments.
        $this->assertEquals($comment["name"], $payload["report"]["recordName"]);

        // Update the report.
        $report = $this->api()
            ->patch("reports/{$report["reportID"]}", ["status" => "escalated"])
            ->getBody();
        $this->assertEventDispatched(
            event: $this->expectedResourceEvent(
                "report",
                ResourceEvent::ACTION_UPDATE,
                self::getReportResourceEventData($report)
            )
        );
    }

    /**
     * Extract the ReportEvent payload fields except for the dates.
     *
     * @param array $report
     * @return array
     */
    private static function getReportResourceEventData(array $report): array
    {
        return [
            "reportID" => $report["reportID"],
            "insertUserID" => $report["insertUserID"],
            "updateUserID" => $report["updateUserID"],
            "status" => $report["status"],
            "recordUserID" => $report["recordUserID"],
            "recordType" => $report["recordType"],
            "recordID" => $report["recordID"],
            "placeRecordType" => $report["placeRecordType"],
            "placeRecordID" => $report["placeRecordID"],
            "recordName" => $report["recordName"],
            "isPending" => $report["isPending"],
            "isPendingUpdate" => $report["isPendingUpdate"],
        ];
    }
}

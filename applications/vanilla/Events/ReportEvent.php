<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Analytics\TrackableDateUtils;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ArrayUtils;

/**
 * Handle the creation/update of a new report on the CDM.
 */
class ReportEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const COLLECTION_NAME = "report";

    /**
     * @inheridoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context["report"] = ArrayUtils::pluck($this->payload["report"] ?? [], [
            "reportID",
            "insertUserID",
            "dateInserted",
            "dateUpdated",
            "status",
            "recordUserID",
            "recordType",
            "recordID",
            "placeRecordType",
            "placeRecordID",
            "recordName",
            "recordDateInserted",
            "isPending",
            "isPendingUpdate",
            "escalationUrl",
            "escalationID",
            "recordUrl",
            "recordIsLive",
            "recordWasEdited",
            "placeRecordUrl",
            "placeRecordName",
        ]);

        return new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);
    }

    /**
     * @inheridoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheridoc
     */
    public function getTrackablePayload(
        TrackableCommunityModel $trackableCommunity,
        TrackableUserModel $trackableUserModel
    ): array {
        $payload = $this->getPayload();

        if ($payload["report"]["placeRecordType"] === "category") {
            $payload["category"] = $trackableCommunity->getTrackableCategory($payload["report"]["placeRecordID"]);
        }

        // Dates
        $payload["report"]["dateInserted"] = TrackableDateUtils::getDateTime($payload["report"]["dateInserted"]);

        $payload["report"]["recordDateInserted"] = TrackableDateUtils::getDateTime(
            $payload["report"]["recordDateInserted"]
        );

        // Users
        $payload["recordUser"] = $trackableUserModel->getTrackableUser($payload["report"]["recordUserID"]);
        $payload["reportUser"] = $trackableUserModel->getTrackableUser($payload["report"]["insertUserID"]);

        if (isset($payload["record"]["updateUserID"])) {
            $payload["updateUser"] = $trackableUserModel->getTrackableUser($payload["report"]["updateUserID"]);
        }

        return $payload;
    }

    /**
     * Strip out the record and report bodies from the payload.
     *
     * @return array|null
     */
    public function getPayload(): ?array
    {
        $payload = $this->payload;
        unset($payload["report"]["recordBody"]);
        unset($payload["report"]["recordFormat"]);
        unset($payload["report"]["premoderatedRecord"]);
        unset($payload["report"]["recordHtml"]);
        unset($payload["report"]["noteBody"]);
        unset($payload["report"]["noteFormat"]);
        return $payload;
    }
}

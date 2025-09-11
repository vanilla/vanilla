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
 * Represents an escalation to the CMD.
 */
class cmdEscalationEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const COLLECTION_NAME = "cmdEscalation";
    const ACTION_ASSIGNED = "assigned";

    /**
     * D.I.
     */
    public function __construct(string $action, array $payload, $sender = null)
    {
        parent::__construct($action, $payload, $sender);
    }

    /**
     * @inheritdoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context["cmdEscalation"] = ArrayUtils::pluck($this->payload["cmdEscalation"] ?? [], [
            "escalationID",
            "name",
            "status",
            "assignedUserID",
            "recordType",
            "recordID",
            "dateInserted",
            "dateUpdated",
            "updateUserID",
            "insertUserID",
            "placeRecordType",
            "placeRecordID",
        ]);

        $log = new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);

        return $log;
    }

    /**
     * @inheritdoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getTrackablePayload(
        TrackableCommunityModel $trackableCommunity,
        TrackableUserModel $trackableUserModel
    ): array {
        $payload = $this->payload;

        if ($payload["cmdEscalation"]["placeRecordType"] === "category") {
            $payload["category"] = $trackableCommunity->getTrackableCategory(
                $payload["cmdEscalation"]["placeRecordID"]
            );
        }

        // Dates
        $payload["cmdEscalation"]["dateInserted"] = TrackableDateUtils::getDateTime(
            $payload["cmdEscalation"]["dateInserted"]
        );

        $payload["cmdEscalation"]["recordDateInserted"] = TrackableDateUtils::getDateTime(
            $payload["cmdEscalation"]["recordDateInserted"]
        );

        // Users
        $payload["recordUser"] = $trackableUserModel->getTrackableUser($payload["cmdEscalation"]["recordUserID"]);
        $payload["escalationUser"] = $trackableUserModel->getTrackableUser($payload["cmdEscalation"]["insertUserID"]);
        if (isset($payload["cmdEscalation"]["assignedUserID"])) {
            $payload["assignedUser"] = $trackableUserModel->getTrackableUser(
                $payload["cmdEscalation"]["assignedUserID"]
            );
        }

        if (!empty($payload["cmdEscalation"]["updateUserID"])) {
            $payload["updateUser"] = $trackableUserModel->getTrackableUser($payload["cmdEscalation"]["updateUserID"]);
        }

        return $payload;
    }
}

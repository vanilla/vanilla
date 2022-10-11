<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use BanModel;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Logger;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a user event for which a user is disciplined.
 */
class UserDisciplineEvent extends UserEvent implements \Garden\Events\TrackingEventInterface
{
    const COLLECTION_NAME = "moderation";

    public const DISCIPLINE_TYPE_POSITIVE = "positive";

    public const DISCIPLINE_TYPE_NEGATIVE = "negative";

    /** @var null|string  */
    private $reason = null;

    /**
     * Construct a UserDisciplineEvent based on a UserEvent.
     *
     * @param array $bannedUser The user against whom the disciplinary action is being taken.
     * @param string $disciplinaryAction The action.
     * @param string $disciplineType Whether the discipline event is positive or negative.
     * @param string|null $source The source of the action and any relevant accompanying info.
     * @param array|null $discipliningUser The user performing the discipline.
     */
    public function __construct(
        array $bannedUser,
        string $disciplinaryAction,
        string $disciplineType,
        ?string $source,
        ?array $discipliningUser
    ) {
        $payload = [
            "disciplinedUser" => $bannedUser,
            "discipliningUser" => $discipliningUser,
            "disciplineType" => $disciplineType,
            "source" => $source,
        ];

        if ($this->reason !== null) {
            $payload["reason"] = $this->reason;
        }

        parent::__construct($disciplinaryAction, $payload, $this->getSender());
        $this->type = "user";
    }

    /**
     * @inheritDoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * Get a payload for analytics tracking.
     *
     * @param TrackableUserModel $trackableUserModel
     */
    public function getTrackablePayload(TrackableUserModel $trackableUserModel)
    {
        $eventPayload = $this->getPayload();
        $trackingData = [
            "disciplinedUser" => $trackableUserModel->getTrackableUser($eventPayload["disciplinedUser"]["userID"]),
            "discipliningUser" =>
                $eventPayload["discipliningUser"] !== null
                    ? $trackableUserModel->getTrackableUser($eventPayload["discipliningUser"]["userID"])
                    : null,
        ];
        return $trackingData;
    }

    /**
     * Set a reason for the disciplinary action.
     *
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context[Logger::FIELD_TARGET_USERID] = $this->getPayload()["disciplinedUser"]["userID"];
        $context[Logger::FIELD_TARGET_USERNAME] = $this->getPayload()["disciplinedUser"]["name"];
        $context[Logger::FIELD_USERID] = $this->getPayload()["discipliningUser"]["userID"] ?? null;
        $context[Logger::FIELD_USERNAME] = $this->getPayload()["discipliningUser"]["name"] ?? null;

        $log = new LogEntry(
            LogLevel::INFO,
            $this->makeLogMessage(
                $this->getAction(),
                $context[Logger::FIELD_TARGET_USERNAME] ?? null,
                $context[Logger::FIELD_USERNAME] ?? null
            ),
            $context
        );
        return $log;
    }

    /**
     * {@inheritDoc}
     */
    public function makeLogMessage(string $action, string $targetName, ?string $username): string
    {
        switch ($action) {
            case BanModel::ACTION_BAN:
            case BanModel::ACTION_UNBAN:
                return $username
                    ? "User {$targetName} was {$action}ned by {$username}."
                    : "User {$targetName} was {$action}ned.";
            default:
                return $username
                    ? "User {$targetName} was {$action}ed by {$username}."
                    : "User {$targetName} was {$action}ed.";
        }
    }
}

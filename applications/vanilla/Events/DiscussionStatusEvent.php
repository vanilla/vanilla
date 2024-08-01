<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Defines the event dispatched when a status is applied to a discussion
 */
class DiscussionStatusEvent extends ResourceEvent implements LoggableEventInterface
{
    //region Properties
    public const ACTION_DISCUSSION_STATUS = "statusUpdate";

    /** @var bool */
    private $isInternal;
    //endregion

    //region Constructor
    /**
     * Constructor
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     */
    public function __construct(string $action, array $payload, $sender = null, array $status = [])
    {
        parent::__construct($action, $payload, $sender);
        $this->type = "discussion";
        $this->isInternal = (bool) $status["isInternal"];
    }
    //endregion

    //region Methods

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context["discussion"] = array_intersect_key($this->payload["discussion"] ?? [], [
            "discussionID" => true,
            "dateInserted" => true,
            "dateUpdated" => true,
            "updateUserID" => true,
            "insertUserID" => true,
            "url" => true,
            "name" => true,
        ]);

        $log = new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);

        return $log;
    }

    /**
     * Convert to string.
     */
    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
     * @return bool
     */
    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    //endregion
}

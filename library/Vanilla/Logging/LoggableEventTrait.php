<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logger;

/**
 * Add simple implementation of LoggableEvent for classes extending ResourceEvent.
 */
trait LoggableEventTrait {

    /**
     * Get the event details for logging.
     *
     * @return LogEntry
     */
    public function getLogEntry(): LogEntry {
        $entry = new LogEntry(
            $this->getLogLevel(),
            $this->getLogMessage(),
            [
                Logger::FIELD_EVENT =>  $this->getType() . "_" . $this->getAction(),
                "payload" => $this->getPayload(),
                "resourceAction" => $this->getAction(),
                "resourceType" => $this->getType(),
            ]
        );
        return $entry;
    }

    /**
     * Get the log level for this event.
     *
     * @return string
     */
    public function getLogLevel(): string {
        return LogLevel::INFO;
    }

    /**
     * Get formattable log message for this event.
     *
     * @return string
     */
    public function getLogMessage(): string {
        $verbs = [
            ResourceEvent::ACTION_DELETE => "deleted",
            ResourceEvent::ACTION_INSERT => "inserted",
            ResourceEvent::ACTION_UPDATE => "updated",
        ];
        $verb = $verbs[$this->getAction()] ?? null;

        if ($verb === null) {
            return $this->getType() . " " . $this->getAction();
        }

        $message = "{username} $verb {resourceType}";
        return $message;
    }
}

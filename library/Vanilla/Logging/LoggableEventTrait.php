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
            $this->getLogContext()
        );
        return $entry;
    }

    /**
     * Get the log context for this event.
     *
     * @return array
     */
    private function getLogContext(): array {
        $payload = $this->getLogPayload();
        $payload = LoggerUtils::stringifyDates($payload);

        $result = [
            Logger::FIELD_EVENT =>  $this->getType() . "_" . $this->getAction(),
            "payload" => $payload,
            "resourceAction" => $this->getAction(),
            "resourceType" => $this->getType(),
        ];
        if ($this->getSender() !== null) {
            $result["sender"] = $this->getSender();
        }
        return $result;
    }

    /**
     * Get the log level for this event.
     *
     * @return string
     */
    private function getLogLevel(): string {
        return LogLevel::INFO;
    }

    /**
     * Get formattable log message for this event.
     *
     * @return string
     */
    private function getLogMessage(): string {
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

    /**
     * Get an optionally customized version of the payload for a log entry..
     *
     * @return array
     */
    private function getLogPayload(): array {
        return $this->getPayload();
    }
}

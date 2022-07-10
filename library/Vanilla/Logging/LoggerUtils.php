<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Events\ResourceEvent;
use Vanilla\Events\EventAction;
use Vanilla\Logger;

/**
 * General utilities for assisting with logging.
 */
class LoggerUtils {

    /**
     * Recursively convert DateTimeInterface objects into ISO-8601 strings.
     *
     * @param array $row
     * @return array
     */
    public static function stringifyDates(array $row): array {
        array_walk_recursive($row, function (&$value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }
        });
        return $row;
    }

    /**
     * Get the log context for an event.
     *
     * @param ResourceEvent $event
     * @return array
     */
    public static function resourceEventLogContext(ResourceEvent $event): array {
        $payload = $event->getPayload();

        $result = [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_EVENT => EventAction::eventName($event->getType(), $event->getAction()),
            "resourceAction" => $event->getAction(),
            "resourceType" => $event->getType(),
        ];
        if (isset($payload[$event->getType()])) {
            $result[$event->getType()] = $payload[$event->getType()];
        }

        if ($event->getSender() !== null) {
            $result[Logger::FIELD_USERID] = $event->getSender()['userID'];
            $result[Logger::FIELD_USERNAME] = $event->getSender()['name'];
        }
        return $result;
    }

    /**
     * Generate a reasonably nice default log message for a resource event.
     *
     * @param ResourceEvent $event
     * @return string
     */
    public static function resourceEventLogMessage(ResourceEvent $event): string {
        $verbs = [
            ResourceEvent::ACTION_DELETE => "deleted",
            ResourceEvent::ACTION_INSERT => "added",
            ResourceEvent::ACTION_UPDATE => "updated",
        ];
        $verb = $verbs[$event->getAction()] ?? $event->getAction();

        $message = ucfirst($event->getType()) . " $verb";
        if ($event->getSender()) {
            $message .= " by {username}.";
        } else {
            $message .= ".";
        }
        return $message;
    }
}

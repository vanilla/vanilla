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
class LoggerUtils
{
    /**
     * Recursively convert DateTimeInterface objects into ISO-8601 strings.
     *
     * @param array $row
     * @return array
     */
    public static function stringifyDates(array $row): array
    {
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
    public static function resourceEventLogContext(ResourceEvent $event): array
    {
        $result = [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_EVENT => EventAction::eventName($event->getType(), $event->getAction()),
            "resourceAction" => $event->getAction(),
            "resourceType" => $event->getType(),
        ];

        $modifications = $event->getModifications();
        if ($modifications !== null) {
            $result["modifications"] = $modifications;
        }

        if ($event->getSender() !== null) {
            $result[Logger::FIELD_USERID] = $event->getSender()["userID"];
            $result[Logger::FIELD_USERNAME] = $event->getSender()["name"];
        }
        return $result;
    }

    /**
     * Generate a reasonably nice default log message for a resource event.
     *
     * @param ResourceEvent $event
     * @return string
     */
    public static function resourceEventLogMessage(ResourceEvent $event): string
    {
        $verbs = [
            ResourceEvent::ACTION_DELETE => "deleted",
            ResourceEvent::ACTION_INSERT => "added",
            ResourceEvent::ACTION_UPDATE => "updated",
        ];
        $verb = $verbs[$event->getAction()] ?? $event->getAction();

        $message = ucfirst($event->getType()) . " $verb";
        if ($event->getSender()) {
            $message .= " by `{$event->getSender()["name"]}`.";
        } else {
            $message .= ".";
        }
        return $message;
    }

    /**
     * Diff 2 arrays into a format for logging changes.
     *
     * @param array $oldData
     * @param array $newData
     *
     * @return array
     */
    public static function diffArrays(array $oldData, array $newData): array
    {
        $diff = [];
        foreach ($oldData as $key => $value) {
            if (!array_key_exists($key, $newData)) {
                $diff[$key] = [
                    "old" => $value,
                    "new" => null,
                ];
                continue;
            }

            if ($value !== $newData[$key]) {
                $diff[$key] = [
                    "old" => $value,
                    "new" => $newData[$key],
                ];
            }
        }

        foreach ($newData as $key => $value) {
            // Handle newly added fields.
            if (!array_key_exists($key, $oldData)) {
                $diff[$key] = [
                    "old" => null,
                    "new" => $value,
                ];
            }
        }
        return $diff;
    }
}

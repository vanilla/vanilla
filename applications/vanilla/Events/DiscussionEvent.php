<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface {
    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context['discussion'] = array_intersect_key($payload["discussion"] ?? [], [
            "discussionID" => true,
            "dateInserted" => true,
            "dateUpdated" => true,
            "updateUserID" => true,
            "insertUserID" => true,
            "url" => true,
            "name" => true,
        ]);

        $log = new LogEntry(
            LogLevel::INFO,
            LoggerUtils::resourceEventLogMessage($this),
            $context
        );

        return $log;
    }
}

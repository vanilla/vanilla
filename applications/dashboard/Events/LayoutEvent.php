<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Event fired on CRUD events from a layout.
 */
class LayoutEvent extends ResourceEvent implements LoggableEventInterface
{
    /**
     * @inheritdoc
     */
    public function getLogEntry(): LogEntry
    {
        $layout = $this->payload["layout"];
        $message = match ($this->getAction()) {
            ResourceEvent::ACTION_INSERT => "Layout `{$layout["name"]}` was created.",
            ResourceEvent::ACTION_UPDATE => "Layout `{$layout["name"]}` was updated.",
            ResourceEvent::ACTION_DELETE => "Layout `{$layout["name"]}` was deleted.",
            default => LoggerUtils::resourceEventLogMessage($this),
        };

        $context = LoggerUtils::resourceEventLogContext($this);
        $context["layoutID"] = $layout["layoutID"];
        $logEntry = new LogEntry(LogLevel::INFO, $message, $context);
        return $logEntry;
    }

    /**
     * Log all of these.
     *
     * @return bool
     */
    public function bypassLogFilters(): bool
    {
        return true;
    }
}

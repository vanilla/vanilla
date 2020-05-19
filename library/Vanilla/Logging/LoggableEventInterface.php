<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

/**
 * Methods for allowing easy logging of an event.
 */
interface LoggableEventInterface {

    /**
     * Get the event details for logging.
     *
     * @return LogEntry
     */
    public function getLogEntry(): LogEntry;
}

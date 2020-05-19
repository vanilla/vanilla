<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

/**
 * Provide a standard way for converting a DB row into a resource event.
 */
interface EventFromRowInterface {

    /**
     * Generate an event based on a database row, including an optional sender.
     *
     * @param array $row
     * @param string $action
     * @param array|null $sender
     * @return ResourceEvent
     */
    public function eventFromRow(array $row, string $action, ?array $sender = null): ResourceEvent;
}

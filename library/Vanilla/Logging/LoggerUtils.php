<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

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
}

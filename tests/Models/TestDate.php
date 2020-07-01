<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

/**
 * Allows tests to generate successive test dates that are sequential.
 *
 * Tests may want to simulate the "real world" a bit by inserting records sequentially, rather than all in the same
 * second. Certain logic depends on dates not being the same.
 */
final class TestDate {
    /**
     * @var int The
     */
    private static $date = 1592611200;

    /**
     * Generate a timestamp.
     *
     * @return int
     */
    public static function timestamp(): int {
        return self::$date++;
    }

    /**
     * Generate a date that can be passed to MySQL.
     *
     * @return string
     */
    public static function mySqlDate(): string {
        return gmdate(MYSQL_DATE_FORMAT, self::timestamp());
    }

    /**
     * Generate a date object.
     *
     * @return \DateTimeInterface
     */
    public static function date(): \DateTimeInterface {
        return new \DateTimeImmutable('@'.self::timestamp());
    }
}

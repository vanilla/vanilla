<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Vanilla\Formatting\DateTimeFormatter;

/**
 * Simple class for getting the current time. Easily configurable for tests.
 *
 * Dependency injecting times can be painful, which is this is structured as a static utility.
 * Any mocks are cleared between test cases.
 */
final class CurrentTimeStamp {
    /**
     * Format dates consistent with MySQL requirements.
     */
    const MYSQL_DATE_FORMAT = "Y-m-d H:i:s";

    /** @var int */
    private static $timeMock = null;

    /**
     * Get the current timestamp.
     */
    public static function get(): int {
        return self::$timeMock ?? time();
    }

    /**
     * Get the current date time object.
     *
     * @return \DateTimeImmutable
     */
    public static function getDateTime(): \DateTimeImmutable {
        return new \DateTimeImmutable('@'.self::get());
    }

    /**
     * Get the current date as a MySQL formatted string (UTC).
     *
     * @return string
     */
    public static function getMySQL(): string {
        return gmdate(self::MYSQL_DATE_FORMAT, self::get());
    }

    /**
     * Convert something like a date into a datetime immutable.
     *
     * @param \DateTimeInterface|string|int $toConvert
     *
     * @return \DateTimeImmutable
     */
    public static function coerceDateTime($toConvert): \DateTimeImmutable {
        self::assertTestMode();
        if ($toConvert instanceof \DateTime) {
            \DateTimeImmutable::createFromMutable($toConvert);
        } elseif ($toConvert instanceof \DateTimeImmutable) {
            return $toConvert;
        } elseif (is_numeric($toConvert)) {
            return new \DateTimeImmutable("@$toConvert");
        }

        $time = strtotime($toConvert);
        return new \DateTimeImmutable("@$time");
    }

    /**
     * Mock the current time. Only works in tests.
     *
     * @param \DateTimeInterface|string|int $toMock
     *
     * @return \DateTimeImmutable The mocked date time.
     */
    public static function mockTime($toMock): \DateTimeImmutable {
        self::assertTestMode();
        $date = self::coerceDateTime($toMock);
        self::$timeMock = $date->getTimestamp();
        return $date;
    }

    /**
     * Reset the mock time.
     */
    public static function clearMockTime() {
        self::assertTestMode();
        self::$timeMock = null;
    }

    /**
     * @throws \Exception If we aren't in test mode.
     */
    private static function assertTestMode() {
        assert(defined('TESTMODE_ENABLED') && TESTMODE_ENABLED);
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use http\Exception\RuntimeException;
use Vanilla\Utility\DebugUtils;

/**
 * Simple class for getting the current time. Easily configurable for tests.
 *
 * Dependency injecting times can be painful, which is this is structured as a static utility.
 * Any mocks are cleared between test cases.
 */
final class CurrentTimeStamp
{
    /**
     * Format dates consistent with MySQL requirements.
     */
    const MYSQL_DATE_FORMAT = "Y-m-d H:i:s";
    const MYSQL_DATE_FORMAT_PRECISE = "Y-m-d H:i:s.u";

    const SAML_SSO_DATE_FORMAT = "Y-m-d\TH:i:s.v\Z";
    const PRECISE_DATE_FORMAT = "U.u";

    /** @var string */
    private static $timeMock = null;

    /**
     * Check if our time is mocked our not.
     *
     * @return bool
     */
    public static function isMocked(): bool
    {
        return self::$timeMock !== null;
    }

    /**
     * Get the current timestamp.
     */
    public static function get(): int
    {
        $time = self::$timeMock ?? time();
        return (int) $time;
    }

    /**
     * @return float
     */
    public static function getPrecise(): float
    {
        return self::$timeMock ?? microtime(true);
    }

    /**
     * Get the current date time object.
     *
     * @return \DateTimeImmutable
     */
    public static function getDateTime(): \DateTimeImmutable
    {
        $timestamp = (string) self::getPrecise();
        if (!str_contains($timestamp, ".")) {
            $timestamp .= ".0";
        }
        $date = \DateTimeImmutable::createFromFormat(self::PRECISE_DATE_FORMAT, $timestamp);
        if (!$date instanceof DateTimeImmutable) {
            throw new \RuntimeException("Could not parse timestamp {$timestamp} into DateTimeImmutable.");
        }

        return $date;
    }

    /**
     * Get the current date as a MySQL formatted string (UTC).
     *
     * @param bool $precise Use microsecond precision.
     *
     * @return string
     */
    public static function getMySQL(bool $precise = false): string
    {
        return self::getDateTime()->format($precise ? self::MYSQL_DATE_FORMAT_PRECISE : self::MYSQL_DATE_FORMAT);
    }

    /**
     * Convert something like a date into a datetime immutable.
     *
     * @param \DateTimeInterface|string|int $toConvert
     *
     * @return \DateTimeImmutable
     */
    public static function coerceDateTime($toConvert): \DateTimeImmutable
    {
        self::assertTestMode();
        if ($toConvert instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($toConvert);
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
     *
     * @codeCoverageIgnore
     */
    public static function mockTime($toMock): \DateTimeImmutable
    {
        self::assertTestMode();
        $date = self::coerceDateTime($toMock);
        self::$timeMock = (float) $date->format(self::PRECISE_DATE_FORMAT);
        JWT::$timestamp = $date->getTimestamp();
        return $date;
    }

    /**
     * Increment the mock timer by 1 second.
     *
     * @return void
     * @throws \Exception
     */
    public static function increment(): void
    {
        self::assertTestMode();
        if (self::$timeMock === null) {
            throw new \Exception("Cannot increment time when not mocked.");
        }
        self::$timeMock += 1;
        JWT::$timestamp = (int) self::$timeMock;
    }

    /**
     * Reset the mock time.
     *
     * @codeCoverageIgnore
     */
    public static function clearMockTime()
    {
        self::assertTestMode();
        JWT::$timestamp = null;
        self::$timeMock = null;
    }

    /**
     * @throws \Exception If we aren't in test mode.
     * @codeCoverageIgnore
     */
    private static function assertTestMode()
    {
        assert(DebugUtils::isTestMode());
    }

    /**
     * Get the time difference of date from current time.
     *
     * @param DateTimeImmutable $date
     * @return int
     */
    public static function getCurrentTimeDifference(\DateTimeImmutable $date): int
    {
        $currentTime = self::getDateTime();
        return $currentTime->getTimestamp() - $date->getTimestamp();
    }

    /**
     * Partition time into blocks as wide as the specified window interval, relative to Unix epoch timestamp,
     * and returns when the current window started.
     *
     * @param \DateInterval $window Amount of time used to partition time into blocks as wide as the specified
     * window, relative to Unix epoch.
     * @return \DateTimeInterface Date/Time when the current window starts given the specified window interval
     *
     * @example if the window specified is 2 minutes, there are 30 windows each hour, i.e. every two minutes,
     * e.g. 09:00:00, 09:02:00, 09:04:00, etc. If the current time is 08:59:17, the current window started at
     * 08:58:00 and ends at 08:59:59 and the function will return a DateTime specifying 08:58:00 in the time portion.
     */
    public static function toWindowStart(\DateInterval $window): \DateTimeInterface
    {
        if ($window->invert) {
            throw new \InvalidArgumentException("Cannot specify a negative time window");
        }
        $now = self::getDateTime();
        $windowWidthSec = $now->add($window)->getTimestamp() - $now->getTimestamp();

        if ($windowWidthSec <= 0) {
            throw new \InvalidArgumentException("Window interval must be a positive value");
        }

        // How many seconds are we into our current time window?
        $nowTimestamp = self::get();
        $secPastWindow = $nowTimestamp % $windowWidthSec;
        // When did our current time window start? (Unix Timestamp)
        $currentWindowTimestamp = $nowTimestamp - $secPastWindow;

        return (new DateTimeImmutable())->setTimestamp($currentWindowTimestamp);
    }

    /**
     * Partition time into blocks as wide as the specified window interval, relative to Unix epoch timestamp,
     * and returns when the next window starts, unless a rollover interval is specified
     * and the amount of time until the start of the next window is within the rollover interval,
     * in which case, roll over the next window and return start of the window after the next window.
     *
     * @param \DateInterval $window Amount of time used to partition time into blocks as wide as the specified
     * window, relative to Unix epoch.
     * @param \DateInterval|null $rolloverWithin Optional, Amount of time relative to the start of the next window
     * within which to rollover the next window and return the start of the window after the next window.
     * @return \DateTimeInterface Date/Time when the next window starts given the specified window interval
     * and optional rollover interval
     * @example if the window specified is 2 minutes, there are 30 windows each hour, i.e. every two minutes,
     * e.g. 09:00:00, 09:02:00, 09:04:00, etc. If the current time is 08:59:17, the current window started at
     * 08:58:00 and ends at 08:59:59, and the next window starts at 09:00:00, which is what is returned if no
     * rollover interval is specified. If the rollover interval is specified as 1 minute and current time is still
     * 08:59:17, the next window still starts at 09:00:00 but the value returned is 09:02:00, as current time window
     * is less than one minute from elapsing, thus rolling over to the next window at 09:02:00. If rollover interval
     * specified as 30 sec and current time is 08:59:17, next time window is 09:00:00 as the number of seconds
     * from now until the next time window (43 sec) is greater than our rollover interval of 30 sec.
     *
     * @throws \InvalidArgumentException Negative or zero window.
     * @throws \InvalidArgumentException Negative rollover.
     * @throws \InvalidArgumentException Rollover equal to or greater than window.
     */
    public static function toNextWindow(
        \DateInterval $window,
        ?\DateInterval $rolloverWithin = null
    ): \DateTimeInterface {
        if ($window->invert) {
            throw new \InvalidArgumentException("Cannot specify a negative time window");
        }

        $now = self::getDateTime();
        $rolloverIntervalSec = 0;
        if (isset($rolloverWithin)) {
            if ($rolloverWithin->invert) {
                throw new \InvalidArgumentException("Cannot specify a negative rollover interval");
            }
            $rolloverIntervalSec = $now->add($rolloverWithin)->getTimestamp() - $now->getTimestamp();
        }

        $windowWidthSec = $now->add($window)->getTimestamp() - $now->getTimestamp();

        if ($windowWidthSec <= 0) {
            throw new \InvalidArgumentException("Window interval must be a positive value");
        }
        if ($rolloverIntervalSec < 0) {
            throw new \InvalidArgumentException("Cannot specify a negative rollover interval");
        }
        if ($rolloverIntervalSec >= $windowWidthSec) {
            throw new \InvalidArgumentException("Cannot specify a rollover interval greater than or equal to window");
        }

        // When did our current time window start? (Unix Timestamp)
        $currentWindowTimestamp = CurrentTimeStamp::toWindowStart($window)->getTimestamp();
        // When does the next time window start? (Unix Timestamp)
        $nextWindowTimestamp = $currentWindowTimestamp + $windowWidthSec;

        // Determine if at or over the rollover threshold that crosses into the window after the next window
        $rolloverThresholdTimestamp = $nextWindowTimestamp - $rolloverIntervalSec;
        $nowTimestamp = self::get();
        if ($nowTimestamp >= $rolloverThresholdTimestamp) {
            $nextWindowTimestamp += $windowWidthSec;
        }
        return (new DateTimeImmutable())->setTimestamp($nextWindowTimestamp);
    }
}

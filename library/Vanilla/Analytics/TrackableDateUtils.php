<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use DateTime;
use DateTimeZone;

/**
 * Date utilities for analytics tracking.
 */
final class TrackableDateUtils
{
    /**
     * @var DateTimeZone An instance of DateTimeZone for date calculations.
     */
    private static $defaultTimeZone = null;

    /**
     * Grab the default time zone for creating dates/times.
     *
     * @return DateTimeZone
     */
    public static function getDefaultTimeZone()
    {
        if (is_null(self::$defaultTimeZone)) {
            self::$defaultTimeZone = new DateTimeZone("UTC");
        }

        return self::$defaultTimeZone;
    }

    /**
     * Filter elements from getDateTime down to date-only fields.
     *
     * @param string $time Time to breakdown.
     * @param DateTimeZone|null $timeZone Time zone to represent the specified time in.
     * @return array
     */
    public static function getDate($time = "now", DateTimeZone $timeZone = null)
    {
        $dateTime = self::getDateTime($time, $timeZone);

        return [
            "year" => $dateTime["year"],
            "month" => $dateTime["month"],
            "day" => $dateTime["day"],
            "dayOfWeek" => $dateTime["dayOfWeek"],
        ];
    }

    /**
     * Grab an array of date/time parts representing the specified date/time.
     *
     * @param string $time Time to breakdown.
     * @param DateTimeZone|null $timeZone Time zone to represent the specified time in.
     * @return array
     */
    public static function getDateTime($time = "now", DateTimeZone $timeZone = null)
    {
        if (is_a($time, \DateTimeInterface::class)) {
            $dateTime = $time;
        } else {
            $dateTime = new DateTime($time, is_null($timeZone) ? self::getDefaultTimeZone() : $timeZone);
        }

        $startOfWeek = $dateTime->format("w") === 0 ? "today" : "last sunday";

        return [
            "year" => (int) $dateTime->format("Y"),
            "month" => (int) $dateTime->format("n"),
            "day" => (int) $dateTime->format("j"),
            "hour" => (int) $dateTime->format("G"),
            "minute" => (int) $dateTime->format("i"),
            "dayOfWeek" => (int) $dateTime->format("w"),
            "startOfWeek" => (int) strtotime($startOfWeek, $dateTime->format("U")),
            "timestamp" => (int) $dateTime->format("U"),
            "timeZone" => $dateTime->format("T"),
        ];
    }

    /**
     * Set the default time zone to the specified parameter.
     *
     * @link http://php.net/manual/en/timezones.php
     *
     * @param DateTimeZone $timeZone Target time zone.
     */
    public static function setDefaultTimeZone(DateTimeZone $timeZone)
    {
        self::$defaultTimeZone = $timeZone;
    }
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\StaticCacheTranslationTrait;
use Vanilla\CurrentTimeStamp;

/**
 * Formatting methods related to dates & times.
 */
class DateTimeFormatter
{
    use StaticCacheTranslationTrait;

    const NULL_TIMESTAMP_DEFALT_VALUE = "-";

    const FORCE_FULL_FORMAT = "force-full-datetime-format";

    /** @var DateConfig */
    private $dateConfig;

    /**
     * @param DateConfig $dateConfig
     */
    public function __construct(DateConfig $dateConfig)
    {
        $this->dateConfig = $dateConfig;
    }

    /**
     * Format a MySQL DateTime string in the specified format.
     *
     * @param string|int $timestamp A timestamp or string in Mysql DateTime format. ie. YYYY-MM-DD HH:MM:SS
     * @param bool $isHtml Whether or not to output this as an HTML string.
     * @param string $format The format string to use. Defaults to the application's default format.
     * @return string
     */
    public function formatDate($timestamp = "", bool $isHtml = false, string $format = ""): string
    {
        // Was a mysqldatetime passed?
        if ($timestamp !== null && !is_numeric($timestamp)) {
            $timestamp = self::dateTimeToTimeStamp($timestamp);
        }

        if ($timestamp === null) {
            return self::t("Null Date", self::NULL_TIMESTAMP_DEFALT_VALUE);
        }

        $gmTimestamp = $timestamp;
        $timestamp = $this->adjustTimeStampForUser($timestamp);

        if ($format === "") {
            $format = $this->getDefaultFormatForTimestamp($timestamp);
        } elseif ($format === self::FORCE_FULL_FORMAT) {
            $format = $this->dateConfig->getDefaultDateTimeFormat();
            $format = $this->normalizeFormatForTimeStamp($format, $timestamp);
        } else {
            $format = $this->convertStrftimeToDateFormat($format);
        }

        $result = date($format, $timestamp);

        if ($isHtml) {
            $fullFormat = $this->dateConfig->getDefaultDateTimeFormat();
            $fullFormat = $this->normalizeFormatForTimeStamp($fullFormat, $timestamp);
            $result = wrap($result, "time", [
                "title" => date($fullFormat, $timestamp),
                "datetime" => gmdate("c", $gmTimestamp),
            ]);
        }
        return $result;
    }

    /**
     * Show times relative to now, e.g. "4 hours ago".
     *
     * Credit goes to: http://byteinn.com/res/426/Fuzzy_Time_function/
     *
     * @param int|string|null $timestamp otherwise time() is used
     * @return string
     */
    public function formatRelativeTime($timestamp = null): string
    {
        if (is_null($timestamp)) {
            $timestamp = $this->getNowTimeStamp();
        } elseif (!is_numeric($timestamp)) {
            $timestamp = self::dateTimeToTimeStamp($timestamp);
        }

        $time = $timestamp;

        $now = $this->getNowTimeStamp();

        $secondsAgo = $now - $time;

        // sod = start of day :)
        $sod = mktime(0, 0, 0, date("m", $time), date("d", $time), date("Y", $time));
        $sod_now = mktime(0, 0, 0, date("m", $now), date("d", $now), date("Y", $now));

        // Today
        if ($sod_now == $sod) {
            if ($time > $now - TimeUnit::ONE_MINUTE * 3) {
                return self::t("just now");
            } elseif ($time > $now - TimeUnit::ONE_MINUTE * 7) {
                return self::t("a few minutes ago");
            } elseif ($time > $now - TimeUnit::ONE_MINUTE * 30) {
                $minutesAgo = ceil($secondsAgo / 60);
                return sprintf(self::t("%s minutes ago"), $minutesAgo);
            } elseif ($time > $now - TimeUnit::ONE_HOUR) {
                return self::t("less than an hour ago");
            }
            return sprintf(self::t("today at %s"), date("g:ia", $time));
        }

        // Yesterday
        if ($sod_now - $sod <= TimeUnit::ONE_DAY) {
            if (date("i", $time) > TimeUnit::ONE_MINUTE + 30) {
                $time += TimeUnit::ONE_HOUR / 2;
            }
            return sprintf(self::t("yesterday around %s"), date("ga", $time));
        }

        // Within the last 5 days.
        if ($sod_now - $sod <= TimeUnit::ONE_DAY * 5) {
            $str = date("l", $time);
            $hour = date("G", $time);
            if ($hour < 12) {
                $str .= self::t(" morning");
            } elseif ($hour < 17) {
                $str .= self::t(" afternoon");
            } elseif ($hour < 20) {
                $str .= self::t(" evening");
            } else {
                $str .= self::t(" night");
            }
            return $str;
        }

        // Number of weeks (between 1 and 3).
        if ($sod_now - $sod < TimeUnit::ONE_WEEK * 3.5) {
            if ($sod_now - $sod < TimeUnit::ONE_WEEK) {
                return self::t("about a week ago");
            } elseif ($sod_now - $sod < TimeUnit::ONE_WEEK * 2) {
                return self::t("about two weeks ago");
            } else {
                return self::t("about three weeks ago");
            }
        }

        // Number of months (between 1 and 11).
        if ($sod_now - $sod < TimeUnit::ONE_MONTH * 11.5) {
            for ($i = TimeUnit::ONE_WEEK * 3.5, $m = 0; $i < TimeUnit::ONE_YEAR; $i += TimeUnit::ONE_MONTH, $m++) {
                if ($sod_now - $sod <= $i) {
                    return sprintf(self::t("about %s month%s ago"), $this->spell1To11($m), $m > 1 ? "s" : "");
                }
            }
        }

        // Number of years.
        for ($i = TimeUnit::ONE_MONTH * 11.5, $y = 0; $i < TimeUnit::ONE_YEAR * 10; $i += TimeUnit::ONE_YEAR, $y++) {
            if ($sod_now - $sod <= $i) {
                return sprintf(self::t("about %s year%s ago"), $this->spell1To11($y), $y > 1 ? "s" : "");
            }
        }

        // More than ten years.
        return self::t("more than ten years ago");
    }

    /**
     * Formats seconds in a human-readable way
     * (ie. 45 seconds, 15 minutes, 2 hours, 4 days, 2 months, etc).
     *
     * @param int $seconds
     * @return string
     */
    public function formatSeconds(int $seconds): string
    {
        $minutes = round($seconds / TimeUnit::ONE_MINUTE);
        $hours = round($seconds / TimeUnit::ONE_HOUR);
        $days = round($seconds / TimeUnit::ONE_DAY);
        $weeks = round($seconds / TimeUnit::ONE_WEEK);
        $months = round($seconds / TimeUnit::ONE_MONTH);
        $years = round($seconds / TimeUnit::ONE_YEAR);

        if ($seconds < 60) {
            return sprintf(plural($seconds, "%s second", "%s seconds"), $seconds);
        } elseif ($minutes < 60) {
            return sprintf(plural($minutes, "%s minute", "%s minutes"), $minutes);
        } elseif ($hours < 24) {
            return sprintf(plural($hours, "%s hour", "%s hours"), $hours);
        } elseif ($days < 7) {
            return sprintf(plural($days, "%s day", "%s days"), $days);
        } elseif ($weeks < 4) {
            return sprintf(plural($weeks, "%s week", "%s weeks"), $weeks);
        } elseif ($months < 12) {
            return sprintf(plural($months, "%s month", "%s months"), $months);
        } else {
            return sprintf(plural($years, "%s year", "%s years"), $years);
        }
    }

    /**
     * Convert a datetime to a timestamp.
     *
     * @param string $dateTime The Mysql-formatted datetime to convert to a timestamp. Should be in one
     * of the following formats: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.
     * @param mixed $fallback The value to return if the value couldn't be properly converted.
     * @param mixed $emptyFallback The fallback for an empty value. If not supplied then the `$fallback` will be used.
     * @return int|null A timestamp or now if it couldn't be parsed properly.
     */
    public static function dateTimeToTimeStamp(?string $dateTime, $fallback = false, $emptyFallback = false): ?int
    {
        if (empty($dateTime)) {
            $emptyFallback = $emptyFallback !== false ? $emptyFallback : $fallback;
            return $emptyFallback !== false ? $emptyFallback : time();
        } elseif (($testTime = strtotime($dateTime)) !== false) {
            return $testTime;
        } else {
            $fallback = $fallback !== false ? $fallback : time();
            trigger_error(__FUNCTION__ . "called with bad input " . $dateTime, E_USER_NOTICE);
            return $fallback;
        }
    }

    /**
     * Convert a timestamp into human readable seconds from now.
     *
     * @see DateTimeFormatter::formatSeconds()
     *
     * @param string $datetime The time to convert.
     * @param int|null $from What time to be relative to.
     * @return int
     */
    public static function dateTimeToSecondsAgo($datetime, $from = null): int
    {
        $from = $from ?? time();
        return abs($from - self::dateTimeToTimeStamp($datetime));
    }

    /**
     * Convert a timetstamp to time formatted as H::MM::SS (g:i:s).
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public static function timeStampToTime(int $timestamp): string
    {
        return date("g:i:s", $timestamp);
    }

    /**
     * Convert a timetstamp to date formatted as D-m-d
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public static function timeStampToDate(int $timestamp): string
    {
        return date("Y-m-d", $timestamp);
    }

    /**
     * Convert a timetstamp to datetime formatted as Y-m-d H:i:s.
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public static function timeStampToDateTime(int $timestamp): string
    {
        return date("Y-m-d H:i:s", $timestamp);
    }

    /**
     * Get the current time formatted as time string.
     *
     * @return string
     */
    public static function getCurrentDateTime(): string
    {
        return self::timeStampToDateTime(CurrentTimeStamp::get());
    }

    /**
     * Adjust a timestamp for the sessioned user's time offset.
     *
     * @param int $timestamp
     * @return int
     */
    public function adjustTimeStampForUser(int $timestamp): int
    {
        $hourOffset = $this->dateConfig->getHourOffset();
        $secondsOffset = $hourOffset * 3600;
        $timestamp += $secondsOffset;
        return $timestamp;
    }

    /** @var null|int */
    private $nowTimeStamp = null;

    /**
     * Get the current time while allowing it to be stubbed for tests.
     *
     * @return int|null
     * @internal Tests only!!!
     */
    private function getNowTimeStamp(): int
    {
        if ($this->nowTimeStamp === null) {
            return time();
        }
        return $this->nowTimeStamp;
    }

    /**
     * @param int|null $nowTimeStamp
     */
    public function setNowTimeStamp(?int $nowTimeStamp): void
    {
        $this->nowTimeStamp = $nowTimeStamp;
    }

    /**
     * Get the current timestamp adjusted for the user's hour offset.
     *
     * @return int
     */
    private function getUserNowTimeStamp(): int
    {
        $now = $this->getNowTimeStamp();
        return $this->adjustTimeStampForUser($now);
    }

    /**
     * Get a relative date format based on how old a timestamp is.
     *
     * @param int $timestamp
     * @return string The format.
     */
    private function getDefaultFormatForTimestamp(int $timestamp): string
    {
        $now = $this->getUserNowTimeStamp();

        // If the timestamp was during the current day
        if (date("Y m d", $timestamp) === date("Y m d", $now)) {
            // Use the time format
            $format = $this->dateConfig->getDefaultTimeFormat();
        } elseif (date("Y", $timestamp) === date("Y", $now)) {
            // If the timestamp is the same year, show the month and date
            $format = $this->dateConfig->getDefaultDayFormat();
        } else {
            // If the timestamp is not the same year, just show the year
            $format = $this->dateConfig->getDefaultYearFormat();
        }

        $format = $this->normalizeFormatForTimeStamp($format, $timestamp);
        return $format;
    }

    /**
     * Normalize a date format by emulating %l and %e for Windows for a given timestamp.
     *
     * @param string $format The format to normalize.
     * @param int $timestamp The timestamp to normalize for.
     *
     * @return string
     */
    private function normalizeFormatForTimeStamp(string $format, int $timestamp): string
    {
        if (strpos($format, "%l") !== false) {
            $format = str_replace("%l", ltrim(date("h", $timestamp), "0"), $format);
        }
        if (strpos($format, "g") !== false) {
            $format = str_replace("g", ltrim(date("h", $timestamp), "0"), $format);
        }
        if (strpos($format, "%e") !== false) {
            $format = str_replace("%e", ltrim(date("d", $timestamp), "0"), $format);
        }
        if (strpos($format, "j") !== false) {
            $format = str_replace("j", ltrim(date("d", $timestamp), "0"), $format);
        }
        $format = self::convertStrftimeToDateFormat($format);
        return $format;
    }

    /**
     * Given a date format for the deprecated {@link strftime()} function
     * convert it into a format for the {@link date} function.
     *
     * @param string $originalFormat
     * @return string
     */
    public static function convertStrftimeToDateFormat(string $originalFormat): string
    {
        $placeholders = [
            "%r" => "%I:%M:%S %p",
            "%R" => "%H:%M",
            "%D" => "%m/%d/%y",
            "%F" => "%Y-%m-%d",
        ];
        $originalFormat = str_replace(array_keys($placeholders), array_values($placeholders), $originalFormat);

        $conversions = [
            "%a" => "D", // 	Mon through Sun
            "%A" => "l", // 	Monday through Sunday
            "%d" => "d", // 	01 to 31
            "%e" => "j", // 	1 to 31
            "%j" => "z", // 	0 to 365 (day of the year)
            "%u" => "N", // 	1 (for Monday) through 7 (for Sunday)
            "%w" => "w", // 	0 (for Sunday) through 6 (for Saturday)
            "%W" => "W", // 	e.g. 42 (for the 42nd full week of the year, starting with the first Monday as the first week)
            "%b" => "M", // 	Jan through Dec
            "%h" => "M", // 	Jan through Dec
            "%B" => "F", // 	January through December
            "%m" => "m", // 	01 (for January) through 12 (for December)
            "%G" => "o", // 	The full four-digit version of %g
            "%y" => "y", // 	Two digit representation of the year, e.g. 14 for 2014
            "%Y" => "Y", // 	Four digit representation of the year, e.g. 2014
            "%H" => "H", // 	00 through 23 (two digit representation of the hour in 24-hour format)
            "%I" => "h", // 	01 through 23 (two digit representation of the hour in 12-hour format)
            "%l" => "g", // 	1 through 12 (hour in 12-hour format)
            "%P" => "a", // 	am or pm
            "%p" => "A", // 	AM or PM
            "%M" => "i", // 	00 through 59 (minutes)
            "%S" => "s", // 	00 through 59 (seconds)
            "%s" => "U", // 	Unix Epoch Time timestamp (same as the time() function)
            "%z" => "O", // 	Timezone offset, e.g. -0500 for US Eastern Time
            "%Z" => "T", // 	Timezone abbreviation, e.g. EST for US Eastern Time
        ];
        $result = str_replace(array_keys($conversions), array_values($conversions), $originalFormat);
        return $result;
    }

    /**
     * Spell out a number with localization between 1 and 11.
     *
     * @param int $num
     * @return string
     */
    public function spell1To11(int $num): string
    {
        switch ($num) {
            case 0:
            case 1:
                return self::t("a");
            case 2:
                return self::t("two");
            case 3:
                return self::t("three");
            case 4:
                return self::t("four");
            case 5:
                return self::t("five");
            case 6:
                return self::t("six");
            case 7:
                return self::t("seven");
            case 8:
                return self::t("eight");
            case 9:
                return self::t("nine");
            case 10:
                return self::t("ten");
            case 11:
                return self::t("eleven");
            default:
                return (string) $num;
        }
    }
}

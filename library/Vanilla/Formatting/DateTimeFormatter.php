<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\StaticCacheTranslationTrait;

/**
 * Formatting methods related to dates & times.
 */
class DateTimeFormatter {

    use StaticCacheTranslationTrait;

    const OUTPUT_TYPE_HTML = 'html';

    const FORCE_FULL_FORMAT = 'force-full-datetime-format';

    /** @var DateConfig */
    private $dateConfig;

    /**
     * @param DateConfig $dateConfig
     */
    public function __construct(DateConfig $dateConfig) {
        $this->dateConfig = $dateConfig;
    }

    /**
     * Format a MySQL DateTime string in the specified format.
     *
     * @link http://us.php.net/manual/en/function.strftime.php
     *
     * @param string|number $timestamp A timestamp or string in Mysql DateTime format. ie. YYYY-MM-DD HH:MM:SS
     * @param bool $isHtml Whether or not to output this as an HTML string.
     * @param string $format The format string to use. Defaults to the application's default format.
     * @return string
     */
    public function formatDate($timestamp = '', bool $isHtml = false, string $format = ''): string {
        // Was a mysqldatetime passed?
        if ($timestamp !== null && !is_numeric($timestamp)) {
            $timestamp = self::dateTimeToTimeStamp($timestamp);
        }

        if ($timestamp === null) {
            return self::t('Null Date', '-');
        }

        if (!$timestamp) {
            $timestamp = time();
        }
        $gmTimestamp = $timestamp;
        $timestamp = $this->adjustTimeStampForUser($timestamp);

        if ($format == '') {
            $format = $this->getDefaultFormatForTimestamp($timestamp);
        } elseif ($format === self::FORCE_FULL_FORMAT) {
            $format = $this->dateConfig->getDefaultDateTimeFormat();
            $format = $this->normalizeFormatForTimeStamp($format, $timestamp);
        }

        $result = strftime($format, $timestamp);

        if ($isHtml) {
            $fullFormat = $this->dateConfig->getDefaultDateTimeFormat();
            $fullFormat = $this->normalizeFormatForTimeStamp($fullFormat, $timestamp);
            $result = wrap(
                $result,
                'time',
                [
                    'title' => strftime($fullFormat, $timestamp),
                    'datetime' => gmdate('c', $gmTimestamp)
                ]
            );
        }
        return $result;
    }

    /**
     * Convert a datetime to a timestamp.
     *
     * @param string $dateTime The Mysql-formatted datetime to convert to a timestamp. Should be in one
     * of the following formats: YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.
     * @return string|bool Returns FALSE upon failure.
     */
    public function dateTimeToTimeStamp($dateTime = '') {
        if ($dateTime === '0000-00-00 00:00:00') {
            return false;
        } elseif (($testTime = strtotime($dateTime)) !== false) {
            return $testTime;
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s{1}(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/', $dateTime, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $hour = val(4, $matches, 0);
            $minute = val(5, $matches, 0);
            $second = val(6, $matches, 0);
            return mktime($hour, $minute, $second, $month, $day, $year);
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateTime, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            return mktime(0, 0, 0, $month, $day, $year);
        } else {
            return false;
        }
    }

    /**
     * Show times relative to now, e.g. "4 hours ago".
     *
     * Credit goes to: http://byteinn.com/res/426/Fuzzy_Time_function/
     *
     * @param int|string|null $timestamp otherwise time() is used
     * @param bool $morePrecise
     * @return string
     */
    public function formatRelativeTime($timestamp = null, bool $morePrecise = false): string {
        if (is_null($timestamp)) {
            $timestamp = time();
        } elseif (!is_numeric($timestamp)) {
            $timestamp = self::dateTimeToTimeStamp($timestamp);
        }

        $time = $timestamp;

        $now = time();
        if (!defined('ONE_MINUTE')) {
            define('ONE_MINUTE', 60);
        }
        if (!defined('ONE_HOUR')) {
            define('ONE_HOUR', 3600);
        }
        if (!defined('ONE_DAY')) {
            define('ONE_DAY', 86400);
        }
        if (!defined('ONE_WEEK')) {
            define('ONE_WEEK', ONE_DAY * 7);
        }
        if (!defined('ONE_MONTH')) {
            define('ONE_MONTH', ONE_WEEK * 4);
        }
        if (!defined('ONE_YEAR')) {
            define('ONE_YEAR', ONE_MONTH * 12);
        }

        $secondsAgo = $now - $time;

        // sod = start of day :)
        $sod = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
        $sod_now = mktime(0, 0, 0, date('m', $nOW), date('d', $nOW), date('Y', $nOW));

        // Today
        if ($sod_now == $sod) {
            if ($time > $now - (ONE_MINUTE * 3)) {
                return self::t('just now');
            } elseif ($time > $now - (ONE_MINUTE * 7)) {
                return self::t('a few minutes ago');
            } elseif ($time > $now - (ONE_HOUR)) {
                if ($morePrecise) {
                    $minutesAgo = ceil($secondsAgo / 60);
                    return sprintf(self::t('%s minutes ago'), $minutesAgo);
                }
                return self::t('less than an hour ago');
            }
            return sprintf(self::t('today at %s'), date('g:ia', $time));
        }

        // Yesterday
        if (($sod_now - $sod) <= ONE_DAY) {
            if (date('i', $time) > (ONE_MINUTE + 30)) {
                $time += ONE_HOUR / 2;
            }
            return sprintf(self::t('yesterday around %s'), date('ga', $time));
        }

        // Within the last 5 days.
        if (($sod_now - $sod) <= (ONE_DAY * 5)) {
            $str = date('l', $time);
            $hour = date('G', $time);
            if ($hour < 12) {
                $str .= self::t(' morning');
            } elseif ($hour < 17) {
                $str .= self::t(' afternoon');
            } elseif ($hour < 20) {
                $str .= self::t(' evening');
            } else {
                $str .= self::t(' night');
            }
            return $str;
        }

        // Number of weeks (between 1 and 3).
        if (($sod_now - $sod) < (ONE_WEEK * 3.5)) {
            if (($sod_now - $sod) < (ONE_WEEK * 1.5)) {
                return self::t('about a week ago');
            } elseif (($sod_now - $sod) < (ONE_DAY * 2.5)) {
                return self::t('about two weeks ago');
            } else {
                return self::t('about three weeks ago');
            }
        }

        // Number of months (between 1 and 11).
        if (($sod_now - $sod) < (ONE_MONTH * 11.5)) {
            for ($i = (ONE_WEEK * 3.5), $m = 0; $i < ONE_YEAR; $i += ONE_MONTH, $m++) {
                if (($sod_now - $sod) <= $i) {
                    return sprintf(
                        self::t('about %s month%s ago'),
                        $this->spell1To11($m),
                        (($m > 1) ? 's' : '')
                    );
                }
            }
        }

        // Number of years.
        for ($i = (ONE_MONTH * 11.5), $y = 0; $i < (ONE_YEAR * 10); $i += ONE_YEAR, $y++) {
            if (($sod_now - $sod) <= $i) {
                return sprintf(
                    self::t('about %s year%s ago'),
                    $this->spell1To11($y),
                    (($y > 1) ? 's' : '')
                );
            }
        }

        // More than ten years.
        return self::t('more than ten years ago');
    }

    /**
     * Convert a timestamp into human readable seconds.
     *
     * @see DateTimeFormatter::formatSeconds()
     *
     * @param string $datetime
     * @return int
     */
    public function dateTimeToSeconds($datetime): int {
        return abs(time() - $this->dateTimeToTimeStamp($datetime));
    }

    /**
     * Formats seconds in a human-readable way
     * (ie. 45 seconds, 15 minutes, 2 hours, 4 days, 2 months, etc).
     *
     * @param int $seconds
     * @return string
     */
    public function formatSeconds(int $seconds): string {
        $minutes = round($seconds / 60);
        $hours = round($seconds / 3600);
        $days = round($seconds / 86400);
        $weeks = round($seconds / 604800);
        $months = round($seconds / 2629743.83);
        $years = round($seconds / 31556926);

        if ($seconds < 60) {
            return sprintf(plural($seconds, '%s second', '%s seconds'), $seconds);
        } elseif ($minutes < 60) {
            return sprintf(plural($minutes, '%s minute', '%s minutes'), $minutes);
        } elseif ($hours < 24) {
            return sprintf(plural($hours, '%s hour', '%s hours'), $hours);
        } elseif ($days < 7) {
            return sprintf(plural($days, '%s day', '%s days'), $days);
        } elseif ($weeks < 4) {
            return sprintf(plural($weeks, '%s week', '%s weeks'), $weeks);
        } elseif ($months < 12) {
            return sprintf(plural($months, '%s month', '%s months'), $months);
        } else {
            return sprintf(plural($years, '%s year', '%s years'), $years);
        }
    }

    /**
     * Convert a timetstamp to time formatted as H::MM::SS (g:i:s).
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public function timestampToTime(int $timestamp): string {
        return date('g:i:s', $timestamp);
    }

    /**
     * Convert a timetstamp to date formatted as D-m-d
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public function timestampToDate(int $timestamp): string {
        return date('D-m-d', $timestamp);
    }

    /**
     * Convert a timetstamp to datetime formatted as Y-m-d H:i:s.
     *
     * @param int $timestamp The timestamp to use.
     *
     * @return string The formatted value.
     */
    public function timestampToDateTime(int $timestamp): string {
        return date('Y-m-d H:i:s', $timestamp);
    }


    /**
     * Adjust a timestamp for the sessioned user's time offset.
     *
     * @param int $timestamp
     * @return int
     */
    public function adjustTimeStampForUser(int $timestamp): int {
        $hourOffset = $this->dateConfig->getHourOffset();
        $secondsOffset = $hourOffset * 3600;
        $timestamp += $secondsOffset;
        return $timestamp;
    }


    /**
     * Get the current timestamp adjusted for the user's hour offset.
     *
     * @return int
     */
    private function getNowTimeStamp(): int {
        $now = time();
        return $this->adjustTimeStampForUser($now);
    }

    /**
     * Get a relative date format based on how old a timestamp is.
     *
     * @param int $timestamp
     * @return string The format.
     */
    private function getDefaultFormatForTimestamp(int $timestamp): string {
        $now = $this->getNowTimeStamp();

        // If the timestamp was during the current day
        if (date('Y m d', $timestamp) == date('Y m d', $now)) {
            // Use the time format
            $format = $this->dateConfig->getDefaultTimeFormat();
        } elseif (date('Y', $timestamp) == date('Y', $now)) {
            // If the timestamp is the same year, show the month and date
            $format = $this->dateConfig->getDefaultDayFormat();
        } elseif (date('Y', $timestamp) != date('Y', $now)) {
            // If the timestamp is not the same year, just show the year
            $format = $this->dateConfig->getDefaultYearFormat();
        } else {
            // Otherwise, use the date format
            $format = $this->dateConfig->getDefaultFormat();
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
    private function normalizeFormatForTimeStamp(string $format, int $timestamp): string {
        if (strpos($format, '%l') !== false) {
            $format = str_replace('%l', ltrim(strftime('%I', $timestamp), '0'), $format);
        }
        if (strpos($format, '%e') !== false) {
            $format = str_replace('%e', ltrim(strftime('%d', $timestamp), '0'), $format);
        }
        return $format;
    }

    /**
     * Spell out a number with localization between 1 and 11.
     *
     * @param int $num
     * @return string
     */
    private function spell1To11(int $num): string {
        switch ($num) {
            case 0:
            case 1:
                return self::t('a');
            case 2:
                return self::t('two');
            case 3:
                return self::t('three');
            case 4:
                return self::t('four');
            case 5:
                return self::t('five');
            case 6:
                return self::t('six');
            case 7:
                return self::t('seven');
            case 8:
                return self::t('eight');
            case 9:
                return self::t('nine');
            case 10:
                return self::t('ten');
            case 11:
                return self::t('eleven');
            default:
                return (string) $num;
        }
    }
}

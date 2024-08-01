<?php
/**
 * Analytics system.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0.17
 */

use Vanilla\CurrentTimeStamp;

/**
 * Handles install-side analytics gathering and sending.
 */
class Gdn_Statistics extends Gdn_Pluggable
{
    /**
     * This is the asynchronous callback.
     *
     * This method is triggerd on every page request via a callback AJAX request
     * so that it may execute asychronously and reduce lag for users. It tracks
     * views, handles registration for new installations, and sends stats every day as needed.
     *
     * @return void
     */
    public function tick()
    {
        // Fire an event for plugins to track their own stats.
        // TODO: Make this analyze the path and throw a specific event (this event will change in future versions).
        $this->EventArguments["Path"] = Gdn::request()->post("Path");
        $this->fireEvent("Tick");

        if (Gdn::session()->isValid()) {
            Gdn::userModel()->updateVisit(Gdn::session()->UserID);
        }
    }

    /**
     *
     *
     * @return int
     */
    public static function time()
    {
        return CurrentTimeStamp::get();
    }

    /**
     *
     *
     * @param string $slotType
     * @param bool $timestamp
     * @return string
     */
    public static function timeSlot($slotType = "d", $timestamp = false)
    {
        if (!$timestamp) {
            $timestamp = self::time();
        }

        if ($slotType == "d") {
            $result = gmdate("Ymd", $timestamp);
        } elseif ($slotType == "w") {
            $sub = gmdate("N", $timestamp) - 1;
            $timestamp = strtotime("-$sub days", $timestamp);
            $result = gmdate("Ymd", $timestamp);
        } elseif ($slotType == "m") {
            $result = gmdate("Ym", $timestamp) . "00";
        } elseif ($slotType == "y") {
            $result = gmdate("Y", $timestamp) . "0000";
        } elseif ($slotType == "a") {
            $result = "00000000";
        }

        return $result;
    }

    /**
     *
     *
     * @param string $slotType
     * @param bool $timestamp
     * @return int
     * @throws Exception
     */
    public static function timeSlotStamp($slotType = "d", $timestamp = false)
    {
        $result = self::timeFromTimeSlot(self::timeSlot($slotType, $timestamp));
        return $result;
    }

    /**
     *
     *
     * @param $timeSlot
     * @return int
     * @throws Exception
     */
    public static function timeFromTimeSlot($timeSlot)
    {
        if ($timeSlot == "00000000") {
            return 0;
        }

        $year = substr($timeSlot, 0, 4);
        $month = substr($timeSlot, 4, 2);
        $day = (int) substr($timeSlot, 6, 2);
        if ($day == 0) {
            $day = 1;
        }
        $dateRaw = mktime(0, 0, 0, $month, $day, $year);

        if ($dateRaw === false) {
            throw new Exception("Invalid timeslot '{$timeSlot}', unable to convert to epoch");
        }

        return $dateRaw;
    }
}

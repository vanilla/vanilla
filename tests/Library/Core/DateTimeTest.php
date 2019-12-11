<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use DateTime;
use DateTimeZone;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for some date time utilities.
 */
class DateTimeTest extends MinimalContainerTestCase {

    /**
     * Test dateCompare.
     *
     * @param mixed $date1
     * @param mixed $date2
     * @param int $expected
     *
     * @dataProvider provideDateComparisons
     */
    public function testDateCompare($date1, $date2, int $expected) {
        $result = dateCompare($date1, $date2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function provideDateComparisons(): array {
        $now = time();
        return [
            ['2015-12-24 12:12:12', '2015-12-24 12:12:11', 1],
            ['2015-12-24 12:12:12', '2015-12-24 12:12:12', 0],
            ['2015-12-24 12:12:12', '2015-12-24 12:12:13', -1],
            [$now, $now - 1, 1],
            [$now, $now, 0],
            [$now, $now + 1, -1],
        ];
    }

    /**
     * Test that different named timezones in the same place produce equivalent dates.
     *
     * @param string $dateString A string representation of a date.
     * @param DateTimeZone $tz1 The first timezone to compare.
     * @param DateTimeZone $tz2 The second timezone to compare.
     * @dataProvider provideDateAndTimeZones
     */
    public function testDifferentTimezones($dateString, DateTimeZone $tz1, DateTimeZone $tz2) {
        $dt1 = new DateTime($dateString, $tz1);
        $dt2 = new DateTime($dateString, $tz2);

        $this->assertEquals($dt1, $dt2);
    }

    /**
     * Provide some date string in and out of DST.
     *
     * @return array Returns a data provider array.
     */
    public function provideDateStrings() {
        return $r = [
            0 => ['2016-04-25'],
            1 => ['2016-12-01']
        ];
    }

    /**
     * Provide test data for {@link testDifferentTimezones}.
     *
     * @return array Returns a data provider array.
     */
    public function provideDateAndTimeZones() {
        $timezones = ['America/Montreal', 'America/Detroit'];
        $dates = array_column($this->provideDateStrings(), 0);

        $r = [];
        foreach ($dates as $date) {
            foreach ($timezones as $i => $timezone1) {
                $tz1 = new DateTimeZone($timezone1);
                foreach ($timezones as $j => $timezone2) {
                    if ($j <= $i) {
                        continue;
                    }

                    $tz2 = new DateTimeZone($timezone2);
                    $r["$date $timezone1 vs $timezone2"] = [
                        $date,
                        $tz1,
                        $tz2
                    ];
                }
            }
        }
        return $r;
    }
}

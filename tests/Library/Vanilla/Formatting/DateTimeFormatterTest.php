<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Formatting;

use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\TimeUnit;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\MinimalContainerTestCase;

/**
 * Test for the DateTimeFormatter.
 */
class DateTimeFormatterTest extends MinimalContainerTestCase {

    use HtmlNormalizeTrait;

    // Saturday, July 27, 2015 12:00:01 AM
    const NOW = 1437955201;

    /**
     * @return DateTimeFormatter
     */
    private function getFormatter(): DateTimeFormatter {
        /** @var DateTimeFormatter $formatter */
        $formatter = self::container()->get(DateTimeFormatter::class);
        $formatter->setNowTimeStamp(self::NOW);
        return $formatter;
    }

    /**
     * Test the HTML formatting.
     * This test needs a separate process because of the time zone setting.
     *
     * @preserveGlobalState
     * @runInSeparateProcess
     */
    public function testFormatDateHtml() {
        date_default_timezone_set("UTC");
        $actual = self::getFormatter()->formatDate(self::NOW, true);
        $expected = '<time datetime=2015-07-27T00:00:01+00:00 title="Mon Jul 27 00:00:01 2015">12:00AM</time>';
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test passing a custom format.
     */
    public function testFormatDateCustomFormat() {
        $format = 'Custom %m.%d.%y';
        $actual = self::getFormatter()->formatDate(
            self::NOW,
            false,
            $format
        );
        $expected = 'Custom 07.27.15';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test passing a custom format.
     * This test needs a separate process because of the time zone setting.
     *
     * @preserveGlobalState
     * @runInSeparateProcess
     */
    public function testForceFullFormat() {
        date_default_timezone_set("UTC");
        $actual = self::getFormatter()->formatDate(
            self::NOW,
            false,
            DateTimeFormatter::FORCE_FULL_FORMAT
        );
        $expected = 'Mon Jul 27 00:00:01 2015';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test passing a custom format.
     */
    public function testCustomConfigFormat() {
        self::setTranslation('Date.DefaultDateTimeFormat', 'Custom %m.%d.%y');
        $actual = self::getFormatter()->formatDate(
            self::NOW,
            false,
            DateTimeFormatter::FORCE_FULL_FORMAT
        );
        $expected = 'Custom 07.27.15';
        $this->assertEquals($expected, $actual);
    }


    /**
     * Tests for date formatting edges for what relative value we use.
     *
     * @inheritdoc Don't validate params.
     * @dataProvider dateEdgeProviders
     */
    public function testFormatDateEdges($timestamp, string $expected) {
        $actual = self::getFormatter()->formatDate($timestamp, false);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function dateEdgeProviders(): array {
        // Now is July 27
        $edgeOfToday = $this->modifyFromNow('midnight');
        $edgeOfYesteday = $this->modifyFromNow(['midnight', '-1 sec']);
        $edgeOfTheYear = $this->modifyFromNow('first day of january this year');
        $edgeOfLastYear = $this->modifyFromNow(['first day of january this year', 'midnight', '-1 sec']);

        return [
            // Within the same day.
            'now' => [
                self::NOW,
                '12:00AM', // Show the time.
            ],
            'edge of today' => [
                $edgeOfToday,
                '12:00AM', // Show the time.
            ],
            'edge of yesterday' => [
                $edgeOfYesteday, // Technically yesterday.
                'July 26', // Switch to showing day.
            ],
            'edge of the year' => [
                $edgeOfTheYear,
                'January 1', // Switch to showing year.
            ],
            'edge of last year' => [
                $edgeOfLastYear,
                'December 2014', // Start showing year.
            ],
        ];
    }

    /**
     * Test various different allowed timestamp values.
     *
     * @param mixed $timestamp
     * @param mixed $expected
     *
     * @dataProvider timeStampEdgeCaseProvider
     */
    public function testFormatDateTimestampRobustness($timestamp, $expected) {
        $actual = self::getFormatter()->formatDate($timestamp, false);
        $this->assertEquals($expected, $actual);
    }


    /**
     * Test various alternate values for the timestamp.
     *
     * @return array
     */
    public function timeStampEdgeCaseProvider(): array {
        return [
            [null, DateTimeFormatter::NULL_TIMESTAMP_DEFALT_VALUE],
            ['2015-12-24 12:12:12', 'December 24'], // Converted from a timestamp.
            ['2015-12-24', 'December 24'], // Converted from a timestamp.
        ];
    }

    /**
     * Test various different allowed timestamp values.
     *
     * @param mixed $timestamp
     * @param mixed $expected
     * @param bool $isWarning
     *
     * @dataProvider timeStampDeprecatedProviders
     */
    public function testDateTimeToTimestampDeprecated($timestamp, $expected, bool $isWarning = false) {
        if ($isWarning) {
            $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        }
        $actual = DateTimeFormatter::dateTimeToTimeStamp($timestamp);
        $this->assertEquals($expected, $actual);
    }


    /**
     * Test various alternate values for the timestamp.
     *
     * @return array
     */
    public function timeStampDeprecatedProviders(): array {
        return [
            ['2015-12-24 12:12:12', 1450959132],
            ['2015-12-24', 1450915200],
            [null, self::NOW, true],
            ['asdfasdf', self::NOW, true],
        ];
    }

    /**
     * Tests for testSpell1To11.
     *
     * @param int $input
     * @param string $expected
     * @dataProvider provide1To11
     */
    public function testSpell1To11(int $input, string $expected) {
        self::setTranslation('seven', 'Custom Seven!!');
        $actual = self::getFormatter()->spell1To11($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function provide1To11(): array {
        return [
            [0, 'a'],
            [1, 'a'],
            [2, 'two'],
            [3, 'three'],
            [4, 'four'],
            [5, 'five'],
            [6, 'six'],
            [7, 'Custom Seven!!'],
            [8, 'eight'],
            [9, 'nine'],
            [10, 'ten'],
            [11, 'eleven'],
            // Higher numbers just get converted to strings.
            [12, '12'],
            [1000, '1000'],
        ];
    }

    /**
     * Tests for formatRelativeTime using a data provider.
     *
     * @param mixed $timestamp
     * @param string $expected
     * @param int $currentTime
     *
     * @dataProvider provideRelativeTimeDifferentDay
     */
    public function testFormatRelativeTime($timestamp, string $expected, int $currentTime = self::NOW) {
        $formatter = self::getFormatter();
        $formatter->setNowTimeStamp($currentTime);
        $actual = $formatter->formatRelativeTime($timestamp);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function provideRelativeTimeDifferentDay(): array {
        // Now is July 27

        $noon = $this->modifyFromNow('noon');
        $fewBeforeNoon = $this->modifyFromNow(['noon', '-3 minutes', '+1 sec']);
        $eightBeforeNoon = $this->modifyFromNow(['noon', '-7 minutes', '+1 sec']);
        $thirtyBeforeNoon = $this->modifyFromNow(['noon', '-30 minutes', '+1 sec']);
        $fiftyNineBeforeNoon = $this->modifyFromNow(['noon', '-59 minutes']);
        $startOfDay = $this->modifyFromNow(['midnight']);

        return [
            // Today
            'just now' => [$fewBeforeNoon, 'just now', $noon],
            'a few minutes ago' => [$eightBeforeNoon, 'a few minutes ago', $noon],
            '30 mins' => [$thirtyBeforeNoon, '30 minutes ago', $noon],
            'less than hour' => [$fiftyNineBeforeNoon, 'less than an hour ago', $noon],
            'today' => [$startOfDay, 'today at 12:00am', $noon],

            // Non-standard inputs.
            'timeStamp' => [DateTimeFormatter::timeStampToDateTime(self::NOW), 'just now'],
            'null' => [null, 'just now'],
            // Within the time period, but the day rolled over.
            'edge of yesterday close' => [$this->modifyFromNow(['midnight', '-1 sec']), 'yesterday around 11pm'],
            // More than 24 hours, but yesterday.
            'start of yesterday' => [$this->modifyFromNow(['-1 day', 'midnight']), 'yesterday around 12am'],
            'two days ago morning' => [$this->modifyFromNow(['-2 day', '+11 hours']), 'Saturday morning'],
            'three days ago afternoon' => [$this->modifyFromNow(['-3 day', 'noon']), 'Friday afternoon'],
            'four days ago evening' => [$this->modifyFromNow(['-4 day', '+17 hours']), 'Thursday evening'],
            'five days ago night' => [$this->modifyFromNow(['-4 day', 'midnight', '-1 sec']), 'Wednesday night'],
            '6 days' => [$this->modifyFromNow(['-6 days']), 'about a week ago'],
            'one week ago' => [$this->modifyFromNow(['-1 week', '1 day']), 'about a week ago'],
            'two weeks ago' => [$this->modifyFromNow(['-1 week', '-1 day']), 'about two weeks ago'],
            'three weeks ago' => [$this->modifyFromNow(['-3 week']), 'about three weeks ago'],
            'months' => [$this->modifyFromNow('-2 months'), 'about two months ago'],
            'years' => [$this->modifyFromNow('-4 years'), 'about four years ago'],
            'more than 10' => [$this->modifyFromNow('-10 years'), 'more than ten years ago'],
        ];
    }

    /**
     * Tests for formatSeconds using a data provider.
     *
     * @param mixed $seconds
     * @param string $expected
     *
     * @dataProvider provideSeconds
     */
    public function testFormatSeconds(int $seconds, string $expected) {
        $actual = self::getFormatter()->formatSeconds($seconds);
        $this->assertEquals($expected, $actual);
    }
    /**
     * @return array
     */
    public function provideSeconds(): array {
        return [
            [-100, '-100 seconds'],
            [0, '0 seconds'],
            [1, '1 second'],
            [59, '59 seconds'],
            [TimeUnit::ONE_MINUTE, '1 minute'],
            [59 * TimeUnit::ONE_MINUTE, '59 minutes'],
            [TimeUnit::ONE_HOUR, '1 hour'],
            [23 * TimeUnit::ONE_HOUR, '23 hours'],
            [TimeUnit::ONE_DAY, '1 day'],
            [6 * TimeUnit::ONE_DAY, '6 days'], // Huh
            [TimeUnit::ONE_WEEK, '1 week'],
            [3 * TimeUnit::ONE_WEEK, '3 weeks'], // Huh
            [TimeUnit::ONE_MONTH, '1 month'],
            [11 * TimeUnit::ONE_MONTH, '11 months'],
            [TimeUnit::ONE_YEAR, '1 year'],
            [5555 * TimeUnit::ONE_YEAR, '5555 years'],
        ];
    }

    /**
     * Test for the utility function.
     */
    public function testTimeStampToTime() {
        $actual = DateTimeFormatter::timeStampToTime(self::NOW);
        $expected = '12:00:01';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for the utility function.
     */
    public function testTimeStampToDateTime() {
        $actual = DateTimeFormatter::timeStampToDateTime(self::NOW);
        $expected = '2015-07-27 00:00:01';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for the utility function.
     */
    public function testTimeStampToDate() {
        $actual = DateTimeFormatter::timeStampToDate(self::NOW);
        $expected = '2015-07-27';
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for the utility function.
     */
    public function testDateTimeToSecondsAgo() {
        $date = DateTimeFormatter::timeStampToDateTime(self::NOW);
        $actual = DateTimeFormatter::dateTimeToSecondsAgo($date, self::NOW);
        $expected = 0;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Modify a timestamp using DateTime::modify().
     *
     * @param array|string $modifys The values to modify the date with.
     * @return int
     */
    private function modifyFromNow($modifys): int {
        if (!is_array($modifys)) {
            $modifys = [$modifys];
        }
        $date = new \DateTime(DateTimeFormatter::timeStampToDateTime(self::NOW));
        foreach ($modifys as $modify) {
            $date = $date->modify($modify);
        }
        return $date->getTimestamp();
    }
}

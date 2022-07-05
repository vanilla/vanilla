<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use PHPUnit\Framework\TestCase;
use Vanilla\CurrentTimeStamp;

/**
 * Some basic tests for the `CurrentTimeStamp` class.
 */
class CurrentTimeStampTest extends TestCase
{
    /**
     * @var \DateTimeImmutable
     */
    private $mockTime;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * Test basic mock time equivalence.
     */
    public function testCurrentTime()
    {
        $this->mockTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        CurrentTimeStamp::mockTime($this->mockTime);

        $this->assertSame($this->mockTime->getTimestamp(), CurrentTimeStamp::get());
        $this->assertEquals($this->mockTime, CurrentTimeStamp::getDateTime());
        $this->assertSame($this->mockTime->format(MYSQL_DATE_FORMAT), CurrentTimeStamp::getMySQL());
    }

    /**
     * Test conditions that should cause toWindowStart to throw an exception
     *
     * @param \DateInterval $window
     * @param string $expectedException
     * @throws \Exception Emitted by DateTimeImmutable.
     * @dataProvider toWindowStartExceptionDataProvider
     */
    public function testToWindowStartExceptions(\DateInterval $window, string $expectedException): void
    {
        $this->mockTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        CurrentTimeStamp::mockTime($this->mockTime);

        $this->expectException($expectedException);
        $_ = CurrentTimeStamp::toWindowStart($window);
    }

    /**
     * Data Provider for toWindowStartException tests
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function toWindowStartExceptionDataProvider(): iterable
    {
        $expectedException = \InvalidArgumentException::class;
        yield "zero window" => [
            "window" => new \DateInterval("PT0S"),
            "expectedException" => $expectedException,
        ];
        $negativeOneMinInterval = new \DateInterval("PT1M");
        $negativeOneMinInterval->invert = 1;

        yield "negative window" => [
            "window" => $negativeOneMinInterval,
            "expectedException" => $expectedException,
        ];
    }

    /**
     * Unit Test toWindowStart function
     *
     * @param \DateTimeInterface $now
     * @param \DateInterval $window
     * @param \DateTimeInterface $expected
     * @dataProvider toWindowStartDataProvider
     */
    public function testToWindowStart(
        \DateTimeInterface $now,
        \DateInterval $window,
        \DateTimeInterface $expected
    ): void {
        $this->mockTime = $now;
        CurrentTimeStamp::mockTime($this->mockTime);
        $windowStart = CurrentTimeStamp::toWindowStart($window);
        $this->assertEquals($expected, $windowStart);
    }

    /**
     * Data Provider for testing toNextWindow
     *
     * @return iterable
     * @throws \Exception Emitted by DateTimeImmutable.
     * @codeCoverageIgnore
     */
    public function toWindowStartDataProvider(): iterable
    {
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        $twoMinuteInterval = new \DateInterval("PT2M");
        $oneMin30SecInterval = new \DateInterval("PT1M30S");
        $windowStart = new \DateTimeImmutable("2020-08-04 11:38:00", new \DateTimeZone("UTC"));

        yield "2 min window, ahead of window start" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "expected" => $windowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:38:00", new \DateTimeZone("UTC"));
        yield "2 min window, on window start" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "expected" => $windowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:59", new \DateTimeZone("UTC"));
        yield "2 min window, at window end" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "expected" => $windowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        $windowStart = new \DateTimeImmutable("2020-08-04 11:39:00", new \DateTimeZone("UTC"));
        yield "1 min 30 sec window, ahead of window start" => [
            "now" => $mockDateTime,
            "window" => $oneMin30SecInterval,
            "expected" => $windowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:00", new \DateTimeZone("UTC"));
        yield "1 min 30 sec window, at window start" => [
            "now" => $mockDateTime,
            "window" => $oneMin30SecInterval,
            "expected" => $windowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:40:29", new \DateTimeZone("UTC"));
        yield "1 min 30 sec window, at window end" => [
            "now" => $mockDateTime,
            "window" => $oneMin30SecInterval,
            "expected" => $windowStart,
        ];
    }

    /**
     * Test conditions that should cause toNextWindow to throw an exception
     *
     * @param \DateInterval $window
     * @param \DateInterval|null $rollover
     * @param string $expectedException
     * @throws \Exception Emitted by DateTimeImmutable.
     * @dataProvider toNextWindowExceptionDataProvider
     */
    public function testToNextWindowExceptions(
        \DateInterval $window,
        ?\DateInterval $rollover,
        string $expectedException
    ): void {
        $this->mockTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        CurrentTimeStamp::mockTime($this->mockTime);

        $this->expectException($expectedException);
        $_ = CurrentTimeStamp::toNextWindow($window, $rollover);
    }

    /**
     * Data Provider for toNextWindowException tests
     *
     * @return iterable
     * @codeCoverageIgnore
     */
    public function toNextWindowExceptionDataProvider(): iterable
    {
        $expectedException = \InvalidArgumentException::class;
        $oneSecInterval = new \DateInterval("PT1S");
        $oneMinInterval = new \DateInterval("PT1M");
        yield "zero window" => [
            "window" => new \DateInterval("PT0S"),
            "rollover" => null,
            "expectedException" => $expectedException,
        ];
        yield "rollover equals window" => [
            "window" => $oneSecInterval,
            "rollover" => $oneSecInterval,
            "expectedException" => $expectedException,
        ];
        yield "rollover greater than window" => [
            "window" => $oneSecInterval,
            "rollover" => $oneMinInterval,
            "expectedException" => $expectedException,
        ];
        $negativeOneMinInterval = new \DateInterval("PT1M");
        $negativeOneMinInterval->invert = 1;

        yield "negative window" => [
            "window" => $negativeOneMinInterval,
            "rollover" => $oneMinInterval,
            "expectedException" => $expectedException,
        ];
        yield "negative rollover" => [
            "window" => $oneMinInterval,
            "rollover" => $negativeOneMinInterval,
            "expectedException" => $expectedException,
        ];
    }

    /**
     * Unit Test toNextWindow function
     *
     * @param \DateTimeInterface $now
     * @param \DateInterval $window
     * @param \DateInterval|null $rollover
     * @param \DateTimeInterface $expected
     * @dataProvider toNextWindowDataProvider
     */
    public function testToNextWindow(
        \DateTimeInterface $now,
        \DateInterval $window,
        ?\DateInterval $rollover,
        \DateTimeInterface $expected
    ): void {
        $this->mockTime = $now;
        CurrentTimeStamp::mockTime($this->mockTime);
        $nextWindowStart = CurrentTimeStamp::toNextWindow($window, $rollover);
        $this->assertEquals($expected, $nextWindowStart);
    }

    /**
     * Data Provider for testing toNextWindow
     *
     * @return iterable
     * @throws \Exception Emitted by DateTimeImmutable.
     * @codeCoverageIgnore
     */
    public function toNextWindowDataProvider(): iterable
    {
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:15", new \DateTimeZone("UTC"));
        $twoMinuteInterval = new \DateInterval("PT2M");
        $oneMin30SecInterval = new \DateInterval("PT1M30S");
        $oneMinuteInterval = new \DateInterval("PT1M");
        $thirtySecInterval = new \DateInterval("PT30S");
        $twentyMinuteInterval = new \DateInterval("PT20M");

        $nextWindowStart = new \DateTimeImmutable("2020-08-04 11:40:00", new \DateTimeZone("UTC"));
        $twoMinuteWindowRolloverStart = new \DateTimeImmutable("2020-08-04 11:42:00", new \DateTimeZone("UTC"));
        $twentyMinuteWindowRolloverStart = new \DateTimeImmutable("2020-08-04 12:00:00", new \DateTimeZone("UTC"));

        yield "2 min window, no rollover interval" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => null,
            "expected" => $nextWindowStart,
        ];
        yield "2 min window, 1 min rollover => rollover past next window" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => $oneMinuteInterval,
            "expected" => $twoMinuteWindowRolloverStart,
        ];
        yield "2 min window, 30 sec rollover => does not rollover past next window" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => $thirtySecInterval,
            "expected" => $nextWindowStart,
        ];
        yield "2 min window, 1 min 30 sec rollover => rollover past next window" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => $oneMin30SecInterval,
            "expected" => $twoMinuteWindowRolloverStart,
        ];
        yield "20 min window, 30 sec rollover => no rollover past next window" => [
            "now" => $mockDateTime,
            "window" => $twentyMinuteInterval,
            "rollover" => $thirtySecInterval,
            "expected" => $nextWindowStart,
        ];
        yield "20 min window, 1 min rollover => rollover past next window" => [
            "now" => $mockDateTime,
            "window" => $twentyMinuteInterval,
            "rollover" => $oneMinuteInterval,
            "expected" => $twentyMinuteWindowRolloverStart,
        ];

        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:40:00", new \DateTimeZone("UTC"));
        $nextWindowStart = new \DateTimeImmutable("2020-08-04 11:42:00", new \DateTimeZone("UTC"));
        yield "date time divisible by window, 2 min window, no rollover" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => $oneMin30SecInterval,
            "expected" => $nextWindowStart,
        ];
        $nextWindowStart = new \DateTimeImmutable("2020-08-04 12:00:00", new \DateTimeZone("UTC"));
        yield "date time divisible by window, 20 min window" => [
            "now" => $mockDateTime,
            "window" => $twentyMinuteInterval,
            "rollover" => null,
            "expected" => $nextWindowStart,
        ];
        $mockDateTime = new \DateTimeImmutable("2020-08-04 11:39:30", new \DateTimeZone("UTC"));
        $rolloverWindowStart = new \DateTimeImmutable("2020-08-04 11:42:00", new \DateTimeZone("UTC"));
        yield "date time at rollover threshold, 2 min window" => [
            "now" => $mockDateTime,
            "window" => $twoMinuteInterval,
            "rollover" => $thirtySecInterval,
            "expected" => $rolloverWindowStart,
        ];
    }
}

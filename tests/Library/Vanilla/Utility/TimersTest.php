<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\Timers;

/**
 * Tests for the `Timers` class.
 */
class TimersTest extends TestCase {
    /**
     * @var Timers
     */
    private $timers;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();

        $this->timers = new Timers();
        $this->timers->start('test');
    }

    /**
     * Test stopping the test timer.
     */
    public function testStopTimer(): void {
        $timer = $this->timers->stop('test');
        $this->assertSame(1, $timer['count']);
        $this->assertSame($timer['time'], $timer['min']);
        $this->assertSame($timer['time'], $timer['max']);
        $this->assertSame(Timers::formatDuration($timer['time']), $timer['human']);
    }

    /**
     * Test calling `start()/stop()` with an array of names.
     */
    public function testMultiSyntax(): void {
        $timers = $this->timers->start(['a', 'b']);
        $this->assertArrayHasKey('a', $timers);
        $this->assertArrayHasKey('b', $timers);
        foreach ($timers as $timer) {
            $this->assertSame(1, $timer['count']);
            $this->assertArrayHasKey('start', $timer);
        }

        $timers = $this->timers->stop(['a', 'b']);
        $this->assertArrayHasKey('a', $timers);
        $this->assertArrayHasKey('b', $timers);
        foreach ($timers as $timer) {
            $this->assertSame(1, $timer['count']);
            $this->assertArrayHasKey('stop', $timer);
            $this->assertSame(Timers::formatDuration($timer['time']), $timer['human']);
        }
    }

    /**
     * Test `Timers::time()`.
     */
    public function testTime(): void {
        $r = $this->timers->time(['foo', 'bar'], function () {
            return 'baz';
        });
        $this->assertSame('baz', $r);
        foreach (['foo', 'bar'] as $name) {
            $timer = $this->timers->get($name);
            $this->assertIsArray($timer);
            $this->assertSame(1, $timer['count']);
            $this->assertArrayHasKey('stop', $timer);
        }
    }

    /**
     * Getting a non-existent timer should return null.
     */
    public function testNonexistent(): void {
        $this->assertNull($this->timers->get('foo'));
    }

    /**
     * You should be able to start a timer twice and have it count as stopped.
     */
    public function testStartingTimerTwice(): void {
        usleep(1);
        $timer = @$this->timers->start('test');
        $this->assertSame(2, $timer['count']);

        usleep(2);
        $timer = $this->timers->stop('test');
        $this->assertGreaterThanOrEqual($timer['min'], $timer['max']);
    }

    /**
     * Stopping a timer without starting is like immediately starting then stopping it.
     */
    public function testStoppingTimerTwice(): void {
        $timer = @$this->timers->stop(__FUNCTION__);
        $this->assertSame(1, $timer['count']);
        $this->assertSame(0.0, $timer['time']);
    }

    /**
     * Test stopping all timers.
     */
    public function testStopAll(): void {
        $this->timers->start(__FUNCTION__);
        $this->timers->stopAll();
        $timers = $this->timers->jsonSerialize();
        foreach ($timers as $name => $timer) {
            $this->assertIsFloat($timer['stop']);
            $this->assertIsFloat($timer['min']);
            $this->assertIsFloat($timer['max']);
        }
    }

    /**
     * Test the lower bounds of the various duration types.
     *
     * @param string $expected
     * @param float $milliseconds
     * @dataProvider provideFormatTests
     */
    public function testFormatDurationMinimums(string $expected, float $milliseconds): void {
        $this->assertSame($expected, Timers::formatDuration($milliseconds));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function provideFormatTests(): array {
        $r = [
            ['0', 0],
            ['1μs', 1e-3],
            ['1ms', 1],
            ['1s', 1000],
            ['1m', 60000],
            ['1h', 1000 * strtotime('1 hour', 0)],
            ['1d', 1000 * strtotime('1 day', 0)],
            ['20ms', 19.572019577026367],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test a sample format string.
     */
    public function testLogFormatString(): void {
        $this->timers->stop('test');
        usleep(2);
        $this->timers->start('foo');
        $str = $this->timers->getLogFormatString();
        $this->assertSame('foo: {foo.human}, test: {test.human}', $str);
        $this->timers->stopAll();
    }
}

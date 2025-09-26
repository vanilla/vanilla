<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\Timers;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `Timers` class.
 */
class TimersTest extends SiteTestCase
{
    use DatabaseTestTrait;

    /**
     * @var Timers
     */
    private $timers;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->timers = new Timers();
        self::container()->setInstance(Timers::class, $this->timers);
        \Gdn::config()->saveToConfig([
            "trace.profiler" => true,
        ]);
    }

    /**
     * Test that timer spans can be nested inside of each other.
     */
    public function testTimerNesting()
    {
        $outerSpan = $this->timers->startGeneric("outer");
        usleep(100 * 1000);
        $innerSpan = $this->timers->startGeneric("inner");
        usleep(500 * 1000);
        $innerSpan->finish();
        $outerSpan->finish();

        $this->assertEquals($outerSpan->getUuid(), $innerSpan->getParentUuid());

        $timers = $this->timers->getAggregateTimers();
        $this->assertLessThanOrEqual(500, $timers["outer_elapsed_ms"]);

        $this->assertGreaterThanOrEqual(500, $timers["inner_elapsed_ms"]);
    }

    /**
     * Test recording of profiles.
     */
    public function testRecordProfile()
    {
        $root = $this->timers->startRootSpan();
        \Gdn::request()->fromImport($this->bessy()->createRequest("GET", "/my-url"));
        \Gdn::request()->setMeta("requestID", "my-request");
        $this->timers->setShouldRecordProfile(true);
        $this->timers->recordProfile();

        $this->assertRecordsFound("developerProfile", [
            "requestID" => "my-request",
            "requestMethod" => "GET",
            "requestPath" => "/my-url",
            "name" => "GET /my-url",
            "requestElapsedMs LIKE" => $root->getElapsedMs(),
        ]);
    }

    /**
     * Finishing a timer twice does nothing.
     */
    public function testFinishSpanTwice(): void
    {
        $span = $this->timers->startGeneric("test");
        usleep(300 * 1000);
        $span->finish();
        $elapsed = $span->getElapsedMs();
        usleep(300 * 1000);
        $span->finish();

        $this->assertSame($elapsed, $span->getElapsedMs());
        $this->assertErrorLogMessage("Timer test was finished more than once.");
    }

    /**
     * Test that timers can warn.
     */
    public function testTimerWarning()
    {
        $this->timers->setWarningLimit("slowTimer", 100);
        $span = $this->timers->startGeneric("slowTimer", [
            "start" => "foo",
        ]);
        usleep(300 * 1000);
        $span->finish([
            "end" => "bar",
        ]);
        $expectedMs = round($span->getElapsedMs());
        $this->assertErrorLogMessage("Timer slowTimer took {$expectedMs}ms.");
        $this->assertErrorLog([
            "tags" => ["timerWarning", "slowTimer"],
            "data.start" => "foo",
            "data.end" => "bar",
            "data.allowedMs" => 100,
        ]);
    }
}

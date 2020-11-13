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
class CurrentTimeStampTest extends TestCase {
    /**
     * @var \DateTimeImmutable
     */
    private $mockTime;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->mockTime = new \DateTimeImmutable('2020-08-04', new \DateTimeZone('UTC'));
        CurrentTimeStamp::mockTime($this->mockTime);
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void {
        CurrentTimeStamp::clearMockTime();
    }

    /**
     * Test basic mock time equivalence.
     */
    public function testCurrentTime() {
        $this->assertSame($this->mockTime->getTimestamp(), CurrentTimeStamp::get());
        $this->assertEquals($this->mockTime, CurrentTimeStamp::getDateTime());
        $this->assertSame($this->mockTime->format(MYSQL_DATE_FORMAT), CurrentTimeStamp::getMySQL());
    }
}

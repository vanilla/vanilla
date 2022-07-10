<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Vanilla\Logger;
use Vanilla\Logging\LogEntry;
use VanillaTests\ObjectTestTrait;
use VanillaTests\TestLogger;

/**
 * Tests for the `LogEntry` class.
 */
class LogEntryTest extends TestCase {
    use ObjectTestTrait;

    /**
     * @var LogEntry
     */
    private $object;

    /**
     * @var TestLogger
     */
    private $log;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();

        $this->object = new LogEntry(LogLevel::DEBUG, 'test', ['a' => 'b']);
        $this->log = new TestLogger();
    }

    /**
     * Test basic object access.
     */
    public function testAccessors(): void {
        $this->assertWith('Level', LogLevel::INFO);
        $this->assertWith('Message', 'foo');
        $this->assertWith('Context', [Logger::FIELD_EVENT => 'a', Logger::FIELD_CHANNEL => Logger::CHANNEL_MODERATION]);
        $this->assertWith('Event', 'a_b');
        $this->assertWith('Channel', Logger::CHANNEL_MODERATION);
    }

    /**
     * Test event factory method.
     */
    public function testCreateEvent(): void {
        $e = LogEntry::createEvent(
            LogLevel::EMERGENCY,
            'a_b',
            'foo',
            Logger::CHANNEL_MODERATION,
            ['a' => 'b']
        );

        $this->assertSame(LogLevel::EMERGENCY, $e->getLevel());
        $this->assertSame('a_b', $e->getEvent());
        $this->assertSame('foo', $e->getMessage());
        $this->assertSame(Logger::CHANNEL_MODERATION, $e->getChannel());
        $this->assertSame('b', $e->getContext()['a']);
    }

    /**
     * Test logging the entry.
     */
    public function testLog(): void {
        $this->object->log($this->log);

        $this->assertNotEmpty(
            $this->log->search([
                'level' => $this->object->getLevel(),
                'message' => $this->object->getMessage(),
                'event' => $this->object->getEvent(),
                'channel' => $this->object->getChannel(),
            ]),
            "The entry was not logged,"
        );
    }
}

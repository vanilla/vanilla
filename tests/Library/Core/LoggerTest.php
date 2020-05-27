<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use VanillaTests\BootstrapTrait;
use VanillaTests\TestLogger;

/**
 * Tests for the `Logger` class.
 */
class LoggerTest extends TestCase {
    use BootstrapTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        parent::setUp();
        $this->setupBoostrapTrait();
    }

    /**
     * Test adding and removing a logger.
     */
    public function testAddRemoveLogger(): void {
        $logger = new TestLogger();
        \Logger::addLogger($logger);
        \Logger::event('foo', LogLevel::INFO, 'bar');
        $this->assertNotNull($logger->search([
            \Logger::FIELD_EVENT => 'foo',
            'message' => 'bar',
            'level' => LogLevel::INFO,
        ]));

        $logger->clear();
        \Logger::removeLogger($logger);
        \Logger::event('foo', LogLevel::INFO, 'bar');
        $this->assertNull($logger->search([
            \Logger::FIELD_EVENT => 'foo',
            'message' => 'bar',
            'level' => LogLevel::INFO,
        ]));
    }

    /**
     * You shouldn't be able to add a vanilla logger to the logger.
     */
    public function testAddInvalidLogger(): void {
        $logger = new \Vanilla\Logger();
        $this->expectException(\InvalidArgumentException::class);
        \Logger::addLogger($logger);
    }

    /**
     * Test the basic logging methds.
     *
     * @param string $level
     * @dataProvider provideLogLevels
     */
    public function testBasicLoggingMethods(string $level): void {
        $method = [\Logger::class, $level];
        call_user_func($method, $level);
        $this->assertLog([
            'level' => $level,
            'message' => $level,
        ]);
    }

    /**
     * Test priority labels.
     *
     * @param string $level
     * @param int $priority
     * @dataProvider provideLogLevels
     */
    public function testPriorityLabels(string $level, int $priority): void {
        $this->assertSame($level, \Logger::priorityLabel($priority));
    }

    /**
     * An unknown priority should get a standard label.
     */
    public function testUnknownPriorityLabel(): void {
        $this->assertSame('unknown', \Logger::priorityLabel('foo'));
    }

    /**
     * Test priority levels.
     *
     * @param string $level
     * @param int $priority
     * @dataProvider provideLogLevels
     */
    public function testLevelPriority(string $level, int $priority): void {
        $this->assertSame($priority, \Logger::levelPriority($level));
    }

    /**
     * An empty level should be interpreted as debug level.
     */
    public function testEmptyLevelPriority(): void {
        $this->assertSame(LOG_DEBUG, \Logger::levelPriority(''));
    }

    /**
     * An unknown log level should be a notice.
     */
    public function testUnknownLevelPriority(): void {
        $this->expectNotice();
        \Logger::levelPriority('foo');
    }

    /**
     * Test get levels.
     */
    public function testGetLevels(): void {
        $test = $this->provideLogLevels();
        $levels = \Logger::getLevels();
        $this->assertSame(count($test), count($levels));
    }

    /**
     * Provide all of the log levels for testing.
     *
     * @return array
     */
    public function provideLogLevels(): array {
        return [
            LogLevel::DEBUG => [LogLevel::DEBUG, LOG_DEBUG],
            LogLevel::INFO => [LogLevel::INFO, LOG_INFO],
            LogLevel::NOTICE => [LogLevel::NOTICE, LOG_NOTICE],
            LogLevel::WARNING => [LogLevel::WARNING, LOG_WARNING],
            LogLevel::ERROR => [LogLevel::ERROR, LOG_ERR],
            LogLevel::CRITICAL => [LogLevel::CRITICAL, LOG_CRIT],
            LogLevel::ALERT => [LogLevel::ALERT, LOG_ALERT],
            LogLevel::EMERGENCY => [LogLevel::EMERGENCY, LOG_EMERG],
        ];
    }
}

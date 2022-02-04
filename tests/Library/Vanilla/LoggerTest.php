<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\LoggerInterfaceTest;
use Vanilla\Logger;
use VanillaTests\BootstrapTrait;

/**
 * Test of basic logging.
 */
class LoggerTest extends LoggerInterfaceTest {
    use BootstrapTrait;

    /**
     * @var TestLogger $logger;
     */
    private $logger;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        $this->logger = new TestLogger();
    }

    /**
     * @return void
     */
    public function testBasicLogging() {
        $logger = new TestLogger();

        $this->assertLogExists($logger, Logger::DEBUG, 'Hello world', ['foo']);
    }

    /**
     * @return void
     */
    public function testLowPriority() {
        $logger = new TestLogger(null, Logger::INFO);

        $this->assertNotLog($logger, Logger::DEBUG, 'Hello world', ['bar']);
    }

    /**
     * @return void
     */
    public function testTwoLoggers() {
        $logger1 = new TestLogger();
        $logger2 = new TestLogger($logger1->parent);
        $loggers = [$logger1, $logger2];

        foreach ($loggers as $logger) {
            $this->assertLogExists($logger, Logger::DEBUG, 'Hello world', ['foo']);
        }
    }

    /**
     * Should throw when an invalid level is given.
     */
    public function testThrowsOnInvalidLevel() {
        $this->expectException(InvalidArgumentException::class);
        parent::testThrowsOnInvalidLevel();
    }

    /**
     * Utility for asserting a log message exists.
     *
     * @param TestLogger $logger
     * @param int $level
     * @param string $message
     * @param array $context
     */
    protected function assertLogExists(TestLogger $logger, $level, $message, $context) {
        $logger->parent->log($level, $message, $context);
        [$lastLevel, $lastMessage, $lastContext] = $logger->last;
        $this->assertSame($level, $lastLevel);
        $this->assertSame($message, $lastMessage);

        $common = array_intersect_key($lastContext, $context);
        $this->assertSame($context, $common);
    }

    protected function assertNotLog(TestLogger $logger, $level, $message, $context) {
        $logger->parent->log($level, $message, $context);
        [$lastLevel, $lastMessage, $lastContext] = $logger->last;
        $this->assertNotSame($level, $lastLevel);
        $this->assertNotSame($message, $lastMessage);
        $this->assertNotSame($context, $lastContext);
    }

    /**
     * Get the logger that will be tested.
     *
     * @return LoggerInterface
     */
    public function getLogger() {
        return $this->logger->parent;
    }

    /**
     * This must return the log messages in order with a simple formatting: "<LOG LEVEL> <MESSAGE>"
     *
     * Example ->error('Foo') would yield "error Foo"
     *
     * @return string[]
     */
    public function getLogs() {
        return $this->logger->logs;
    }
}

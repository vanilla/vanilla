<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Psr\Log\Test\LoggerInterfaceTest;
use Vanilla\Logger;

class LoggerTest extends LoggerInterfaceTest {
    /**
     * @var TestLogger $logger;
     */
    private $logger;

    protected function setUp(): void {
        $this->logger = new TestLogger();
    }

    public function testBasicLogging() {
        $logger = new TestLogger();

        $this->assertLog($logger, Logger::DEBUG, 'Hello world', ['foo']);
    }

    public function testLowPriority() {
        $logger = new TestLogger(null, Logger::INFO);

        $this->assertNotLog($logger, Logger::DEBUG, 'Hello world', ['bar']);
    }

    public function testTwoLoggers() {
        $logger1 = new TestLogger();
        $logger2 = new TestLogger($logger1->parent);
        $loggers = [$logger1, $logger2];

        foreach ($loggers as $logger) {
            $this->assertLog($logger, Logger::DEBUG, 'Hello world', ['foo']);
        }
    }

    protected function assertLog(TestLogger $logger, $level, $message, $context) {
        $logger->parent->log($level, $message, $context);
        list($lastLevel, $lastMessage, $lastContext) = $logger->last;
        $this->assertSame($level, $lastLevel);
        $this->assertSame($message, $lastMessage);
        $this->assertSame($context, $lastContext);
    }

    protected function assertNotLog(TestLogger $logger, $level, $message, $context) {
        $logger->parent->log($level, $message, $context);
        list($lastLevel, $lastMessage, $lastContext) = $logger->last;
        $this->assertNotSame($level, $lastLevel);
        $this->assertNotSame($message, $lastMessage);
        $this->assertNotSame($context, $lastContext);
    }

    /**
     * @return \Psr\Log\Test\LoggerInterface
     */
    function getLogger() {
        return $this->logger->parent;
    }

    /**
     * This must return the log messages in order with a simple formatting: "<LOG LEVEL> <MESSAGE>"
     *
     * Example ->error('Foo') would yield "error Foo"
     *
     * @return string[]
     */
    function getLogs() {
        return $this->logger->logs;
    }
}

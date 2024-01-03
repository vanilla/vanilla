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
class LoggerTest extends LoggerInterfaceTest
{
    use BootstrapTrait;

    /**
     * @var TestLogger $logger;
     */
    private $logger;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

    /**
     * @return void
     */
    public function testBasicLogging()
    {
        $this->getTestLogger()->debug("Hello world");
        $this->assertLog([
            "level" => Logger::DEBUG,
            "message" => "Hello world",
        ]);
    }

    /**
     * @return void
     */
    public function testLowPriority()
    {
        $this->getTestLogger()->info("Hello world");
        $this->assertLog([
            "level" => Logger::DEBUG,
            "message" => "Hello world",
        ]);
    }

    /**
     * Test log interpolation.
     *
     * @return void
     */
    public function testInterpolateLogs()
    {
        $testLogger = $this->getTestLogger();
        $logger = new Logger();
        $logger->addLogger($testLogger);
        $logger->info("Hello {var}", ["var" => "world!"]);
        $this->assertLog([
            "level" => Logger::INFO,
            "message" => "Hello world!",
        ]);

        // It works with numbers too.
        $logger->info("Hello {var}", ["var" => 5]);
        $this->assertLog([
            "level" => Logger::INFO,
            "message" => "Hello 5",
        ]);

        // Doesn't blow up on non-string values.
        $this->getTestLogger()->info("Hello {var}", ["var" => ["arr"]]);
        $this->assertLog([
            "level" => Logger::INFO,
            "message" => "Hello {var}",
        ]);
    }

    /**
     * Should throw when an invalid level is given.
     */
    public function testThrowsOnInvalidLevel()
    {
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
    protected function assertLogExists(TestLogger $logger, $level, $message, $context)
    {
        $logger->parent->log($level, $message, $context);
        [$lastLevel, $lastMessage, $lastContext] = $logger->last;
        $this->assertSame($level, $lastLevel);
        $this->assertSame($message, $lastMessage);

        $common = array_intersect_key($lastContext, $context);
        $this->assertSame($context, $common);
    }

    protected function assertNotLog(TestLogger $logger, $level, $message, $context)
    {
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
    public function getLogger()
    {
        return $this->logger->parent;
    }

    /**
     * This must return the log messages in order with a simple formatting: "<LOG LEVEL> <MESSAGE>"
     *
     * Example ->error('Foo') would yield "error Foo"
     *
     * @return string[]
     */
    public function getLogs()
    {
        return $this->logger->logs;
    }
}

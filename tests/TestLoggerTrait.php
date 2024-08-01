<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Psr\Log\LoggerInterface;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Logging\LogDecorator;
use VanillaTests\Library\Vanilla\Logging\TestErrorLoggerCollection;

/**
 * Test trait for working with loggers.
 */
trait TestLoggerTrait
{
    /** @var string */
    protected static $errorLogFilePath;

    /**
     * @return void
     */
    protected function setUpLoggerTrait()
    {
        $logger = $this->getTestLogger();
        $logger->clear();
        static::$errorLogFilePath = TestErrorLoggerCollection::createLogFilePath();
        \Gdn::config()->saveToConfig(ErrorLogger::CONF_LOG_FILE, self::$errorLogFilePath, ["Save" => false]);
    }

    /**
     * @return TestLogger
     */
    protected static function getTestLogger(): TestLogger
    {
        $logger = static::container()->get(TestLogger::class);
        return $logger;
    }

    /**
     * @return LoggerInterface
     */
    protected static function getLogger(): LoggerInterface
    {
        $logger = static::container()->get(LoggerInterface::class);
        return $logger;
    }

    /**
     * @return TestErrorLoggerCollection
     */
    protected static function getTestErrorLogCollection(): TestErrorLoggerCollection
    {
        $collection = new TestErrorLoggerCollection(static::$errorLogFilePath);
        return $collection;
    }

    /**
     * Run a callback that will receive a log decorator and allow mutations.
     * Any changes will be reset afterwards.
     *
     * @param callable $callable
     */
    protected static function runWithLogDecorator(callable $callable)
    {
        $origDecorator = self::container()->get(LogDecorator::class);
        try {
            $clonedDecorator = clone $origDecorator;
            self::container()->setInstance(LogDecorator::class, $clonedDecorator);
            call_user_func($callable, $clonedDecorator);
        } finally {
            self::container()->setInstance(LogDecorator::class, $origDecorator);
        }
    }

    /**
     * Assert that something was logged.
     *
     * @param array $filter The log filter.
     * @param TestLogger|null $testLogger The logger to search through.
     *
     * @return array Return the first log entry found.
     */
    public function assertLog($filter = [], TestLogger $testLogger = null): array
    {
        $logger = $testLogger ?? $this->getTestLogger();
        $item = $logger->search($filter);
        if ($item === null) {
            $actual = "";
            $logs = $logger->getLog();
            foreach ($logs as $log) {
                $actual .= '\n';
                $actual .= json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            $this->fail(
                "Could not find expected log: " .
                    json_encode($filter, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) .
                    "\n" .
                    $actual
            );
        }
        $this->assertNotNull($item);
        return $item;
    }

    /**
     * Assert that some error was logged.
     *
     * @param array $filter The log filter.
     *
     * @return array Return the first log entry found.
     */
    public function assertErrorLog($filter = []): array
    {
        $collection = self::getTestErrorLogCollection();
        return $this->assertLog($filter, $collection->getTestLogger());
    }

    /**
     * Assert that the log has a message.
     *
     * @param string $message
     */
    public function assertErrorLogMessage(string $message)
    {
        $collection = self::getTestErrorLogCollection();
        $this->assertLogMessage($message, $collection->getTestLogger());
    }

    /**
     * Assert that something was NOT logged.
     *
     * @param array $filter The log filter.
     * @param TestLogger|null $testLogger The logger to search through.
     */
    public function assertNoLog($filter = [], TestLogger $testLogger = null)
    {
        $logger = $testLogger ?? $this->getTestLogger();
        $item = $logger->search($filter);
        $this->assertNull($item, "Unexpected log found: " . json_encode($filter));
    }

    /**
     * Assert that some error was NOT logged.
     *
     * @param array $filter The log filter.
     */
    public function assertNoErrorLog($filter = [])
    {
        $collection = self::getTestErrorLogCollection();
        $this->assertNoLog($filter, $collection->getTestLogger());
    }

    /**
     * Assert that the log has a message.
     *
     * @param string $message
     * @param TestLogger|null $testLogger
     */
    public function assertLogMessage(string $message, TestLogger $testLogger = null)
    {
        $logger = $testLogger ?? $this->getTestLogger();
        $this->assertTrue(
            $logger->hasMessage($message),
            "The log doesn't have the message: " .
                $message .
                "\n Found logs: \n" .
                json_encode($logger->getLog(), JSON_PRETTY_PRINT)
        );
    }
}

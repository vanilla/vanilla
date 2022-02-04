<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
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
trait TestLoggerTrait {

    /** @var string */
    protected static $errorLogFilePath;

    /**
     * @return void
     */
    protected function setUpLoggerTrait() {
        $logger = $this->getTestLogger();
        $logger->clear();
        static::$errorLogFilePath = TestErrorLoggerCollection::createLogFilePath();
        \Gdn::config()->saveToConfig(ErrorLogger::CONF_LOG_FILE, self::$errorLogFilePath, ['Save' => false]);
    }

    /**
     * @return TestLogger
     */
    protected static function getTestLogger(): TestLogger {
        $logger = static::container()->get(TestLogger::class);
        return $logger;
    }

    /**
     * @return LoggerInterface
     */
    protected static function getLogger(): LoggerInterface {
        $logger = static::container()->get(LoggerInterface::class);
        return $logger;
    }

    /**
     * @return TestErrorLoggerCollection
     */
    protected static function getTestErrorLogCollection(): TestErrorLoggerCollection {
        $collection = new TestErrorLoggerCollection(static::$errorLogFilePath);
        return $collection;
    }

    /**
     * @return LogDecorator
     */
    protected static function applyLogDecorator(): LogDecorator {
        self::container()->setInstance(LogDecorator::class, null);
        LogDecorator::applyAsLogger(self::container());
        return self::container()->get(LogDecorator::class);
    }

    /**
     * Run a callback with a log decorator. A specific request may be applied to the log decorator.
     *
     * @param callable $callable
     * @param \Gdn_Request|null $request
     */
    protected static function runWithLogDecorator(callable $callable, \Gdn_Request $request = null) {
        $existingLogger = self::container()->get(LoggerInterface::class);
        try {
            $logDecorator = self::applyLogDecorator();
            $logDecorator->setRequest($request);
            call_user_func($callable);
        } finally {
            self::container()->setInstance(LoggerInterface::class, $existingLogger);
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
    public function assertLog($filter = [], TestLogger $testLogger = null): array {
        $logger = $testLogger ?? $this->getTestLogger();
        $item = $logger->search($filter);
        if ($item === null) {
            $actual = '';
            $logs = $logger->getLog();
            foreach ($logs as $log) {
                $actual .= '\n';
                $actual .= json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            $this->fail("Could not find expected log: ".json_encode($filter, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" .
                $actual);
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
    public function assertErrorLog($filter = []): array {
        $collection = self::getTestErrorLogCollection();
        return $this->assertLog($filter, $collection->getTestLogger());
    }

    /**
     * Assert that something was NOT logged.
     *
     * @param array $filter The log filter.
     * @param TestLogger|null $testLogger The logger to search through.
     */
    public function assertNoLog($filter = [], TestLogger $testLogger = null) {
        $logger = $testLogger ?? $this->getTestLogger();
        $item = $logger->search($filter);
        $this->assertNull($item, "Unexpected log found: ".json_encode($filter));
    }

    /**
     * Assert that some error was NOT logged.
     *
     * @param array $filter The log filter.
     */
    public function assertNoErrorLog($filter = []) {
        $collection = self::getTestErrorLogCollection();
        $this->assertNoLog($filter, $collection->getTestLogger());
    }

    /**
     * Assert that the log has a message.
     *
     * @param string $message
     */
    public function assertLogMessage(string $message) {
        $logger = $this->getTestLogger();
        $this->assertTrue($logger->hasMessage($message), "The log doesn't have the message: ".$message);
    }
}

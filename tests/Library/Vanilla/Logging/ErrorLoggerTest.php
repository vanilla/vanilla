<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use Garden\Http\HttpClient;
use Garden\Web\Exception\ServerException;
use Psr\Log\LogLevel;
use Vanilla\Contracts\Site\Site;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Logging\LogDecorator;
use Vanilla\Site\OwnSite;
use VanillaTests\Fixtures\RecursiveSerializable;
use VanillaTests\Site\MockOwnSite;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the error logger.
 */
class ErrorLoggerTest extends SiteTestCase
{
    use UsersAndRolesApiTestTrait;

    /**
     * Test logging to a file.
     */
    public function testLogToFile()
    {
        ErrorLogger::error("an error", ["tag1"]);
        $this->assertErrorLog(["message" => "an error", "level" => "error", "tags" => ["tag1"]]);
    }

    /**
     * Test that the decorator context is applied.
     */
    public function testLogDecoratorContext()
    {
        $user = $this->createUser(["name" => "loguser"]);
        $mockOwnSite = self::container()->get(MockOwnSite::class);
        $mockOwnSite->applyFrom(new Site("site", "https://test.com", 100, 500, new HttpClient()));
        self::container()->setInstance(OwnSite::class, $mockOwnSite);
        $request = $this->bessy()
            ->createRequest("POST", "/path/some-request", [])
            ->setIP("1.1.4.4");

        $this->runWithUser(function () use ($request, $mockOwnSite) {
            $this->runWithLogDecorator(function (LogDecorator $decorator) use ($request, $mockOwnSite) {
                $decorator->setRequest($request);
                $decorator->setOwnSite($mockOwnSite);
                ErrorLogger::error("foo", ["tag1"], ["contextkey" => "contextvalue"]);
            });
        }, $user);

        $log = $this->assertErrorLog([
            "level" => "error",
            "message" => "foo",
            "request.method" => "POST",
            "request.protocol" => "https",
            "request.hostname" => "vanilla.test",
            "request.path" => "/path/some-request",
            "request.clientIP" => "1.1.4.4",
            "site.version" => APPLICATION_VERSION,
            "site.siteID" => "100",
            //            'site.accountID' => '500',
            //            'userID' => $user['userID'],
            //            'username' => 'loguser',
            //            'tags' => ['tag1'],
            //            'data.contextkey' => 'contextvalue',
        ]);
        $this->assertNotNull($log["stacktrace"] ?? null);
    }

    /**
     * Test that different error levels are logged.
     */
    public function testErrorLevels()
    {
        ErrorLogger::error("an error", []);
        $this->assertErrorLog(["level" => "error"]);
        ErrorLogger::warning("A warning", []);
        $this->assertErrorLog(["level" => "warning"]);

        ErrorLogger::notice("A notice", []);
        $this->assertNoErrorLog(["level" => "notice"]);

        // Now with the config settings.
        $this->runWithConfig([ErrorLogger::CONF_LOG_NOTICES => true], function () {
            ErrorLogger::notice("A notice", []);
            $this->assertErrorLog(["level" => "notice"]);
        });
    }

    /**
     * Test that we can log a throwable.
     */
    public function testFromThrowable()
    {
        $expection = new ServerException("wtf", 500, ["contextfield" => "contextvalue"]);
        ErrorLogger::error($expection, []);
        $log = $this->assertErrorLog([
            "message" => "wtf",
            "tags" => [ErrorLogger::TAG_THROWABLE, ServerException::class],
            "data.contextfield" => "contextvalue",
        ]);
        $this->assertNotNull($log["stacktrace"] ?? null);
    }

    /**
     * Test that we catch a json serialziation failure.
     */
    public function testJsonEncodeFailed()
    {
        ErrorLogger::error(
            "recursive json",
            ["tag1"],
            [
                "recursive" => new RecursiveSerializable(),
                Logger::FIELD_EVENT => "myevent_occured",
            ]
        );
        $log = $this->assertErrorLog([
            "message" => "recursive json",
            Logger::FIELD_EVENT => "myevent_occured",
            "tags" => ["myevent", "occured", "tag1", ErrorLogger::TAG_LOG_FAILURE_JSON],
        ]);
        $this->assertNull($log["data"]["recursive"] ?? null);
        $this->assertNotNull($log["site"]);
        $this->assertNotNull($log["request"]);
    }

    /**
     * Test catching a failure in our log decorator.
     */
    public function testDecoratorFailed()
    {
        try {
            $this->container()->setInstance(LogDecorator::class, "not the decorator");
            ErrorLogger::error(
                "bad decorator",
                ["tag1"],
                [
                    Logger::FIELD_EVENT => "myevent_occured",
                    "contextfield" => "contextvalue",
                ]
            );
            $log = $this->assertErrorLog([
                "message" => "bad decorator",
                Logger::FIELD_EVENT => "myevent_occured",
                "tags" => ["myevent", "occured", "tag1", LogDecorator::TAG_LOG_FAILURE_DECORATOR],
                "data.contextfield" => "contextvalue",
            ]);
            $this->assertNull($log["contextfield"] ?? null);
            $this->assertIsString($log["data"][LogDecorator::TAG_LOG_FAILURE_DECORATOR]["message"]);
            $this->assertIsString($log["data"][LogDecorator::TAG_LOG_FAILURE_DECORATOR]["stacktrace"]);
        } finally {
            $this->container()->setInstance(LogDecorator::class, null);
        }
    }

    /**
     * Test that we can't trigger logs while we're already logging.
     */
    public function testLogRecursion()
    {
        $nestedLogSerializable = new class implements \JsonSerializable {
            /**
             * @inheritdoc
             */
            public function jsonSerialize()
            {
                ErrorLogger::warning("level2", []);
                return "serialized";
            }
        };

        ErrorLogger::error("level1", [], ["nested" => $nestedLogSerializable]);
        $this->assertErrorLog(["message" => "level1"]);
        $this->assertNoErrorLog(["message" => "level2"]);
    }

    /**
     * Test our error handler.
     *
     * @param callable $errorFn Function to generate the error.
     * @param array $expectedLog The expected log filter.
     *
     * @dataProvider providePhpErrorHandler
     */
    public function testPHPErrorHandler(callable $errorFn, array $expectedLog)
    {
        \Gdn::config()->saveToConfig(ErrorLogger::CONF_LOG_NOTICES, true);
        $error = null;
        try {
            call_user_func($errorFn);
        } catch (\PHPUnit\Framework\Error\Error $err) {
            $error = $err;
        }
        $this->assertInstanceOf(\PHPUnit\Framework\Error\Error::class, $error);
        try {
            ErrorLogger::handleError($error->getCode(), $error->getMessage(), $error->getFile(), $error->getLine());
        } catch (\ErrorException $e) {
            ErrorLogger::handleException($e);
        }
        $this->assertErrorLog($expectedLog);
    }

    /**
     * @return iterable
     */
    public function providePhpErrorHandler(): iterable
    {
        yield "userNotice" => [
            $this->errorFn("my notice", E_USER_NOTICE),
            [
                "message" => "my notice",
                "level" => ErrorLogger::LEVEL_NOTICE,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_VANILLA,
            ],
        ];
        yield "notice" => [
            $this->errorFn("php notice", E_NOTICE),
            [
                "message" => "php notice",
                "level" => ErrorLogger::LEVEL_NOTICE,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_PHP,
            ],
        ];
        yield "userWarning" => [
            $this->errorFn("my warning", E_USER_WARNING),
            [
                "message" => "my warning",
                "level" => ErrorLogger::LEVEL_WARNING,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_VANILLA,
            ],
        ];
        yield "warning" => [
            $this->errorFn("php warning", E_WARNING),
            [
                "message" => "php warning",
                "level" => ErrorLogger::LEVEL_WARNING,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_PHP,
            ],
        ];
        yield "userDeprecated" => [
            $this->errorFn("my deprecated", E_USER_DEPRECATED),
            [
                "message" => "my deprecated",
                "level" => ErrorLogger::LEVEL_WARNING,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_VANILLA,
            ],
        ];
        yield "deprecated" => [
            $this->errorFn("php deprecated", E_DEPRECATED),
            [
                "message" => "php deprecated",
                "level" => ErrorLogger::LEVEL_WARNING,
                "tags" => [ErrorLogger::TAG_SOURCE_ERROR_HANDLER],
                "channel" => ErrorLogger::CHANNEL_PHP,
            ],
        ];
        yield "userError" => [
            $this->errorFn("user error", E_USER_ERROR),
            [
                "message" => "user error",
                "level" => ErrorLogger::LEVEL_ERROR,
                "tags" => [ErrorLogger::TAG_UNCAUGHT, ErrorLogger::TAG_SOURCE_EXCEPTION_HANDLER],
                "channel" => ErrorLogger::CHANNEL_VANILLA,
            ],
        ];
        yield "error" => [
            $this->errorFn("php error", E_ERROR),
            [
                "message" => "php error",
                "level" => ErrorLogger::LEVEL_ERROR,
                "tags" => [ErrorLogger::TAG_UNCAUGHT, ErrorLogger::TAG_SOURCE_EXCEPTION_HANDLER],
                "channel" => ErrorLogger::CHANNEL_PHP,
            ],
        ];
    }

    /**
     * Test that silenced/suppressed errors don't get logged.
     */
    public function testSuppressedErrorHandler()
    {
        try {
            // Will trigger an suppressed error.
            $doc = new \DOMDocument("1.0", "UTF-8");
            // Bad HTML.
            $doc->loadHTML("<asdfasdf");
        } catch (\PHPUnit\Framework\Error\Error $err) {
            $caught = $err;
        }
        $this->assertInstanceOf(\PHPUnit\Framework\Error\Error::class, $caught);

        // Suppress
        try {
            $oldErrorLevel = error_reporting();
            error_reporting(0);
            ErrorLogger::handleError($caught->getCode(), $caught->getMessage(), $caught->getFile(), $caught->getLine());
        } finally {
            error_reporting($oldErrorLevel);
        }

        $this->assertNoErrorLog();
    }

    /**
     * Generate function that generates a PHP error.
     *
     * @param string $message
     * @param int $level
     * @return callable
     */
    private function errorFn(string $message, int $level): callable
    {
        return function () use ($message, $level) {
            throw new \PHPUnit\Framework\Error\Error($message, $level, __FILE__, __LINE__);
        };
    }

    /**
     * Test that an error in the \Vanilla\Logger should log to the error logger as well.
     *
     * @param string $level
     * @param bool $shouldLogToErr
     *
     * @dataProvider provideShouldLogToError
     */
    public function testShouldLogToError(string $level, bool $shouldLogToErr)
    {
        $logger = $this->getLogger();
        $logger->log($level, $level, ["arglevel" => $level]);

        $search = [
            "level" => $level,
            "message" => $level,
            "data.arglevel" => $level,
        ];

        $this->assertLog($search);
        if ($shouldLogToErr) {
            $this->assertErrorLog($search);
        } else {
            $this->assertNoErrorLog($search);
        }
    }

    /**
     * @return iterable
     */
    public function provideShouldLogToError(): iterable
    {
        yield LogLevel::DEBUG => [LogLevel::DEBUG, false];
        yield LogLevel::INFO => [LogLevel::INFO, false];
        yield LogLevel::NOTICE => [LogLevel::NOTICE, false];
        yield LogLevel::WARNING => [LogLevel::WARNING, true];
        yield LogLevel::ERROR => [LogLevel::ERROR, true];
        yield LogLevel::ALERT => [LogLevel::ALERT, true];
        yield LogLevel::CRITICAL => [LogLevel::CRITICAL, true];
        yield LogLevel::EMERGENCY => [LogLevel::EMERGENCY, true];
    }
}

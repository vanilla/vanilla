<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\StaticCacheConfigTrait;
use Garden\Web\Exception\HttpException;
use Vanilla\Logger;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\StringUtils;
use function trace;

/**
 * Class for logging errors in vanilla.
 */
final class ErrorLogger
{
    /** @var bool */
    private static $inCall = false;

    use StaticCacheConfigTrait;

    public const CHANNEL_PHP = "channel-php";
    public const CHANNEL_VANILLA = "channel-vanilla";

    public const CONF_LOG_FILE = "errors.logFilePath";
    public const CONF_LOG_NOTICES = "errors.logNotices";

    public const TAG_THROWABLE = "throwable";
    public const TAG_UNCAUGHT = "uncaught";
    public const TAG_LOG_FAILURE_JSON = "logFailure-json";
    public const TAG_SOURCE_EXCEPTION_HANDLER = "source-exceptionHandler";
    public const TAG_SOURCE_ERROR_HANDLER = "source-errorHandler";

    public const LEVEL_NOTICE = "notice";
    public const LEVEL_WARNING = "warning";
    public const LEVEL_ERROR = "error";
    public const LEVEL_CRITICAL = "critical";

    private const ERROR_SUPPRESSED = 0;
    private const BITMASK_FATAL =
        E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
    private const BITMASK_NOTICE = E_NOTICE | E_USER_NOTICE;
    private const BITMASK_DEPRECATED = E_DEPRECATED | E_USER_DEPRECATED;
    private const BITMASK_WARNING = E_WARNING | E_USER_WARNING | E_STRICT | self::BITMASK_DEPRECATED;
    private const BITMASK_TYPE_VANILLA = E_USER_NOTICE | E_USER_DEPRECATED | E_USER_WARNING;
    private const BITMASK_TYPE_PHP =
        E_NOTICE | E_DEPRECATED | E_WARNING | E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;

    /**
     * Log a notice level of an error.
     *
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    public static function notice($message, array $tags, array $context = [])
    {
        $shouldLogNotices = DebugUtils::isDebug() || self::c(self::CONF_LOG_NOTICES, false);
        if (!$shouldLogNotices && !self::$inCall) {
            // We don't have noticing logging enabled.
            trace($message, TRACE_NOTICE);
            return;
        }
        self::log(self::LEVEL_NOTICE, $message, $tags, $context);
    }

    /**
     * Log a warning level error.
     *
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    public static function warning($message, array $tags, array $context = [])
    {
        self::log(self::LEVEL_WARNING, $message, $tags, $context);
    }

    /**
     * Log an error.
     *
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    public static function error($message, array $tags, array $context = [])
    {
        self::log(self::LEVEL_ERROR, $message, $tags, $context);
    }

    /**
     * Log a critical error.
     *
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    public static function critical($message, array $tags, array $context = [])
    {
        self::log(self::LEVEL_CRITICAL, $message, $tags, $context);
    }

    /**
     * Wrapper for @link{\Vanilla\Logging\ErrorLogger::logInternal()} to prevent nested log calls.
     *
     * @param string $level
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    public static function log(string $level, $message, array $tags, array $context = [])
    {
        // Prevent an infinite cycle by setting an internal flag.
        if (self::$inCall) {
            return;
        }
        self::$inCall = true;

        try {
            self::logInternal($level, $message, $tags, $context);
        } finally {
            self::$inCall = false;
        }
    }

    /**
     * Log an error of an arbitrary level.
     *
     * @param string $level
     * @param \Throwable|string $message The message or throwable to log. It is recommended NOT to interpolate variables into messages.
     * @param array $tags An array of tags for the error. You should have at least one. This is used for filtering and grouping errors.
     * @param array $context Context used to debug a logged error.
     */
    private static function logInternal(string $level, $message, array $tags, array $context = [])
    {
        // If we had a throwable pull out its message.
        /** @var \Throwable $throwable */
        $throwable = $context["exception"] ?? ($context["throwable"] ?? null);
        if ($message instanceof \Throwable) {
            $throwable = $message;
            $message = $message->getMessage();
        }

        // If it was an HTTP exception mix in the context.
        if ($throwable instanceof HttpException) {
            $context = array_replace_recursive($throwable->getContext(), $context);
        }

        $context[Logger::FIELD_TAGS] = $tags;
        if ($throwable !== null && !$throwable instanceof \ErrorException) {
            $context[Logger::FIELD_TAGS][] = self::TAG_THROWABLE;
            $context[Logger::FIELD_TAGS][] = get_class($throwable);
        }

        // Try to decorate the context.
        $context = LogDecorator::applyLogDecorator($context);

        $toLog = array_replace($context, [
            "message" => $message,
            "level" => $level,
            "stacktrace" => DebugUtils::stackTraceString(
                isset($throwable) ? $throwable->getTrace() : debug_backtrace()
            ),
        ]);

        if (empty($toLog[Logger::FIELD_CHANNEL])) {
            $toLog[Logger::FIELD_CHANNEL] = self::CHANNEL_VANILLA;
        }

        $jsonParams = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        try {
            $serialized = StringUtils::jsonEncodeChecked($toLog, $jsonParams);
        } catch (\Exception $e) {
            $toLog["tags"][] = self::TAG_LOG_FAILURE_JSON;

            // Drop the extra context. It likely caused the problem.
            $toLog["data"] = new \stdClass();
            $serialized = StringUtils::jsonEncodeChecked($toLog, $jsonParams);
        }

        // Prefix so that we know to interpret it as json.
        $serialized = '$json:' . $serialized;
        self::writeErrorLog($serialized);

        // In debug mode these may get collected in memory for display later.
        trace($throwable ?? $message, $level);
    }

    /**
     * Write an error message to the error log file.
     *
     * @param string $message The error message.
     */
    public static function writeErrorLog(string $message)
    {
        $errorLogFile = self::c(self::CONF_LOG_FILE);

        // Log only if the PHP setting "log_errors" is enabled
        // OR if the Garden config "Garden.Errors.LogFile" is provided
        if (!$errorLogFile && !ini_get("log_errors")) {
            return;
        }

        if (!$errorLogFile) {
            // sends to PHP's system logger
            @error_log($message, 0);
        } else {
            // Need to prepend the date when appending to an error log file
            // and also add a newline manually
            $date = date("d-M-Y H:i:s e");
            $message = sprintf("[%s] %s", $date, $message) . PHP_EOL;
            error_log($message, 3, $errorLogFile);
        }
    }

    /**
     * An error handler for php's `set_error_handler` function.
     *
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public static function handleError(int $severity, string $message, string $file = "", int $line = 0): void
    {
        $isErrorSuppressed = error_reporting() === self::ERROR_SUPPRESSED;
        if ($isErrorSuppressed) {
            return;
        }

        $errorException = new \ErrorException($message, $severity, $severity, $file, $line);

        // Fatal errors are thrown.
        if ($severity & self::BITMASK_FATAL) {
            throw $errorException;
        }

        // We're going to log the error.
        $context = [];
        $tags = [self::TAG_SOURCE_ERROR_HANDLER];
        if ($severity & self::BITMASK_TYPE_PHP) {
            $context[Logger::FIELD_CHANNEL] = self::CHANNEL_PHP;
        } else {
            $context[Logger::FIELD_CHANNEL] = self::CHANNEL_VANILLA;
        }

        // Notice level logging.
        if ($severity & self::BITMASK_NOTICE) {
            self::notice($errorException, $tags, $context);
            return;
        }

        // Warning level logging.
        if ($severity & self::BITMASK_WARNING) {
            self::warning($errorException, $tags, $context);
            return;
        }

        // Anything else is just a normal error.
        self::error($errorException, $tags, $context);
    }

    /**
     * A PHP exception handler for logging.
     *
     * @param \Throwable $exception
     */
    public static function handleException(\Throwable $exception)
    {
        $code = $exception->getCode();
        $channel = $code & self::BITMASK_TYPE_PHP ? ErrorLogger::CHANNEL_PHP : ErrorLogger::CHANNEL_VANILLA;
        self::error(
            $exception,
            [ErrorLogger::TAG_UNCAUGHT, ErrorLogger::TAG_SOURCE_EXCEPTION_HANDLER],
            [
                \Vanilla\Logger::FIELD_CHANNEL => $channel,
            ]
        );
    }
}

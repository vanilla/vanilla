<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * A logger that can contain many loggers.
 *
 * Class Logger
 * @package Vanilla
 */
class Logger implements LoggerInterface {
    use LoggerTrait;

    public const FIELD_EVENT = 'event';
    public const FIELD_LOG_TYPE = 'logType';

    public const TYPE_ADMIN = 'admin';
    public const TYPE_APPLICATION = 'application';
    public const TYPE_MODERATION = 'moderation';
    public const TYPE_SECURITY = 'security';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_DEFAULT = self::TYPE_APPLICATION;

    /** Log type. */
    public const EMERGENCY = LogLevel::EMERGENCY;

    /** Log type. */
    public const ALERT = LogLevel::ALERT;

    /** Log type. */
    public const CRITICAL = LogLevel::CRITICAL;

    /** Log type. */
    public const ERROR = LogLevel::ERROR;

    /** Log type. */
    public const WARNING = LogLevel::WARNING;

    /** Log type. */
    public const NOTICE = LogLevel::NOTICE;

    /** Log type. */
    public const INFO = LogLevel::INFO;

    /** Log type. */
    public const DEBUG = LogLevel::DEBUG;

    /**
     * @var array An array of loggers and levels.
     */
    private $loggers = [];

    /**
     * Get the numeric priority for a log level.
     *
     * The priorities are set to the LOG_* constants from the {@link syslog()} function.
     * A lower number is more severe.
     *
     * @param string|int $level The string log level or an actual priority.
     * @return int Returns the numeric log level or `8` if the level is invalid.
     */
    public static function levelPriority($level) {
        static $priorities = [
            self::DEBUG => LOG_DEBUG,
            self::INFO => LOG_INFO,
            self::NOTICE => LOG_NOTICE,
            self::WARNING => LOG_WARNING,
            self::ERROR => LOG_ERR,
            self::CRITICAL => LOG_CRIT,
            self::ALERT => LOG_ALERT,
            self::EMERGENCY => LOG_EMERG
        ];

        if (isset($priorities[$level])) {
            return $priorities[$level];
        } else {
            return LOG_DEBUG + 1;
        }
    }

    /**
     * Add a new logger to observe messages.
     *
     * @param LoggerInterface $logger The logger to add.
     * @param string $level One of the **LogLevel::*** constants.
     * @return Logger Returns $this for fluent calls.
     */
    public function addLogger(LoggerInterface $logger, $level = null) {
        // Make a small attempt to prevent infinite cycles by disallowing all logger chaining.
        if ($logger instanceof Logger) {
            throw new \InvalidArgumentException("You cannot add a Logger instance to a Logger.", 500);
        }

        $level = $level ?: self::DEBUG;
        $this->loggers[] = [$logger, static::levelPriority($level)];
        return $this;
    }

    /**
     * Remove a logger that was previously added with {@link Logger::addLogger()}.
     *
     * @param LoggerInterface $logger The logger to remove.
     * @param bool $trigger Whether or not to trigger a notice if the logger isn't found.
     * @return $this Returns $this for fluent calls.
     */
    public function removeLogger(LoggerInterface $logger, $trigger = true) {
        foreach ($this->loggers as $i => $addedLogger) {
            if ($addedLogger[0] === $logger) {
                unset($this->loggers[$i]);
                return $this;
            }
        }
        if ($trigger) {
            $class = get_class($logger);
            trigger_error("Logger $class was removed without being added.");
        }
        return $this;
    }

    /**
     * Log an event.
     *
     * @param string $event The event key. This is usually all lowercase, separated by underscores.
     * @param mixed $level One of the **Logger::*** constants.
     * @param string $message The message to log. Put fields in {braces} to replace with context values.
     * @param array $context The context to format the message with.
     */
    public function event($event, $level, $message, $context = []) {
        $context['event'] = $event;
        $this->log($level, $message, $context);
    }

    /**
     * Log with an arbitrary level.
     *
     * @param mixed $level One of the **Logger::*** constants.
     * @param string $message The message to log. Put fields in {braces} to replace with context values.
     * @param array $context The context to format the message with.
     */
    public function log($level, $message, array $context = []) {
        $levelPriority = self::levelPriority($level);
        if ($levelPriority > LOG_DEBUG) {
            throw new \Psr\Log\InvalidArgumentException("Invalid log level: $level.");
        }

        // Prevent an infinite cycle by setting an internal flag.
        static $inCall = false;
        if ($inCall) {
            return;
        }
        $inCall = true;

        foreach ($this->loggers as $row) {
            /* @var LoggerInterface $logger */
            list($logger, $loggerPriority) = $row;

            if ($loggerPriority >= $levelPriority) {
                try {
                    $logger->log($level, $message, $context);
                } catch (\Exception $ex) {
                    $inCall = false;
                    throw $ex;
                }
            }
        }

        $inCall = false;
    }
}

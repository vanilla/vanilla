<?php
/**
 * Logger.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.2
 */

use Psr\Log\LoggerInterface;

/**
 * Global event logging object.
 *
 * If nothing sets logger then Logger will be set to BaseLogger which is a dry loop.
 *
 * @see BaseLogger
 */
class Logger {

    /** Log type. */
    const EMERGENCY = 'emergency';

    /** Log type. */
    const ALERT = 'alert';

    /** Log type. */
    const CRITICAL = 'critical';

    /** Log type. */
    const ERROR = 'error';

    /** Log type. */
    const WARNING = 'warning';

    /** Log type. */
    const NOTICE = 'notice';

    /** Log type. */
    const INFO = 'info';

    /** Log type. */
    const DEBUG = 'debug';

    /** @var \Vanilla\Logger The interface responsible for doing the actual logging. */
    private static $instance;

    /** @var string The global level at which events are committed to the log. */
    private static $logLevel;

    /**
     * Add a new logger to observe messages.
     *
     * @param LoggerInterface $logger The logger to add.
     * @param string $level One of the **Logger::*** constants.
     */
    public static function addLogger(LoggerInterface $logger, $level = null) {
        static::getLogger()->addLogger($logger, $level);
    }

    /**
     * Remove a logger that was previously added with {@link Logger::addLogger()}.
     *
     * @param LoggerInterface $logger The logger to remove.
     * @param bool $trigger Whether or not to trigger a notice if the logger isn't found.
     */
    public static function removeLogger($logger, $trigger = true) {
        static::getLogger()->removeLogger($logger, $trigger);
    }

    /**
     * Set the logger.
     *
     * @param LoggerInterface $logger Specify a new value to set the logger to.
     */
    public static function setLogger($logger = null) {
        if ($logger instanceof \Vanilla\Logger) {
            self::$instance = $logger;
        } else {
            deprecated('Logger::setLogger()', 'Logger::addLogger');

            // Check for class compatibility while we update plugins.
            // TODO: Remove this check.
            if ($logger instanceof LoggerInterface) {
                static::addLogger($logger);
            }
        }
    }

    /**
     * Get the logger implementation.
     *
     * @return \Vanilla\Logger Returns a {@link \Vanilla\Logger}.
     */
    public static function getLogger() {
        if (!self::$instance) {
            self::$instance = new \Vanilla\Logger();
        }
        return self::$instance;
    }

    /**
     * Get the valid log levels.
     *
     * @return string[] Returns an array with level keys and label values.
     */
    public static function getLevels() {
        $r = [
            self::DEBUG => self::DEBUG,
            self::INFO => self::INFO,
            self::NOTICE => self::NOTICE,
            self::WARNING => self::WARNING,
            self::ERROR => self::ERROR,
            self::CRITICAL => self::CRITICAL,
            self::ALERT => self::ALERT,
            self::EMERGENCY => self::EMERGENCY

        ];

        $r = array_map('t', $r);

        return $r;
    }

    /**
     * Log a debug message.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function debug($message, $context = []) {
        static::log(Logger::DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function info($message, $context = []) {
        static::log(Logger::INFO, $message, $context);
    }

    /**
     * Log a notice.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function notice($message, $context = []) {
        static::log(Logger::NOTICE, $message, $context);
    }

    /**
     * Log a warning.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function warning($message, $context = []) {
        static::log(Logger::WARNING, $message, $context);
    }

    /**
     * Log an error.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function error($message, $context = []) {
        static::log(Logger::ERROR, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function critical($message, $context = []) {
        static::log(Logger::CRITICAL, $message, $context);
    }

    /**
     * Log an alert.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function alert($message, $context = []) {
        static::log(Logger::ALERT, $message, $context);
    }

    /**
     * Log an emergency.
     *
     * @param string $message The message to log.
     * @param string $context The message data.
     */
    public static function emergency($message, $context = []) {
        static::log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * Log an event.
     *
     * @param string $event The code of the event.
     * @param string $level One of the **Logger::*** constants.
     * @param string $message The message.
     * @param array $context The message data.
     */
    public static function event($event, $level, $message, $context = []) {
        $context['event'] = $event;
        static::log($level, $message, $context);
    }

    /**
     * Get the numeric priority for a log level.
     *
     * The priorities are set to the LOG_* constants from the {@link syslog()} function.
     * A lower number is more severe.
     *
     * @param string $level The string log level.
     * @return int Returns the numeric log level or `-1` if the level is invalid.
     */
    public static function levelPriority($level) {
        static $priorities = [
            Logger::DEBUG => LOG_DEBUG,
            Logger::INFO => LOG_INFO,
            Logger::NOTICE => LOG_NOTICE,
            Logger::WARNING => LOG_WARNING,
            Logger::ERROR => LOG_ERR,
            Logger::CRITICAL => LOG_CRIT,
            Logger::ALERT => LOG_ALERT,
            Logger::EMERGENCY => LOG_EMERG
        ];

        if (empty($level)) {
            return LOG_DEBUG;
        } elseif (isset($priorities[$level])) {
            return $priorities[$level];
        } else {
            error_log($level);
            self::log(Logger::NOTICE, "Unknown log level {unknownLevel}.", ['unknownLevel' => $level]);
            return LOG_DEBUG + 1;
        }
    }

    /**
     * Log the access of a resource.
     *
     * Since resources can be accessed with every page view this event will only log when the cache is enabled
     * and once every five minutes.
     *
     * @param string $event The name of the event to log.
     * @param string $level The log level of the event.
     * @param string $message The log message format.
     * @param array $context Additional information to pass to the event.
     */
    public static function logAccess($event, $level, $message, $context = []) {
        // Throttle the log access to 1 event every 5 minutes.
        if (Gdn::cache()->activeEnabled()) {
            $userID = Gdn::session()->UserID;
            $path = Gdn::request()->path();
            $key = "log:$event:$userID:$path";
            if (Gdn::cache()->get($key) === false) {
                self::event($event, $level, $message, $context);
                Gdn::cache()->store($key, time(), [Gdn_Cache::FEATURE_EXPIRY => 300]);
            }
        }
    }

    /**
     * Log a message.
     *
     * A message can contain fields that will be filled by the context. Fields are enclosed in curly braces like
     * `{this}`. Default fields are added to the context if they do not exist.
     *
     * @param string $level One of the **Logger::*** constants.
     * @param string $message The message format.
     * @param array $context The message data.
     */
    public static function log($level, $message, $context = []) {
        // Add default fields to the context if they don't exist.
        $defaults = [
            'userid' => Gdn::session()->UserID,
            'username' => val("Name", Gdn::session()->User, 'anonymous'),
            'ip' => Gdn::request()->ipAddress(),
            'timestamp' => time(),
            'method' => Gdn::request()->requestMethod(),
            'domain' => rtrim(url('/', true), '/'),
            'path' => Gdn::request()->path()
        ];
        $context = $context + $defaults;
        static::getLogger()->log($level, $message, $context);
    }

    /**
     * Return the string label for a numeric log priority.
     *
     * @param int $priority One of the LOG_* log levels.
     * @return string Returns one of the constants from this class or "unknown" if the priority isn't known.
     */
    public static function priorityLabel($priority) {
        switch ($priority) {
            case LOG_DEBUG:
                return self::DEBUG;
            case LOG_INFO:
                return self::INFO;
            case LOG_NOTICE:
                return self::NOTICE;
            case LOG_WARNING:
                return self::WARNING;
            case LOG_ERR:
                return self::ERROR;
            case LOG_CRIT:
                return self::CRITICAL;
            case LOG_ALERT:
                return self::ALERT;
            case LOG_EMERG:
                return self::EMERGENCY;
            default:
                return 'unknown';
        }
    }
}

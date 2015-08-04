<?php
/**
 * Logger.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.2
 */

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

    /** @var LoggerInterface The interface responsible for doing the actual logging. */
    protected static $instance;

    /** @var string The global level at which events are committed to the log. */
    protected static $logLevel;

    /**
     *
     *
     * @param LoggerInterface $value Specify a new value to set the logger to.
     */
    public static function setLogger(LoggerInterface $value = null) {
        if ($value !== null) {
            self::$instance = $value;
        } else {
            self::$instance = new BaseLogger();
        }
    }

    /**
     *
     *
     * @return LoggerInterface
     */
    public static function getLogger() {
        if (!self::$instance) {
            self::setLogger();
        }
        return self::$instance;
    }

    /**
     * Log an event
     *
     * @param string $event
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public static function event($event, $level, $message, $context = array()) {
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
        static $priorities = array(
            Logger::DEBUG => LOG_DEBUG,
            Logger::INFO => LOG_INFO,
            Logger::NOTICE => LOG_NOTICE,
            Logger::WARNING => LOG_WARNING,
            Logger::ERROR => LOG_ERR,
            Logger::CRITICAL => LOG_CRIT,
            Logger::ALERT => LOG_ALERT,
            Logger::EMERGENCY => LOG_EMERG
        );

        if (isset($priorities[$level])) {
            return $priorities[$level];
        } else {
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
    public static function logAccess($event, $level, $message, $context = array()) {
        // Throttle the log access to 1 event every 5 minutes.
        if (Gdn::cache()->activeEnabled()) {
            $userID = Gdn::session()->UserID;
            $path = Gdn::request()->path();
            $key = "log:$event:$userID:$path";
            if (Gdn::cache()->get($key) === false) {
                self::event($event, $level, $message, $context);
                Gdn::cache()->store($key, time(), array(Gdn_Cache::FEATURE_EXPIRY => 300));
            }
        }
    }

    /**
     * Gets or sets the current log level.
     *
     * @param string $value Pass a non-empty string to set a new log level.
     * @return string Returns the current logLevel.
     * @throws Exception Throws an exception of {@link $value} is an incorrect log level.
     */
    public static function logLevel($value = '') {
        if ($value !== '') {
            if (self::levelPriority($value) > LOG_DEBUG) {
                throw new Exception("Invalid log level $value.", 422);
            }
            self::$logLevel = $value;
        } elseif ($value === null) {
            self::$logLevel = Logger::NOTICE;
        }
        return self::$logLevel;
    }

    /**
     * Adds default fields to context if they do not exist
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public static function log($level, $message, $context = array()) {
        if (self::levelPriority($level) > self::levelPriority(self::logLevel())) {
            return;
        }

        // Add default fields to the context if they don't exist.
        $defaults = array(
            'userid' => Gdn::session()->UserID,
            'username' => val("Name", Gdn::session()->User, 'anonymous'),
            'ip' => Gdn::request()->ipAddress(),
            'timestamp' => time(),
            'method' => Gdn::request()->requestMethod(),
            'domain' => rtrim(Url('/', true), '/'),
            'path' => Gdn::request()->path()
        );
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

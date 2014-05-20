<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
/**
 * EventLogging
 *
 * If nothing sets logger; then Logger will be set to BaseLogger which is a dry loop
 *
 * @see DbLogger BaseLogger
 * @since 2.2
 */
class Logger {

    protected static $instance;

    /**
     * @param LoggerInterface $value
     */
    public static function setLogger(LoggerInterface $value = null) {
        if ($value !== null) {
            self::$instance = $value;
        } else {
            self::$instance = new BaseLogger();
        }
    }

    /**
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
        $context['Event'] = $event;
        static::log($level, $message, $context);
    }

    /**
     * Adds default fields to context if they do not exist
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public static function log($level, $message, $context = array()) {

        //Add default fields to the context if they don't exist.

        $defaults = array(
            'InsertUserID'=> Gdn::Session()->UserID,
            'InsertName'=> val("Name", Gdn::Session()->User, 'anonymous'),
            'InsertIPAddress'=> Gdn::Request()->IpAddress(),
            'TimeInserted'=> time(),
            'LogLevel' => $level,
            'Domain' => Url('/', true),
            'Path' => Url('', '/')
        );
        $context = $context + $defaults;
        static::getLogger()->log($level, $message, $context);
    }
}

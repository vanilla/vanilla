<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Global event logging object.
 *
 * If nothing sets logger then Logger will be set to BaseLogger which is a dry loop
 *
 * @see BaseLogger
 * @since 2.2
 */
class Logger {
   /**
    * @var LoggerInterface The interface responsible for doing the actual logging.
    */
   protected static $instance;

   /**
    * @var string The global level at which events are committed to the log.
    */
   protected static $logLevel;

   /**
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
    * Get the numeric priority for a log level.
    *
    * @param string $level The string log level.
    * @return int Returns the numeric log level or `-1` if the level is invalid.
    */
   public static function levelPriority($level) {
      static $priorities = array(
         self::DEBUG => 0,
         self::INFO => 1,
         self::NOTICE => 2,
         self::WARNING => 3,
         self::ERROR => 4,
         self::CRITICAL => 5,
         self::ALERT => 6,
         self::EMERGENCY => 7
      );

      if (isset($priorities[$level])) {
         return $priorities[$level];
      } else {
         return -1;
      }
   }

   /**
    * Gets or sets the current log level.
    *
    * @param string $value Pass a non-empty string to set a new log level.
    * @return string Returns the current logLevel.
    * @throws Exception Throws an exception of {@link $value} is an incorrect log level.
    */
   public function logLevel($value = '') {
      if ($value !== '') {
         if (self::levelPriority($value) < 0) {
            throw new Exception("Invalid log level $value.", 422);
         }
         self::$logLevel = $value;
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
      if (self::levelPriority($level) < self::levelPriority(self::logLevel())) {
         return;
      }

      //Add default fields to the context if they don't exist.
      $defaults = array(
         'InsertUserID' => Gdn::Session()->UserID,
         'InsertName' => val("Name", Gdn::Session()->User, 'anonymous'),
         'InsertIPAddress' => Gdn::Request()->IpAddress(),
         'TimeInserted' => time(),
         'LogLevel' => $level,
         'Domain' => Url('/', true),
         'Path' => Url('', '/')
      );
      $context = $context + $defaults;
      static::getLogger()->log($level, $message, $context);
   }
}

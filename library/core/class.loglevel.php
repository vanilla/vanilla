<?php
/**
 * LogLevel.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.2
 */

/**
 * Describes log levels. These are ordered from most severe to least severe.
 *
 * These constants have been moved to the Logger class and will be removed shortly.
 */
class LogLevel {

    /** @deprecated Use Logger::EMERGENCY instead. */
    const EMERGENCY = 'emergency';

    /** @deprecated Use Logger::ALERT instead. */
    const ALERT = 'alert';

    /** @deprecated Use Logger::CRITICAL instead. */
    const CRITICAL = 'critical';

    /** @deprecated Use Logger::ERROR instead. */
    const ERROR = 'error';

    /** @deprecated Use Logger::WARNING instead. */
    const WARNING = 'warning';

    /** @deprecated Use Logger::NOTICE instead. */
    const NOTICE = 'notice';

    /** @deprecated Use Logger::INFO instead. */
    const INFO = 'info';

    /** @deprecated Use Logger::DEBUG instead. */
    const DEBUG = 'debug';
}

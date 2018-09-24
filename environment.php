<?php
/**
 * Bare minimum setup of the environment to use Vanilla's classes.
 *
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

if (PHP_VERSION_ID < 70000) {
    die('Vanilla requires PHP 7.0 or greater.');
}

// Define the constants we need to get going.
if (!defined('APPLICATION')) {
    define('APPLICATION', 'Vanilla');
}
if (!defined('APPLICATION_VERSION')) {
    define('APPLICATION_VERSION', '2.6.3');
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', getcwd());
}

// Disable Phar stream
stream_wrapper_unregister('phar');

/**
 * Bootstrap Before
 *
 * This file gives developers the opportunity to hook into Garden before any
 * real work has been done. Nothing has been included yet, aside from this file.
 * No Garden features are available yet.
 */
$isWeb = PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_METHOD']);
if ($isWeb && file_exists(PATH_ROOT.'/conf/bootstrap.before.php')) {
    require_once PATH_ROOT.'/conf/bootstrap.before.php';
}

/**
 * Define Core Constants
 *
 * Garden depends on the presence of a certain base set of defines that allow it
 * to be aware of its own place within the system. These are conditionally
 * defined here, in case they've already been set by a zealous bootstrap.before.
 */

// Path to the primary configuration file.
if (!defined('PATH_CONF')) {
    define('PATH_CONF', PATH_ROOT.'/conf');
}

// Include default constants.
require_once PATH_CONF.'/constants.php';

// Make sure a default time zone is set.
// Do NOT edit this. See config `Garden.GuestTimeZone`.
date_default_timezone_set('UTC');

// Make sure the mb_* functions are utf8.
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Include the core autoloader.
if (!include_once __DIR__.'/vendor/autoload.php') {
    die("Could not find the autoloader. Did you forget to run 'composer install' in '".__DIR__."' ?\n");
}

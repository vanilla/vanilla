<?php
/**
 * Application Gateway.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

if (PHP_VERSION_ID < 50300) {
    die("Vanilla requires PHP 5.3 or greater.");
}

define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.2');

// Report and track all errors.
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 0);
ini_set('track_errors', 1);

ob_start();

// Define the constants we need to get going.

define('DS', '/');
define('PATH_ROOT', getcwd());

// Include the bootstrap to configure the framework.

require_once(PATH_ROOT.'/bootstrap.php');

// Create and configure the dispatcher.

$Dispatcher = Gdn::dispatcher();

$EnabledApplications = Gdn::ApplicationManager()->EnabledApplicationFolders();
$Dispatcher->EnabledApplicationFolders($EnabledApplications);
$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);

// Process the request.
$Dispatcher->start();
$Dispatcher->dispatch();

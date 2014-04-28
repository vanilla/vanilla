<?php

/**
 * Application Gateway
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @package Garden
 * @since 2.0
 */

define('APPLICATION', 'Vanilla');
define('APPLICATION_VERSION', '2.1');

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

$Dispatcher = Gdn::Dispatcher();

$EnabledApplications = Gdn::ApplicationManager()->EnabledApplicationFolders();
$Dispatcher->EnabledApplicationFolders($EnabledApplications);
$Dispatcher->PassProperty('EnabledApplications', $EnabledApplications);

// Process the request.

$Dispatcher->Start();
$Dispatcher->Dispatch();

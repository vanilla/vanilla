<?php
/**
 * Application Gateway.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

// Report and track all errors.
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
ini_set('display_errors', 0);
ini_set('track_errors', 1);

ob_start();

// Minimal environment needed to use most of Vanilla's framework.
require_once(__DIR__.'/environment.php');

// Require the bootstrap to configure the application.
require_once(__DIR__.'/bootstrap.php');

// Create and configure the dispatcher.
$dispatcher = Gdn::dispatcher();

// Process the request.
$dispatcher->start();
$dispatcher->dispatch();

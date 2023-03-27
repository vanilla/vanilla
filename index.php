<?php
/**
 * Application Gateway.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

// Report and track all errors.
error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("track_errors", 1);
set_time_limit(30);

ob_start();

// Minimal environment needed to use most of Vanilla's framework.
require_once __DIR__ . "/environment.php";

// Require the bootstrap to configure the application.
require_once __DIR__ . "/bootstrap.php";

// Create and configure the dispatcher.
$dispatcher = Gdn::dispatcher();

// Process the request.
$dispatcher->start();
$dispatcher->dispatch();

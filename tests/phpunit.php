<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use VanillaTests\NullContainer;

require_once __DIR__ . "/test-autoload.php";

// Use consistent timezone for all tests.
date_default_timezone_set("UTC");

ini_set("default_charset", "UTF-8");
error_reporting(E_ALL);
ini_set("log_errors", "0");

// Make sure we have clean directories.
\Vanilla\FileUtils::ensureCleanDirectory(PATH_CONF . "/vanilla.test");
\Vanilla\FileUtils::ensureCleanDirectory(PATH_CONF . "/e2e-tests.vanilla.localhost");
\Vanilla\FileUtils::ensureCleanDirectory(PATH_CACHE);
\Vanilla\FileUtils::ensureCleanDirectory(PATH_CACHE . "/bootstrap");
\Vanilla\FileUtils::ensureCleanDirectory(PATH_CACHE . "/vanilla.test");
\Vanilla\FileUtils::ensureCleanDirectory(PATH_UPLOADS);

// This effectively disable the auto instantiation of a new container when calling Gdn::getContainer();
Gdn::setContainer(new NullContainer());

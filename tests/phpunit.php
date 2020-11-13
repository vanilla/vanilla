<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use VanillaTests\NullContainer;

require_once __DIR__.'/definitions.php';

// Use consistent timezone for all tests.
date_default_timezone_set("UTC");

ini_set("default_charset", "UTF-8");
error_reporting(E_ALL);
ini_set('log_errors', '0');

// Alias classes for some limited PHPUnit v5 compatibility with v6.
$classCompatibility = [
    'PHPUnit\\Framework\\TestCase' => 'PHPUnit_Framework_TestCase', // See https://github.com/php-fig/log/pull/52
];
foreach ($classCompatibility as $class => $legacyClass) {
    if (!class_exists($legacyClass) && class_exists($class)) {
        class_alias($class, $legacyClass);
    }
}

// Copy the cgi-bin files.
$dir = PATH_ROOT.'/cgi-bin';
if (!file_exists($dir)) {
    mkdir($dir);
}

$files = glob(PATH_ROOT."/.circleci/scripts/templates/vanilla/cgi-bin/*.php");
foreach ($files as $file) {
    $dest = $dir.'/'.basename($file);
    $r = copy($file, $dest);
    if (!$r) {
        throw new \Exception("Could not copy $dest.", 500);
    }
}

// ===========================================================================
// Adding the minimum dependencies to support unit testing for core libraries
// ===========================================================================
require PATH_ROOT.'/environment.php';

// Allow any addon class to be auto-loaded.
\VanillaTests\Bootstrap::registerAutoloader();


// Allow a test before.
$bootstrapTestFile = PATH_CONF . '/bootstrap.tests.php';
if (file_exists($bootstrapTestFile)) {
    require_once $bootstrapTestFile;
}

// This effectively disable the auto instantiation of a new container when calling Gdn::getContainer();
Gdn::setContainer(new NullContainer());

// Clear the test cache.
\Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache');

// Ensure our uploads directory exists.
mkdir(PATH_ROOT.'/tests/cache', 0777);
mkdir(PATH_UPLOADS, 0777);

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

require_once PATH_LIBRARY_CORE.'/functions.render.php';

// Include test utilities.
$utilityFiles = array_merge(
    glob(PATH_ROOT.'/plugins/*/tests/Utils/*.php'),
    glob(PATH_ROOT.'/applications/*/tests/Utils/*.php')
);
foreach ($utilityFiles as $file) {
    require_once $file;
}

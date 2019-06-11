<?php

use VanillaTests\NullContainer;
error_reporting(E_ALL);
// Alias classes for some limited PHPUnit v5 compatibility with v6.
$classCompatibility = [
    'PHPUnit\\Framework\\TestCase' => 'PHPUnit_Framework_TestCase', // See https://github.com/php-fig/log/pull/52
];
foreach ($classCompatibility as $class => $legacyClass) {
    if (!class_exists($legacyClass) && class_exists($class)) {
        class_alias($class, $legacyClass);
    }
}

// Define some constants to help with testing.
define('APPLICATION', 'Vanilla Tests');
define('PATH_ROOT', realpath(__DIR__.'/..'));

// Copy the cgi-bin files.
$dir = PATH_ROOT.'/cgi-bin';
if (!file_exists($dir)) {
    mkdir($dir);
}

$files = glob(__DIR__."/travis/templates/vanilla/cgi-bin/*.php");
foreach ($files as $file) {
    $dest = $dir.'/'.basename($file);
    $r = copy($file, $dest);
}

// ===========================================================================
// Adding the minimum dependencies to support unit testing for core libraries
// ===========================================================================
require PATH_ROOT.'/environment.php';

// This effectively disable the auto instanciation of a new container when calling Gdn::getContainer();
Gdn::setContainer(new NullContainer());

// Clear the test cache.
\Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

require_once PATH_LIBRARY_CORE.'/functions.render.php';

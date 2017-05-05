<?php

use Garden\Container\Container;
use Vanilla\Addon;
use Vanilla\InjectableInterface;

// Define some constants to help with testing.
define('APPLICATION', 'Vanilla Tests');
define('PATH_ROOT', realpath(__DIR__.'/..'));
define('DS', '/');

// Autoload all of the classes.
require PATH_ROOT.'/vendor/autoload.php';

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

// Path to the primary configuration file.
if (!defined('PATH_CONF')) {
    define('PATH_CONF', PATH_ROOT.'/conf');
}

if (!defined('APPLICATION_VERSION')) {
    define('APPLICATION_VERSION', '2.2.101.7');
}

// Loads the constants
require PATH_CONF . '/constants.php';

// Set up the dependency injection container.
$bootstrap = new \VanillaTests\Bootstrap();
$bootstrap->run(new Container());

// Clear the test cache.
\Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

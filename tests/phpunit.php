<?php

// Define some constants to help with testing.
define('APPLICATION', 'Vanilla Tests');
define('PATH_ROOT', realpath(__DIR__.'/..'));

// Autoload all of the classes.
require PATH_ROOT.'/vendor/autoload.php';

// Copy the cgi-bin files.
$dir = PATH_ROOT.'/cgi-bin';
if (!file_exists($dir)) {
    mkdir($dir);
}

$files = glob(__DIR__."/travis/cgi-bin/*.php");
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

// Install the configuration handler.
Gdn::factoryInstall(Gdn::AliasConfig, 'Gdn_Configuration');

// ThemeManager
Gdn::factoryInstall(Gdn::AliasThemeManager, 'Gdn_ThemeManager');

// Session
Gdn::factoryInstall(Gdn::AliasSession, 'Gdn_Session');

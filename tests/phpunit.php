<?php

use Vanilla\Addon;

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

// Set up the dependency injection container.
$dic = new \Garden\Container\Container();
Gdn::setContainer($dic);

$dic->setInstance('Garden\Container\Container', $dic)
    ->rule('Interop\Container\ContainerInterface')
    ->setAliasOf('Garden\Container\Container')

    ->rule('Gdn_Request')
    ->setShared(true)
    ->addAlias('Request')

    ->rule('Gdn_DatabaseStructure')
    ->setClass('Gdn_MySQLStructure')
    ->setShared(true)
    ->addAlias(Gdn::AliasDatabaseStructure)
    ->addAlias('MySQLStructure')

    ->rule('Gdn_SQLDriver')
    ->setClass('Gdn_MySQLDriver')
    ->setShared(true)
    ->addAlias('Gdn_MySQLDriver')
    ->addAlias('MySQLDriver')
    ->addAlias(Gdn::AliasSqlDriver);
;

// Install the configuration handler.
Gdn::factoryInstall(Gdn::AliasConfig, 'Gdn_Configuration');

// AddonManager
Gdn::factoryInstall(
    Gdn::AliasAddonManager,
    '\\Vanilla\\AddonManager',
    '',
    Gdn::FactorySingleton,
    [
        [
            Addon::TYPE_ADDON => ['/applications', '/plugins'],
            Addon::TYPE_THEME => '/themes',
            Addon::TYPE_LOCALE => '/locales'
        ],
        __DIR__.'/cache'
    ]
);
// This is for satisfying dependencies.
Gdn::factoryInstall('\\Vanilla\\AddonManager', '\\Vanilla\\AddonManager', '', Gdn::FactorySingleton, Gdn::addonManager());

// Install a bogus locale because the "Locale" alias of Gdn clashes with a built in Locale object.
Gdn::factoryInstall(Gdn::AliasLocale, '\VanillaTests\Fixtures\Locale');

// ThemeManager
Gdn::factoryInstall(Gdn::AliasThemeManager, 'Gdn_ThemeManager');

// Session
Gdn::factoryInstall(Gdn::AliasSession, 'Gdn_Session');

// Clear the test cache.
\Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

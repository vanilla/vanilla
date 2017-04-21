<?php

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
$dic = $GLOBALS['dic'] = new \Garden\Container\Container();
Gdn::setContainer($dic);

$dic->setInstance('Garden\Container\Container', $dic)
    ->rule('Interop\Container\ContainerInterface')
    ->setAliasOf('Garden\Container\Container')

    ->rule(InjectableInterface::class)
    ->addCall('setDependencies')

    // Cache
    ->rule('Gdn_Cache')
    ->setShared(true)
    ->setFactory(['Gdn_Cache', 'initialize'])
    ->addAlias('Cache')

    // Configuration
    ->rule('Gdn_Configuration')
    ->setShared(true)
    ->addCall('defaultPath', [PATH_ROOT.'/conf/vanilla.test'])
    ->addCall('load', [PATH_ROOT.'/conf/config-defaults.php'])
    ->addAlias('Config')

    // AddonManager
    ->rule(Vanilla\AddonManager::class)
    ->setShared(true)
    ->setConstructorArgs([
        [
            Addon::TYPE_ADDON => ['/applications', '/plugins'],
            Addon::TYPE_THEME => '/themes',
            Addon::TYPE_LOCALE => '/locales'
        ],
        PATH_CACHE
    ])
    ->addAlias('AddonManager')
    ->addCall('registerAutoloader')

    // ApplicationManager
    ->rule('Gdn_ApplicationManager')
    ->setShared(true)
    ->addAlias('ApplicationManager')

    // PluginManager
    ->rule('Gdn_PluginManager')
    ->setShared(true)
    ->addAlias('PluginManager')

    // ThemeManager
    ->rule('Gdn_ThemeManager')
    ->setShared(true)
    ->addAlias('ThemeManager')

    // Logger
    ->rule(\Vanilla\Logger::class)
    ->setShared(true)
    ->addAlias(\Psr\Log\LoggerInterface::class)

    ->rule(\Psr\Log\LoggerAwareInterface::class)
    ->addCall('setLogger')

    // EventManager
    ->rule(\Garden\EventManager::class)
    ->setShared(true)

    ->rule(InjectableInterface::class)
    ->addCall('setDependencies')

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
    ->addAlias(Gdn::AliasSqlDriver)

    ->rule(Gdn::AliasLocale)
    ->setClass(\VanillaTests\Fixtures\Locale::class)

    ->rule('Gdn_Session')
    ->setShared(true)
    ->addAlias('Session')

    ->rule(Gdn::AliasAuthenticator)
    ->setClass('Gdn_Auth')
    ->setShared(true)

    ->rule('Gdn_Router')
    ->addAlias(Gdn::AliasRouter)
    ->setShared(true)

    ->rule('Gdn_Dispatcher')
    ->setShared(true)
    ->addAlias(Gdn::AliasDispatcher)

    ->rule(\Garden\Web\Dispatcher::class)
    ->setShared(true)
    ->addCall('addRoute', ['route' => new \Garden\Container\Reference('@api-v2-route'), 'api-v2'])

    ->rule('@api-v2-route')
    ->setClass(\Garden\Web\ResourceRoute::class)
    ->setConstructorArgs(['/api/v2/', '%sApiController'])

    ->rule(\Garden\ClassLocator::class)
    ->setClass(\Vanilla\VanillaClassLocator::class)

    ->rule(Gdn_Plugin::class)
    ->setShared(true)
    ->addCall('setAddonFromManager')
;

// Clear the test cache.
\Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

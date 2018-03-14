<?php

use Garden\Container\Container;
use Garden\Container\Reference;
use Vanilla\Addon;
use Vanilla\InjectableInterface;

if (!defined('APPLICATION')) exit();
/**
 * Bootstrap.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

// Guard against broken cache files.
if (!class_exists('Gdn')) {
    // Throwing an exception here would result in a white screen for the user.
    // This error usually indicates the .ini files in /cache are out of date and should be deleted.
    exit("Class Gdn not found.");
}

// Set up the dependency injection container.
$dic = new Container();
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

    ->rule(Garden\Web\Cookie::class)
    ->setShared(true)
    ->addAlias('Cookie')

    // PluginManager
    ->rule('Gdn_PluginManager')
    ->setShared(true)
    ->addAlias('PluginManager')

    ->rule(SsoUtils::class)
    ->setShared(true)

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

    // Locale
    ->rule('Gdn_Locale')
    ->setShared(true)
    ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Garden.Locale'])])
    ->addAlias('Locale')

    // Request
    ->rule('Gdn_Request')
    ->setShared(true)
    ->addCall('fromEnvironment')
    ->addAlias('Request')
    ->addAlias(\Garden\Web\RequestInterface::class)

    // Database.
    ->rule('Gdn_Database')
    ->setShared(true)
    ->setConstructorArgs([new Reference(['Gdn_Configuration', 'Database'])])
    ->addAlias('Database')

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

    ->rule('Identity')
    ->setClass('Gdn_CookieIdentity')
    ->setShared(true)

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
    ->addCall('addRoute', ['route' => new Reference('@api-v2-route'), 'api-v2'])
    ->addCall('addRoute', ['route' => new \Garden\Container\Callback(function () {
        return new \Garden\Web\PreflightRoute('/api/v2', true);
    })])
    ->addCall('setAllowedOrigins', ['isTrustedDomain'])

    ->rule('@api-v2-route')
    ->setClass(\Garden\Web\ResourceRoute::class)
    ->setConstructorArgs(['/api/v2/', '*\\%sApiController'])
    ->addCall('setMeta', ['CONTENT_TYPE', 'application/json; charset=utf-8'])

    ->rule('@view-application/json')
    ->setClass(\Vanilla\Web\JsonView::class)
    ->setShared(true)

    ->rule(\Garden\ClassLocator::class)
    ->setClass(\Vanilla\VanillaClassLocator::class)

    ->rule('Gdn_Model')
    ->setShared(true)

    ->rule('Gdn_IPlugin')
    ->setShared(true)

    ->rule(Gdn_Plugin::class)
    ->setShared(true)
    ->addCall('setAddonFromManager')

    ->rule('Gdn_Slice')
    ->setShared(true)
    ->addAlias('Slice')

    ->rule('Gdn_Statistics')
    ->addAlias('Statistics')
    ->setShared(true)

    ->rule('Gdn_Regarding')
    ->setShared(true)

    ->rule('BBCodeFormatter')
    ->setClass('BBCode')
    ->setShared(true)

    ->rule('HtmlFormatter')
    ->setClass(VanillaHtmlFormatter::class)
    ->addAlias(VanillaHtmlFormatter::class)
    ->setShared(true)

    ->rule('Smarty')
    ->setShared(true)

    ->rule('WebLinking')
    ->setClass(\Vanilla\Web\WebLinking::class)
    ->setShared(true)

    ->rule('ViewHandler.tpl')
    ->setClass('Gdn_Smarty')
    ->setShared(true)

    ->rule('Gdn_Form')
    ->addAlias('Form')
;

// Run through the bootstrap with dependencies.
$dic->call(function (
    Container $dic,
    Gdn_Configuration $config,
    \Vanilla\AddonManager $addonManager,
    \Garden\EventManager $eventManager,
    Gdn_Request $request // remove later
) {

    // Load default baseline Garden configurations.
    $config->load(PATH_CONF.'/config-defaults.php');

    // Load installation-specific configuration so that we know what apps are enabled.
    $config->load($config->defaultPath(), 'Configuration', true);

    /**
     * Bootstrap Early
     *
     * A lot of the framework is loaded now, most importantly the core autoloader,
     * default config and the general and error functions. More control is possible
     * here, but some things have already been loaded and are immutable.
     */
    if (file_exists(PATH_CONF.'/bootstrap.early.php')) {
        require_once PATH_CONF.'/bootstrap.early.php';
    }

    $config->caching(true);
    debug($config->get('Debug', false));

    setHandlers();

    /**
     * Installer Redirect
     *
     * If Garden is not yet installed, force the request to /dashboard/setup and
     * begin installation.
     */
    if ($config->get('Garden.Installed', false) === false && strpos($request->path(), 'setup') === false) {
        safeHeader('Location: '.$request->url('dashboard/setup', true));
        exit();
    }


    /**
     * Extension Managers
     *
     * Now load the Addon, Application, Theme and Plugin managers into the Factory, and
     * process the application-specific configuration defaults.
     */

    // Start the addons, plugins, and applications.
    $addonManager->startAddonsByKey(c('EnabledPlugins'), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey(c('EnabledApplications'), Addon::TYPE_ADDON);
    $addonManager->startAddonsByKey(array_keys(c('EnabledLocales', [])), Addon::TYPE_LOCALE);

    $currentTheme = c('Garden.Theme', Gdn_ThemeManager::DEFAULT_DESKTOP_THEME);
    if (isMobile()) {
        $currentTheme = c('Garden.MobileTheme', Gdn_ThemeManager::DEFAULT_MOBILE_THEME);
    }
    $addonManager->startAddonsByKey([$currentTheme], Addon::TYPE_THEME);

    // Load the configurations for enabled addons.
    foreach ($addonManager->getEnabled() as $addon) {
        /* @var Addon $addon */
        if ($configPath = $addon->getSpecial('config')) {
            $config->load($addon->path($configPath));
        }
    }

    // Re-apply loaded user settings.
    $config->overlayDynamic();

    /**
     * Bootstrap Late
     *
     * All configurations are loaded, as well as the Application, Plugin and Theme
     * managers.
     */
    if (file_exists(PATH_CONF.'/bootstrap.late.php')) {
        require_once PATH_CONF.'/bootstrap.late.php';
    }

    if ($config->get('Debug')) {
        debug(true);
    }

    Gdn_Cache::trace(debug()); // remove later

    /**
     * Extension Startup
     *
     * Allow installed addons to execute startup and bootstrap procedures that they may have, here.
     */

    // Bootstrapping.
    foreach ($addonManager->getEnabled() as $addon) {
        /* @var Addon $addon */
        if ($bootstrapPath = $addon->getSpecial('bootstrap')) {
            $bootstrapPath = $addon->path($bootstrapPath);
            include_once $bootstrapPath;
        }
    }

    // Plugins startup
    $addonManager->bindAllEvents($eventManager);

    if ($eventManager->hasHandler('gdn_pluginManager_afterStart')) {
        $eventManager->fire('gdn_pluginManager_afterStart', $dic->get(Gdn_PluginManager::class));
    }

    // Now that all of the events have been bound, fire an event that allows plugins to modify the container.
    $eventManager->fire('container_init', $dic);
});

// Send out cookie headers.
register_shutdown_function(function() use ($dic) {
    $dic->call(function(Garden\Web\Cookie $cookie) {
        $cookie->flush();
    });
});

/**
 * Locales
 *
 * Install any custom locales provided by applications and plugins, and set up
 * the locale management system.
 */

// Load the Garden locale system.
$dic->get('Gdn_Locale');

require_once PATH_LIBRARY_CORE.'/functions.validation.php';

// Start Authenticators
$dic->get('Authenticator')->startAuthenticator();

/**
 * Bootstrap After
 *
 * After the bootstrap has finished loading, this hook allows developers a last
 * chance to customize Garden's runtime environment before the actual request
 * is handled.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.after.php')) {
    require_once PATH_ROOT.'/conf/bootstrap.after.php';
}

// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once PATH_LIBRARY_CORE.'/functions.render.php';

if (!defined('CLIENT_NAME')) {
    define('CLIENT_NAME', 'vanilla');
}

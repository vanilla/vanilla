<?php if (!defined('APPLICATION')) exit();
/**
 * Bootstrap.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Bootstrap Before
 *
 * This file gives developers the opportunity to hook into Garden before any
 * real work has been done. Nothing has been included yet, aside from this file.
 * No Garden features are available yet.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.before.php')) {
    require_once(PATH_ROOT.'/conf/bootstrap.before.php');
}

/**
 * Define Core Constants
 *
 * Garden depends on the presence of a certain base set of defines that allow it
 * to be aware of its own place within the system. These are conditionally
 * defined here, in case they've already been set by a zealous bootstrap.before.
 */

// Path to the primary configuration file.
if (!defined('PATH_CONF')) {
    define('PATH_CONF', PATH_ROOT.'/conf');
}

// Include default constants if none were defined elsewhere.
if (!defined('VANILLA_CONSTANTS')) {
    include(PATH_CONF.'/constants.php');
}

// Make sure a default time zone is set.
// Do NOT edit this. See config `Garden.GuestTimeZone`.
date_default_timezone_set('UTC');

// Make sure the mb_* functions are utf8.
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Include the core autoloader.
require_once __DIR__.'/vendor/autoload.php';

// Initialize the autoloader.
Gdn_Autoloader::start();

// Guard against broken cache files.
if (!class_exists('Gdn')) {
    // Throwing an exception here would result in a white screen for the user.
    // This error usually indicates the .ini files in /cache are out of date and should be deleted.
    exit("Class Gdn not found.");
}

// Cache Layer
Gdn::factoryInstall(Gdn::AliasCache, 'Gdn_Cache', null, Gdn::FactoryRealSingleton, 'Initialize');

// Install the configuration handler.
Gdn::factoryInstall(Gdn::AliasConfig, 'Gdn_Configuration');

// Load default baseline Garden configurations.
Gdn::config()->load(PATH_CONF.'/config-defaults.php');

// Load installation-specific configuration so that we know what apps are enabled.
Gdn::config()->load(Gdn::config()->defaultPath(), 'Configuration', true);

/**
 * Bootstrap Early
 *
 * A lot of the framework is loaded now, most importantly the autoloader,
 * default config and the general and error functions. More control is possible
 * here, but some things have already been loaded and are immutable.
 */
if (file_exists(PATH_CONF.'/bootstrap.early.php')) {
    require_once(PATH_CONF.'/bootstrap.early.php');
}

Gdn::config()->caching(true);

debug(c('Debug', false));

// Default request object
Gdn::factoryInstall(Gdn::AliasRequest, 'Gdn_Request', null, Gdn::FactoryRealSingleton, 'Create');
Gdn::request()->fromEnvironment();

setHandlers();

/**
 * Extension Managers
 *
 * Now load the Application, Theme and Plugin managers into the Factory, and
 * process the Application-specific configuration defaults.
 */

// ApplicationManager
Gdn::factoryInstall(Gdn::AliasApplicationManager, 'Gdn_ApplicationManager');
Gdn_Autoloader::attach(Gdn_Autoloader::CONTEXT_APPLICATION);

// ThemeManager
Gdn::factoryInstall(Gdn::AliasThemeManager, 'Gdn_ThemeManager');

// PluginManager
Gdn::factoryInstall(Gdn::AliasPluginManager, 'Gdn_PluginManager');

// Load the configurations for enabled Applications.
foreach (Gdn::applicationManager()->enabledApplicationFolders() as $applicationName => $applicationFolder) {
    Gdn::config()->load(PATH_APPLICATIONS."/{$applicationFolder}/settings/configuration.php");
}

/**
 * Installer Redirect
 *
 * If Garden is not yet installed, force the request to /dashboard/setup and
 * begin installation.
 */
if (Gdn::config('Garden.Installed', false) === false && strpos(Gdn_Url::request(), 'setup') === false) {
    safeHeader('Location: '.Gdn::request()->url('dashboard/setup', true));
    exit();
}

// Re-apply loaded user settings.
Gdn::config()->overlayDynamic();

/**
 * Bootstrap Late
 *
 * All configurations are loaded, as well as the Application, Plugin and Theme
 * managers.
 */
if (file_exists(PATH_CONF.'/bootstrap.late.php')) {
    require_once(PATH_CONF.'/bootstrap.late.php');
}

if (c('Debug')) {
    debug(true);
}

Gdn_Cache::trace(debug());

/**
 * Factory Services
 *
 * These are the helper classes that facilitate Garden's operation. They can be
 * overwritten using FactoryOverwrite, but their defaults are installed here.
 */

// Default database.
Gdn::factoryInstall(Gdn::AliasDatabase, 'Gdn_Database', null, Gdn::FactorySingleton, array('Database'));

// Database drivers.
Gdn::factoryInstall('MySQLDriver', 'Gdn_MySQLDriver', null, Gdn::FactoryInstance);
Gdn::factoryInstall('MySQLStructure', 'Gdn_MySQLStructure', null, Gdn::FactoryInstance);

// Form class
Gdn::factoryInstall('Form', 'Gdn_Form', null, Gdn::FactoryInstance);

// Identity, Authenticator & Session.
Gdn::factoryInstall('Identity', 'Gdn_CookieIdentity');
Gdn::factoryInstall(Gdn::AliasSession, 'Gdn_Session');
Gdn::factoryInstall(Gdn::AliasAuthenticator, 'Gdn_Auth');

// Dispatcher.
Gdn::factoryInstall(Gdn::AliasRouter, 'Gdn_Router');
Gdn::factoryInstall(Gdn::AliasDispatcher, 'Gdn_Dispatcher');

// Smarty Templating Engine
Gdn::factoryInstall('Smarty', 'Smarty', PATH_LIBRARY.'/vendors/smarty/libs/Smarty.class.php');
Gdn::factoryInstall('ViewHandler.tpl', 'Gdn_Smarty');

// Slice handler
Gdn::factoryInstall(Gdn::AliasSlice, 'Gdn_Slice');

// Remote Statistics
Gdn::factoryInstall('Statistics', 'Gdn_Statistics', null, Gdn::FactorySingleton);
Gdn::statistics();

// Regarding
Gdn::factoryInstall('Regarding', 'Gdn_Regarding', null, Gdn::FactorySingleton);
Gdn::regarding();

// Other objects.
Gdn::FactoryInstall('BBCodeFormatter', 'BBCode', null, Gdn::FactorySingleton);
Gdn::factoryInstall('Dummy', 'Gdn_Dummy');

/**
 * Extension Startup
 *
 * Allow installed Extensions (Applications, Themes, Plugins) to execute startup
 * and bootstrap procedures that they may have, here.
 */

// Applications startup
foreach (Gdn::applicationManager()->enabledApplicationFolders() as $applicationName => $applicationFolder) {
    // Include the application's bootstrap.
    $gdnPath = PATH_APPLICATIONS."/{$applicationFolder}/settings/bootstrap.php";
    if (file_exists($gdnPath)) {
        include_once($gdnPath);
    }

    // Include the application's hooks.
    $hooksPath = PATH_APPLICATIONS."/{$applicationFolder}/settings/class.hooks.php";
    if (file_exists($hooksPath)) {
        include_once($hooksPath);
    }
}

unset($gdnPath);
unset($hooksPath);

// Themes startup
Gdn::themeManager()->start();
Gdn_Autoloader::attach(Gdn_Autoloader::CONTEXT_THEME);

// Plugins startup
Gdn::pluginManager()->start();
Gdn_Autoloader::attach(Gdn_Autoloader::CONTEXT_PLUGIN);

/**
 * Locales
 *
 * Install any custom locales provided by applications and plugins, and set up
 * the locale management system.
 */

// Load the Garden locale system
$gdnLocale = new Gdn_Locale(c('Garden.Locale', 'en'), Gdn::applicationManager()->enabledApplicationFolders(), Gdn::pluginManager()->enabledPluginFolders());
Gdn::factoryInstall(Gdn::AliasLocale, 'Gdn_Locale', null, Gdn::FactorySingleton, $gdnLocale);
unset($gdnLocale);

require_once(PATH_LIBRARY_CORE.'/functions.validation.php');

// Start Authenticators
Gdn::authenticator()->startAuthenticator();

/**
 * Bootstrap After
 *
 * After the bootstrap has finished loading, this hook allows developers a last
 * chance to customize Garden's runtime environment before the actual request
 * is handled.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.after.php')) {
    require_once(PATH_ROOT.'/conf/bootstrap.after.php');
}

// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once(PATH_LIBRARY_CORE.'/functions.render.php');

if (!defined('CLIENT_NAME')) {
    define('CLIENT_NAME', 'vanilla');
}

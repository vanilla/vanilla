<?php if (!defined('APPLICATION')) exit();

/**
 * Bootstrap Before
 * 
 * This file gives developers the opportunity to hook into Garden before any 
 * real work has been done. Nothing has been included yet, aside from this file.
 * No Garden features are available yet.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.before.php'))
   require_once(PATH_ROOT.'/conf/bootstrap.before.php');

/**
 * Define Core Constants
 * 
 * Garden depends on the presence of a certain base set of defines that allow it
 * to be aware of its own place within the system. These are conditionally 
 * defined here, in case they've already been set by a zealous bootstrap.before.
 */

// Path to the primary configuration file
if (!defined('PATH_CONF')) define('PATH_CONF', PATH_ROOT.'/conf');

// Include default constants if none were defined elsewhere
if (!defined('VANILLA_CONSTANTS'))
   include(PATH_CONF.'/constants.php');

// Make sure a default time zone is set
date_default_timezone_set('UTC');

// Include the core function definitions
require_once(PATH_LIBRARY_CORE.'/functions.error.php');
require_once(PATH_LIBRARY_CORE.'/functions.general.php');
require_once(PATH_LIBRARY_CORE.'/functions.compatibility.php');

// Include and initialize the autoloader
require_once(PATH_LIBRARY_CORE.'/class.autoloader.php');
Gdn_Autoloader::Start();

// Cache Layer
Gdn::FactoryInstall(Gdn::AliasCache, 'Gdn_Cache', NULL, Gdn::FactoryRealSingleton, 'Initialize');

// Install the configuration handler
Gdn::FactoryInstall(Gdn::AliasConfig, 'Gdn_Configuration');

// Load default baseline Garden configurations
Gdn::Config()->Load(PATH_CONF.'/config-defaults.php');

// Load installation-specific configuration so that we know what apps are enabled
Gdn::Config()->Load(PATH_CONF.'/config.php', 'Configuration', TRUE);

Gdn::Config()->Caching(TRUE);

/**
 * Bootstrap Early
 * 
 * A lot of the framework is loaded now, most importantly the autoloader, 
 * default config and the general and error functions. More control is possible 
 * here, but some things have already been loaded and are immutable.
 */
if (file_exists(PATH_CONF.'/bootstrap.early.php'))
   require_once(PATH_CONF.'/bootstrap.early.php');

Debug(C('Debug', FALSE));

// Default request object
Gdn::FactoryInstall(Gdn::AliasRequest, 'Gdn_Request', NULL, Gdn::FactoryRealSingleton, 'Create');
Gdn::Request()->FromEnvironment();

/**
 * Extension Managers
 * 
 * Now load the Application, Theme and Plugin managers into the Factory, and 
 * process the Application-specific configuration defaults.
 */

// ApplicationManager
Gdn::FactoryInstall(Gdn::AliasApplicationManager, 'Gdn_ApplicationManager');
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_APPLICATION);

// ThemeManager
Gdn::FactoryInstall(Gdn::AliasThemeManager, 'Gdn_ThemeManager');

// PluginManager
Gdn::FactoryInstall(Gdn::AliasPluginManager, 'Gdn_PluginManager');

// Load the configurations for enabled Applications
foreach (Gdn::ApplicationManager()->EnabledApplicationFolders() as $ApplicationName => $ApplicationFolder)
   Gdn::Config()->Load(PATH_APPLICATIONS."/{$ApplicationFolder}/settings/configuration.php");
   
/**
 * Installer Redirect
 * 
 * If Garden is not yet installed, force the request to /dashboard/setup and 
 * begin installation.
 */
if (Gdn::Config('Garden.Installed', FALSE) === FALSE && strpos(Gdn_Url::Request(), 'setup') === FALSE) {
   header('Location: '.Gdn::Request()->Url('dashboard/setup', TRUE));
   exit();
}

// Re-apply loaded user settings
Gdn::Config()->OverlayDynamic();

/**
 * Bootstrap Late
 * 
 * All configurations are loaded, as well as the Application, Plugin and Theme 
 * managers.
 */
if (file_exists(PATH_CONF.'/bootstrap.late.php'))
   require_once(PATH_CONF.'/bootstrap.late.php');

if (C('Debug'))
   Debug(TRUE);

Gdn_Cache::Trace(Debug());

/**
 * Factory Services
 * 
 * These are the helper classes that facilitate Garden's operation. They can be 
 * overwritten using FactoryOverwrite, but their defaults are installed here.
 */

// Default database.
Gdn::FactoryInstall(Gdn::AliasDatabase, 'Gdn_Database', NULL, Gdn::FactorySingleton, array('Database'));
// Database drivers.
Gdn::FactoryInstall('MySQLDriver', 'Gdn_MySQLDriver', NULL, Gdn::FactoryInstance);
Gdn::FactoryInstall('MySQLStructure', 'Gdn_MySQLStructure', NULL, Gdn::FactoryInstance);
// Form class
Gdn::FactoryInstall('Form', 'Gdn_Form', NULL, Gdn::FactoryInstance);

// Identity, Authenticator & Session.
Gdn::FactoryInstall('Identity', 'Gdn_CookieIdentity');
Gdn::FactoryInstall(Gdn::AliasSession, 'Gdn_Session');
Gdn::FactoryInstall(Gdn::AliasAuthenticator, 'Gdn_Auth');

// Dispatcher.
Gdn::FactoryInstall(Gdn::AliasRouter, 'Gdn_Router');
Gdn::FactoryInstall(Gdn::AliasDispatcher, 'Gdn_Dispatcher');

// Smarty Templating Engine
Gdn::FactoryInstall('Smarty', 'Smarty', PATH_LIBRARY.'/vendors/Smarty-2.6.25/libs/Smarty.class.php');
Gdn::FactoryInstall('ViewHandler.tpl', 'Gdn_Smarty');

// Slice handler
Gdn::FactoryInstall(Gdn::AliasSlice, 'Gdn_Slice');

// Remote Statistics
Gdn::FactoryInstall('Statistics', 'Gdn_Statistics', NULL, Gdn::FactorySingleton);
Gdn::Statistics();

// Regarding
Gdn::FactoryInstall('Regarding', 'Gdn_Regarding', NULL, Gdn::FactorySingleton);
Gdn::Regarding();

// Other objects.
Gdn::FactoryInstall('Dummy', 'Gdn_Dummy');

/**
 * Extension Startup
 * 
 * Allow installed Extensions (Applications, Themes, Plugins) to execute startup
 * and bootstrap procedures that they may have, here.
 */

// Applications startup
foreach (Gdn::ApplicationManager()->EnabledApplicationFolders() as $ApplicationName => $ApplicationFolder) {
   // Include the application's bootstrap.
   $Gdn_Path = PATH_APPLICATIONS."/{$ApplicationFolder}/settings/bootstrap.php";
   if (file_exists($Gdn_Path))
      include_once($Gdn_Path);
      
   // Include the application's hooks.
   $Hooks_Path = PATH_APPLICATIONS."/{$ApplicationFolder}/settings/class.hooks.php";
   if (file_exists($Hooks_Path))
      include_once($Hooks_Path);
}

unset($Gdn_Path);
unset($Hooks_Path);

// Themes startup
Gdn::ThemeManager()->Start();
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_THEME);

// Plugins startup
Gdn::PluginManager()->Start();
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_PLUGIN);

/**
 * Locales
 * 
 * Install any custom locales provided by applications and plugins, and set up 
 * the locale management system.
 */

// Load the Garden locale system
$Gdn_Locale = new Gdn_Locale(C('Garden.Locale', 'en-CA'), Gdn::ApplicationManager()->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders());
Gdn::FactoryInstall(Gdn::AliasLocale, 'Gdn_Locale', NULL, Gdn::FactorySingleton, $Gdn_Locale);
unset($Gdn_Locale);

require_once(PATH_LIBRARY_CORE.'/functions.validation.php');

// Start Authenticators
Gdn::Authenticator()->StartAuthenticator();

/**
 * Bootstrap After
 * 
 * After the bootstrap has finished loading, this hook allows developers a last
 * chance to customize Garden's runtime environment before the actual request
 * is handled.
 */
if (file_exists(PATH_ROOT.'/conf/bootstrap.after.php'))
   require_once(PATH_ROOT.'/conf/bootstrap.after.php');
   
// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once(PATH_LIBRARY_CORE.'/functions.render.php');

if (!defined('CLIENT_NAME'))
   define('CLIENT_NAME', 'vanilla');

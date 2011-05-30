<?php if (!defined('APPLICATION')) exit();
// Include a user-defined bootstrap.
if (file_exists(PATH_ROOT.'/conf/bootstrap.before.php'))
	require_once(PATH_ROOT.'/conf/bootstrap.before.php');

if (!defined('PATH_LOCAL_ROOT')) define('PATH_LOCAL_ROOT', PATH_ROOT);

// Define core constants.
if(!defined('PATH_CONF')) define('PATH_CONF', PATH_ROOT.'/conf');
if(!defined('PATH_LOCAL_CONF')) define('PATH_LOCAL_CONF', PATH_CONF);

// Include default constants if none were defined elsewhere
if (!defined('VANILLA_CONSTANTS'))
   include(PATH_CONF.'/constants.php');
   
if (!defined('PATH_APPLICATIONS')) define('PATH_APPLICATIONS', PATH_ROOT.'/applications');
if (!defined('PATH_LOCAL_APPLICATIONS')) define('PATH_LOCAL_APPLICATIONS', PATH_APPLICATIONS);

if (!defined('PATH_CACHE')) define('PATH_CACHE', PATH_ROOT.'/cache');
if (!defined('PATH_LOCAL_CACHE')) define('PATH_LOCAL_CACHE', PATH_CACHE);

if (!defined('PATH_UPLOADS')) define('PATH_UPLOADS', PATH_ROOT.'/uploads');
if (!defined('PATH_LOCAL_UPLOADS')) define('PATH_LOCAL_UPLOADS', PATH_UPLOADS);

if (!defined('PATH_PLUGINS')) define('PATH_PLUGINS', PATH_ROOT.'/plugins');
if (!defined('PATH_LOCAL_PLUGINS')) define('PATH_LOCAL_PLUGINS', PATH_PLUGINS);

if (!defined('PATH_THEMES')) define('PATH_THEMES', PATH_ROOT.'/themes');
if (!defined('PATH_LOCAL_THEMES')) define('PATH_LOCAL_THEMES', PATH_THEMES);

if (!defined('PATH_LIBRARY')) define('PATH_LIBRARY', PATH_ROOT.'/library');
if (!defined('PATH_LIBRARY_CORE')) define('PATH_LIBRARY_CORE', PATH_LIBRARY.'/core');

// Make sure a default time zone is set
if (ini_get('date.timezone') == '')
   date_default_timezone_set('America/Montreal');

// Include the core function definitions
require_once(PATH_LIBRARY_CORE.'/functions.error.php');
require_once(PATH_LIBRARY_CORE.'/functions.general.php');

// Include and initialize the autoloader
require_once(PATH_LIBRARY_CORE.'/class.autoloader.php');
Gdn_Autoloader::Start();

// Cache Layer
Gdn::FactoryInstall(Gdn::AliasCache, 'Gdn_Cache', NULL, Gdn::FactoryRealSingleton, 'Initialize');

/// Install the configuration.
Gdn::FactoryInstall(Gdn::AliasConfig, 'Gdn_Configuration');

// Configuration Defaults.
Gdn::Config()->Load(PATH_CONF.'/config-defaults.php', 'Use');

// Load installation-specific static configuration so that we know what apps are enabled.
Gdn::Config()->Load(PATH_CONF.'/config.php', 'Use');

Gdn::Config()->Caching(TRUE);

Debug(C('Debug', FALSE));

if (PATH_LOCAL_CONF != PATH_CONF) {
   // Load the custom configurations 
   Gdn::Config()->Load(PATH_LOCAL_CONF.'/config.php', 'Use');
}

// Default request object
Gdn::FactoryInstall(Gdn::AliasRequest, 'Gdn_Request', NULL, Gdn::FactoryRealSingleton, 'Create');
Gdn::Request()->FromEnvironment();

// ApplicationManager
Gdn::FactoryInstall(Gdn::AliasApplicationManager, 'Gdn_ApplicationManager');
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_APPLICATION);

// ThemeManager
Gdn::FactoryInstall(Gdn::AliasThemeManager, 'Gdn_ThemeManager');

// PluginManager
Gdn::FactoryInstall(Gdn::AliasPluginManager, 'Gdn_PluginManager');

// Load the configurations for the installed items.
$Gdn_EnabledApplications = Gdn::Config('EnabledApplications', array());
foreach ($Gdn_EnabledApplications as $ApplicationName => $ApplicationFolder) {
	Gdn::Config()->Load(PATH_APPLICATIONS."/{$ApplicationFolder}/settings/configuration.php", 'Use');
}

// Load the custom configurations again so that application setting defaults are overridden.
Gdn::Config()->Load(PATH_LOCAL_CONF.'/config.php', 'Use');

// Redirect to the setup screen if Dashboard hasn't been installed yet.
if (!Gdn::Config('Garden.Installed', FALSE) && strpos(Gdn_Url::Request(), 'setup') === FALSE) {
   header('location: '.Gdn::Request()->Url('dashboard/setup', TRUE));
   exit();
}

// Install some of the services.
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

// Execute other application startup.
foreach ($Gdn_EnabledApplications as $ApplicationName => $ApplicationFolder) {
	// Include the application's bootstrap.
	$Gdn_Path = PATH_APPLICATIONS."/{$ApplicationFolder}/settings/bootstrap.php";
	if (file_exists($Gdn_Path))
		include_once($Gdn_Path);
		
	// Include the application's hooks.
	$Hooks_Path = PATH_APPLICATIONS."/{$ApplicationFolder}/settings/class.hooks.php";
   if (file_exists($Hooks_Path))
		include_once($Hooks_Path);
}

unset($Gdn_EnabledApplications);
unset($Gdn_Path);
unset($Hooks_Path);

Gdn::ThemeManager()->Start();
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_THEME);

Gdn::PluginManager()->Start();
Gdn_Autoloader::Attach(Gdn_Autoloader::CONTEXT_PLUGIN);

if (!Gdn::FactoryExists(Gdn::AliasLocale)) {
	$Codeset = Gdn::Config('Garden.LocaleCodeset', 'UTF8');
	$CurrentLocale = Gdn::Config('Garden.Locale', 'en-CA');
	$SetLocale = str_replace('-', '_', $CurrentLocale).'.'.$Codeset;
	setlocale(LC_ALL, $SetLocale);
	$Gdn_Locale = new Gdn_Locale($CurrentLocale, Gdn::ApplicationManager()->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders());
	Gdn::FactoryInstall(Gdn::AliasLocale, 'Gdn_Locale', NULL, Gdn::FactorySingleton, $Gdn_Locale);
	unset($Gdn_Locale);
}

require_once(PATH_LIBRARY_CORE.'/functions.validation.php');

Gdn::Authenticator()->StartAuthenticator();

// Include a user-defined bootstrap.
if (file_exists(PATH_ROOT.'/conf/bootstrap.after.php'))
	require_once(PATH_ROOT.'/conf/bootstrap.after.php');
	
// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once(PATH_LIBRARY_CORE.'/functions.render.php');
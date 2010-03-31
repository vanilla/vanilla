<?php if (!defined('APPLICATION')) exit();
/// Include a user-defined bootstrap.
if(file_exists(PATH_ROOT.DS.'conf'.DS.'bootstrap.before.php'))
	require_once(PATH_ROOT.DS.'conf'.DS.'bootstrap.before.php');
	
/// Define core constants.
if(!defined('PATH_CONF')) define('PATH_CONF', PATH_ROOT.DS.'conf');
include(PATH_CONF . DS . 'constants.php');
if(!defined('PATH_APPLICATIONS')) define('PATH_APPLICATIONS', PATH_ROOT.DS.'applications');
if(!defined('PATH_CACHE')) define('PATH_CACHE', PATH_ROOT.DS.'cache');
if(!defined('PATH_LIBRARY')) define('PATH_LIBRARY', PATH_ROOT.DS.'library');
if(!defined('PATH_LIBRARY_CORE')) define('PATH_LIBRARY_CORE', PATH_LIBRARY.DS.'core');
if(!defined('PATH_PLUGINS')) define('PATH_PLUGINS', PATH_ROOT.DS.'plugins');
if(!defined('PATH_THEMES')) define('PATH_THEMES', PATH_ROOT.DS.'themes');

// Make sure a default time zone is set
if (ini_get('date.timezone') == '')
   date_default_timezone_set('Canada/Saskatchewan');

/// Include the error handler.
require_once(PATH_LIBRARY_CORE . DS . 'functions.error.php');

/// Include all the utility libraries.

require_once(PATH_LIBRARY_CORE . DS . 'functions.general.php');
require_once(PATH_LIBRARY_CORE . DS . 'functions.validation.php');

require_once(PATH_LIBRARY_CORE . DS . 'interface.iauthenticator.php');
require_once(PATH_LIBRARY_CORE . DS . 'interface.iplugin.php');
require_once(PATH_LIBRARY_CORE . DS . 'interface.isingleton.php');
require_once(PATH_LIBRARY_CORE . DS . 'interface.imodule.php');

require_once(PATH_LIBRARY_CORE . DS . 'class.pluggable.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.controller.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.dispatcher.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.filesystem.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.format.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.model.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.module.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.modulecollection.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.schema.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.session.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.shell.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.url.php');
require_once(PATH_LIBRARY_CORE . DS . 'class.validation.php');

/// Include the core Gdn object.
require_once(PATH_LIBRARY_CORE . DS . 'class.gdn.php');

/// Install the factory.
require_once(PATH_LIBRARY_CORE . DS . 'class.factory.php');
Gdn::SetFactory(new Gdn_Factory(), FALSE);
$FactoryOverwriteBak = Gdn::FactoryOverwrite(FALSE);

/// Install the configuration.
Gdn::FactoryInstall(Gdn::AliasConfig, 'Gdn_Configuration', PATH_LIBRARY_CORE.DS.'class.configuration.php', Gdn::FactorySingleton);
$Gdn_Config = Gdn::Factory(Gdn::AliasConfig);

/// Configuration Defaults.
$Gdn_Config->Load(PATH_CONF.DS.'config-defaults.php', 'Use');

// Load the custom configurations so that we know what apps are enabled.
$Gdn_Config->Load(PATH_CONF.DS.'config.php', 'Use');

/// Load the configurations for the installed items.
$Gdn_EnabledApplications = Gdn::Config('EnabledApplications', array());
foreach ($Gdn_EnabledApplications as $ApplicationName => $ApplicationFolder) {
	$Gdn_Config->Load(PATH_APPLICATIONS.DS.$ApplicationFolder.DS.'settings'.DS.'configuration.php', 'Use');
}

/// Load the custom configurations again so that application setting defaults are overridden.
$Gdn_Config->Load(PATH_CONF.DS.'config.php', 'Use');
unset($Gdn_Config);

// Redirect to the setup screen if Garden hasn't been installed yet.
if(!Gdn::Config('Garden.Installed', FALSE) && strpos(Gdn_Url::Request(), 'gardensetup') === FALSE) {
   header('location: '.CombinePaths(array(Gdn_Url::WebRoot(TRUE), 'index.php/garden/gardensetup'), '/'));
   exit();
}

/// Install some of the services.
// Default database.
Gdn::FactoryInstall(Gdn::AliasDatabase, 'Gdn_Database', PATH_LIBRARY.DS.'database'.DS.'class.database.php', Gdn::FactorySingleton, array('Database'));
// Database drivers.
Gdn::FactoryInstall('MySQLDriver', 'Gdn_MySQLDriver', PATH_LIBRARY.DS.'database'.DS.'class.mysql.driver.php', Gdn::FactoryInstance);
Gdn::FactoryInstall('MySQLStructure', 'Gdn_MySQLStructure', PATH_LIBRARY.DS.'database'.DS.'class.mysql.structure.php', Gdn::FactoryInstance);
// Form class
Gdn::FactoryInstall('Form', 'Gdn_Form', PATH_LIBRARY.DS.'core'.DS.'class.form.php', Gdn::FactoryInstance);

// Identity, Authenticator & Session.
Gdn::FactoryInstall('Identity', 'Gdn_CookieIdentity', PATH_LIBRARY_CORE.DS.'class.cookieidentity.php');
$AuthType = Gdn::Config('Garden.Authenticator.Type', 'Password');
Gdn::FactoryInstall(Gdn::AliasAuthenticator, 'Gdn_'.$AuthType.'Authenticator', PATH_LIBRARY_CORE.DS.'class.'.strtolower($AuthType).'authenticator.php', Gdn::FactorySingleton, array('Garden.Authenticator'));
Gdn::FactoryInstall(Gdn::AliasSession, 'Gdn_Session', PATH_LIBRARY_CORE.DS.'class.session.php');
// Dispatcher.
Gdn::FactoryInstall(Gdn::AliasDispatcher, 'Gdn_Dispatcher', PATH_LIBRARY_CORE.DS.'class.dispatcher.php', Gdn::FactorySingleton);
// Smarty Templating Engine
Gdn::FactoryInstall('Smarty', 'Smarty', PATH_LIBRARY.DS.'vendors'.DS.'Smarty-2.6.25'.DS.'libs'.DS.'Smarty.class.php', Gdn::FactorySingleton);
Gdn::FactoryInstall('ViewHandler.tpl', 'Gdn_Smarty', PATH_LIBRARY_CORE.DS.'class.smarty.php', Gdn::FactorySingleton);
// Application manager.
Gdn::FactoryInstall('ApplicationManager', 'Gdn_ApplicationManager', PATH_LIBRARY_CORE.DS.'class.applicationmanager.php', Gdn::FactorySingleton);

// Other objects.
Gdn::FactoryInstall('Dummy', 'Gdn_Dummy', PATH_LIBRARY_CORE.DS.'class.dummy.php', Gdn::FactorySingleton);
if(!Gdn::FactoryExists(Gdn::AliasLocale)) {
	require_once(PATH_LIBRARY_CORE.DS.'class.locale.php');
	$CurrentLocale = Gdn::Config('Garden.Locale', 'en-CA');
	setlocale(LC_ALL, str_replace('-', '_', $CurrentLocale));
	$Gdn_Locale = new Gdn_Locale($CurrentLocale, Gdn::Config('EnabledApplications'), Gdn::Config('EnabledPlugins'));
	Gdn::FactoryInstall(Gdn::AliasLocale, 'Gdn_Locale', PATH_LIBRARY_CORE.DS.'class.locale.php', Gdn::FactorySingleton, $Gdn_Locale);
	unset($Gdn_Locale);
}
// Execute other application startup.
foreach ($Gdn_EnabledApplications as $ApplicationName => $ApplicationFolder) {
	// Include the application's bootstrap.
	$Gdn_Path = PATH_APPLICATIONS.DS.$ApplicationFolder.DS.'settings'.DS.'bootstrap.php';
	if(file_exists($Gdn_Path))
		include_once($Gdn_Path);
		
	// Include the application's hooks.
   include_once(PATH_APPLICATIONS . DS . $ApplicationFolder . DS . 'settings' . DS . 'hooks.php');
}
unset($Gdn_EnabledApplications);
unset($Gdn_Path);

// If there is a hooks file in the theme folder, include it.
$ThemeHooks = PATH_THEMES . DS . Gdn::Config('Garden.Theme', 'default') . DS . 'hooks.php';
if (file_exists($ThemeHooks))
	include_once($ThemeHooks);

// Set up the plugin manager (doing this early so it has fewer classes to
// examine to determine if they are plugins).
Gdn::FactoryInstall('PluginManager', 'Gdn_PluginManager', PATH_LIBRARY . DS . 'core' . DS . 'class.pluginmanager.php', Gdn::FactorySingleton);
$PluginManager = Gdn::Factory('PluginManager');
$PluginInfo = $PluginManager->IncludePlugins();
$PluginManager->EnabledPlugins = $PluginInfo;
$PluginManager->RegisterPlugins();
unset($EnabledPlugins);
unset($PluginInfo);

Gdn::FactoryOverwrite($FactoryOverwriteBak);
unset($FactoryOverwriteBak);

/// Include a user-defined bootstrap.
if(file_exists(PATH_ROOT.DS.'conf'.DS.'bootstrap.after.php'))
	require_once(PATH_ROOT.DS.'conf'.DS.'bootstrap.after.php');
	
// Include "Render" functions now - this way pluggables and custom confs can override them.
require_once(PATH_LIBRARY_CORE . DS . 'functions.render.php');

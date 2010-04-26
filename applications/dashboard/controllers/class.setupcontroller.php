<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Dashboard Setup Controller
 */
class SetupController extends DashboardController {
   
   public $Uses = array('Form', 'Database');
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddCssFile('setup.css');
   }
   
   /**
    * The summary of all settings available. The menu items displayed here are
    * collected from each application's application controller and all plugin's
    * definitions.
    */
   public function Index() {
      $this->ApplicationFolder = 'dashboard';
      $this->MasterView = 'setup';
      // Fatal error if Garden has already been installed.
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Installed = Gdn::Config('Garden.Installed') ? TRUE : FALSE;
      if ($Installed)
         trigger_error(ErrorMessage('Vanilla has already been installed.', 'SetupController', 'Index'));
         
      if (!$this->_CheckPrerequisites()) {
         $this->View = 'prerequisites';
      } else {
         $this->View = 'configure';
         $ApplicationManager = new Gdn_ApplicationManager();
         $AvailableApplications = $ApplicationManager->AvailableApplications();
         
         // Need to go through all of the setups for each application. Garden,
         if ($this->Configure() && $this->Form->IsPostBack()) {
            // Step through the available applications, enabling each of them
            $AppNames = array_keys($AvailableApplications);
            try {
               foreach ($AvailableApplications as $AppName => $AppInfo) {
                  if (strtolower($AppName) != 'dashboard') {
                     $Validation = new Gdn_Validation();
                     $ApplicationManager->RegisterPermissions($AppName, $Validation);
                     $ApplicationManager->EnableApplication($AppName, $Validation);
                  }
               }
            } catch (Exception $ex) {
               $this->Form->AddError(strip_tags($ex->getMessage()));
            }
            if ($this->Form->ErrorCount() == 0) {
               // Save a variable so that the application knows it has been installed.
               // Now that the application is installed, select a more user friendly error page.
               SaveToConfig(array(
                  'Garden.Installed' => TRUE,
                  'Garden.Errors.MasterView' => 'error.master.php'
               ));
               
               // Go to the dashboard
               Redirect('/settings');
            }
         }
      }
      $this->Render();
   }
   
   /**
    * Allows the configuration of basic setup information in Garden. This
    * should not be functional after the application has been set up.
    */
   public function Configure($RedirectUrl = '') {
      $Config = Gdn::Factory(Gdn::AliasConfig);
      
      // Create a model to save configuration settings
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.Locale', 'Garden.Title', 'Garden.RewriteUrls', 'Garden.WebRoot', 'Garden.Cookie.Salt', 'Garden.Cookie.Domain', 'Database.Name', 'Database.Host', 'Database.User', 'Database.Password'));
      
      // Set the models on the forms.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load the locales for the locale dropdown
      // $Locale = Gdn::Locale();
      // $AvailableLocales = $Locale->GetAvailableLocaleSources();
      // $this->LocaleData = array_combine($AvailableLocales, $AvailableLocales);
      
      // If seeing the form for the first time...
      if (!$this->Form->IsPostback()) {
         // Force the webroot using our best guesstimates
         $ConfigurationModel->Data['Database.Host'] = 'localhost';
         $this->Form->SetData($ConfigurationModel->Data);
      } else {         
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->AddRule('Connection', 'function:ValidateConnection');
         $ConfigurationModel->Validation->ApplyRule('Database.Name', 'Connection');
         $ConfigurationModel->Validation->ApplyRule('Garden.Title', 'Required');
         
         $ConfigurationFormValues = $this->Form->FormValues();
         if ($ConfigurationModel->Validate($ConfigurationFormValues) !== TRUE) {
            // Apply the validation results to the form(s)
            $this->Form->SetValidationResults($ConfigurationModel->ValidationResults());
         } else {
            $Host = Gdn_Url::Host();
            $Domain = Gdn_Url::Domain();

            // Set up cookies now so that the user can be signed in.
            $ConfigurationFormValues['Garden.Cookie.Salt'] = RandomString(10);
            $ConfigurationFormValues['Garden.Cookie.Domain'] = strpos($Host, '.') === FALSE ? '' : $Host; // Don't assign the domain if it is a non .com domain as that will break cookies.
            $ConfigurationModel->Save($ConfigurationFormValues);
            
            // If changing locale, redefine locale sources:
            $NewLocale = 'en-CA'; // $this->Form->GetFormValue('Garden.Locale', FALSE);
            if ($NewLocale !== FALSE && Gdn::Config('Garden.Locale') != $NewLocale) {
               $ApplicationManager = new Gdn_ApplicationManager();
               $PluginManager = Gdn::Factory('PluginManager');
               $Locale = Gdn::Locale();
               $Locale->Set($NewLocale, $ApplicationManager->EnabledApplicationFolders(), $PluginManager->EnabledPluginFolders(), TRUE);
            }
            
            // Set the instantiated config object's db params and make the database use them (otherwise it will use the default values from conf/config-defaults.php).
            $Config->Set('Database.Host', $ConfigurationFormValues['Database.Host']);
            $Config->Set('Database.Name', $ConfigurationFormValues['Database.Name']);
            $Config->Set('Database.User', $ConfigurationFormValues['Database.User']);
            $Config->Set('Database.Password', $ConfigurationFormValues['Database.Password']);
            $Config->ClearSaveData();
            
            Gdn::FactoryInstall(Gdn::AliasDatabase, 'Gdn_Database', PATH_LIBRARY.DS.'database'.DS.'class.database.php', Gdn::FactorySingleton, array(Gdn::Config('Database')));
            
            // Install db structure & basic data.
            $Database = Gdn::Database();
            $Drop = FALSE; // Gdn::Config('Garden.Version') === FALSE ? TRUE : FALSE;
            $Explicit = FALSE;
            try {
               include(PATH_APPLICATIONS . DS . 'dashboard' . DS . 'settings' . DS . 'structure.php');
            } catch (Exception $ex) {
               $this->Form->AddError(strip_tags($ex->getMessage()));
            }
            
            if ($this->Form->ErrorCount() > 0)
               return FALSE;

            // Create the administrative user
            $UserModel = Gdn::UserModel();
            $UserModel->DefineSchema();
            $UserModel->Validation->ApplyRule('Name', 'Username', 'Admin username can only contain letters, numbers, and underscores.');
            $UserModel->Validation->ApplyRule('Password', 'Required');
            $UserModel->Validation->ApplyRule('Password', 'Match');
            
            if (!$UserModel->SaveAdminUser($ConfigurationFormValues)) {
               $this->Form->SetValidationResults($UserModel->ValidationResults());
            } else {
               // The user has been created successfully, so sign in now
               $Authenticator = Gdn::Authenticator();
               $AuthUserID = $Authenticator->Authenticate(array(
                  'Email' => $this->Form->GetValue('Email'),
                  'Password' => $this->Form->GetValue('Password'),
                  'RememberMe' => TRUE)
               );
            }
            
            if ($this->Form->ErrorCount() > 0)
               return FALSE;
            
            // Assign some extra settings to the configuration file if everything succeeded.
            $ApplicationInfo = array();
            include(CombinePaths(array(PATH_APPLICATIONS . DS . 'dashboard' . DS . 'settings' . DS . 'about.php')));
            SaveToConfig(array(
               'Garden.Version' => ArrayValue('Version', ArrayValue('Garden', $ApplicationInfo, array()), 'Undefined'),
               'Garden.WebRoot' => Gdn_Url::WebRoot(),
               'Garden.RewriteUrls' => (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) ? TRUE : FALSE,
               'Garden.Domain' => $Domain,
               'Garden.CanProcessImages' => function_exists('gd_info'),
               'EnabledPlugins.GettingStarted' => 'GettingStarted', // Make sure the getting started plugin is enabled
               'EnabledPlugins.HTMLPurifier' => 'HtmlPurifier' // Make sure html purifier is enabled so html has a default way of being safely parsed
            ));
         }
      }
      return $this->Form->ErrorCount() == 0 ? TRUE : FALSE;
   }
   
   private function _CheckPrerequisites() {
      // Make sure we are running at least PHP 5.1
      if (version_compare(phpversion(), ENVIRONMENT_PHP_VERSION) < 0)
         $this->Form->AddError("You are running <b>PHP version ".phpversion()."</b>. Vanilla requires PHP ".ENVIRONMENT_PHP_VERSION." or greater, so you'll need to upgrade PHP before you can continue.<code><span>Upgrade PHP to</span> v".ENVIRONMENT_PHP_VERSION."</code>");

      // Make sure PDO is available
      if (!class_exists('PDO'))
         $this->Form->AddError('You must have <b>PDO enabled</b> in PHP in order for Vanilla to connect to your database.<code><span>Enable</span> PHP PDO</code>');

      if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
         $this->Form->AddError('You must have the <b>MySQL driver for PDO</b> enabled in order for Vanilla to connect to your database.<code><span>Install</span> PDO_Mysql</code>');

      // Make sure that the correct filesystem permissions are in place
      
      // Make sure the config folder is writeable
      if (!is_readable(PATH_CONF) || !is_writable(PATH_CONF))
         $this->Form->AddError('Your <b>configuration folder</b> does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this folder: <code><span>Fix permissions</span> '.PATH_CONF.'</code>');
      else {
         $ConfigFile = PATH_CONF . DS . 'config.php';
         if (!file_exists($ConfigFile))
            file_put_contents($ConfigFile, '');
         
         // Make sure the config file is writeable
         if (!is_readable($ConfigFile) || !is_writable($ConfigFile))
            $this->Form->AddError('Your <b>configuration file</b> does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this file: <code><span>Fix permissions</span> '.$ConfigFile.'</code>');
      }
      
      $UploadsFolder = PATH_ROOT . DS . 'uploads';
      if (!is_readable($UploadsFolder) || !is_writable($UploadsFolder))
         $this->Form->AddError('Your <b>uploads folder</b> does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this folder: <code><span>Fix permissions</span> '.$UploadsFolder.'</code>');

      // Make sure the cache folder is writeable
      if (!is_readable(PATH_CACHE) || !is_writable(PATH_CACHE)) {
         $this->Form->AddError('Your <b>cache folder</b> does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this folder and all the files within it: <code><span>Fix permissions</span> '.PATH_CACHE.'</code>');
      } else {
         if (!file_exists(PATH_CACHE.DS.'HtmlPurifier')) mkdir(PATH_CACHE.DS.'HtmlPurifier');
         if (!file_exists(PATH_CACHE.DS.'Smarty')) mkdir(PATH_CACHE.DS.'Smarty');
         if (!file_exists(PATH_CACHE.DS.'Smarty'.DS.'cache')) mkdir(PATH_CACHE.DS.'Smarty'.DS.'cache');
         if (!file_exists(PATH_CACHE.DS.'Smarty'.DS.'compile')) mkdir(PATH_CACHE.DS.'Smarty'.DS.'compile');
      }
      return $this->Form->ErrorCount() == 0 ? TRUE : FALSE;
   }
   
    public function First() {
      // Start the session.
      Gdn::Session()->Start(Gdn::Authenticator());
   
      $this->Permission('Garden.First'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
      
      // Enable all of the plugins.
      $PluginManager = Gdn::Factory('PluginManager');
      foreach($PluginManager->EnabledPlugins as $PluginName => $PluginFolder) {
         $PluginManager->EnablePlugin($PluginName, NULL, TRUE);
      }
      
      Redirect('/settings');
   }
}

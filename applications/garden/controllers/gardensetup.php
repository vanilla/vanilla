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
 * Garden Setup Controller
 */
class GardenSetupController extends GardenController {
   
   public $Uses = array('Form', 'ApplicationManager', 'Database');
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddCssFile('setup.css');
   }
   
   /**
    * The summary of all settings available. The menu items displayed here are
    * collected from each application's appcontroller and all plugin's
    * definitions.
    */
   public function Index($CurrentStep = 1) {
      $this->MasterView = 'setup';
      // Fatal error if Garden has already been installed.
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Installed = Gdn::Config('Garden.Installed') ? TRUE : FALSE;
      if ($Installed)
         trigger_error(ErrorMessage('Garden has already been installed.', 'GardenSetupController', 'Index'));
         
      if (!$this->_CheckPrerequisites()) {
         $this->View = 'prerequisites';
         $this->Render();
      } else {
         $AvailableApplications = $this->ApplicationManager->AvailableApplications();
         $SetupApplications = array();
         foreach ($AvailableApplications as $AppName => $AppInfo) {
            if (ArrayValue('SetupController', $AppInfo)) {
               $SetupApplications[$AppName] = $AppInfo;
            }
         }
         $TotalSteps = count($SetupApplications) + 2;
         
         // Need to go through all of the setups for each application. Garden,
         // Step 1: Garden Setup
         // Step N: Other Application Setups
         // Final Step: Complete
         if ($CurrentStep == 1) {
            $this->View = 'configure';
            if (!$this->Configure() || !$this->Form->IsPostBack()) {
               $this->Render();
            } else {
               ++$CurrentStep;
               Redirect('/garden/gardensetup/'.$CurrentStep);
            }
         }
         
         if ($CurrentStep > 1 && $CurrentStep < $TotalSteps) {
            // Step through the available applications, enabling each of them
            $AppFolders = ConsolidateArrayValuesByKey($SetupApplications, 'Folder');
            $AppNames = array_keys($SetupApplications);
            $AppKey = $CurrentStep - 2;
            $AppFolder = $AppFolders[$AppKey];
            $AppName = $AppNames[$AppKey];
            $Validation = new Gdn_Validation();
            $this->ApplicationManager->RegisterPermissions($AppName, $Validation);
            if ($this->ApplicationManager->ApplicationSetup($AppName, $this, $Validation)) {
               ++$CurrentStep;
               Redirect('/garden/gardensetup/'.$CurrentStep);
            }
         }
         
         if ($CurrentStep == $TotalSteps) {
            // Save a variable so that the application knows it has been installed.
            SaveToConfig('Garden.Installed', TRUE);
            
            /*
            $Database = Gdn::Database();
            $DbTables = $Database->SQL()->FetchTables();
            if (in_array('LUM_User', $DbTables) === TRUE) {
               // If there are vanilla 1 tables to import, prompt the user with that screen
               $this->Render();
            } else {
               // Otherwise just redirect them to the dashboard
            */
               Redirect('/settings');
            // }
         }
      }
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
               include(PATH_APPLICATIONS . DS . 'garden' . DS . 'settings' . DS . 'structure.php');
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
            include(CombinePaths(array(PATH_APPLICATIONS . DS . 'garden' . DS . 'settings' . DS . 'about.php')));
            $Save = array(
               'Garden.Version' => ArrayValue('Version', ArrayValue('Garden', $ApplicationInfo, array()), 'Undefined'),
               'Garden.WebRoot' => Gdn_Url::WebRoot(),
               'Garden.RewriteUrls' => (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) ? TRUE : FALSE,
               'Garden.Domain' => $Domain,
               'Garden.CanProcessImages' => function_exists('gd_info'),
               'Garden.Messages.Cache' => 'arr:["Garden\/Settings\/Index"]', // Make sure that the "welcome" message is cached for viewing
               'EnabledPlugins.GettingStarted' => 'GettingStarted', // Make sure the getting started plugin is enabled
               'EnabledPlugins.HTMLPurifier' => 'HtmlPurifier' // Make sure html purifier is enabled so html has a default way of being safely parsed
            );
            SaveToConfig($Save);
         }
      }
      return $this->Form->ErrorCount() == 0 ? TRUE : FALSE;
   }
   
   private function _CheckPrerequisites() {
      // Make sure we are running at least PHP 5.1
      if (version_compare(phpversion(), '5.1.0') < 0)
         $this->Form->AddError("You are running PHP version ".phpversion().". Vanilla requires PHP 5.1.0 or greater, so you'll need to upgrade PHP before you can continue.");

      // Make sure PDO is available
      if (!class_exists('PDO'))
         $this->Form->AddError('You must have PDO enabled in PHP in order for Vanilla to connect to your database.');

      if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY'))
         $this->Form->AddError('You must have the MySQL driver for PDO enabled in order for Vanilla to connect to your database.');

      // Make sure that the correct filesystem permissions are in place
      $ConfigFile = PATH_CONF . DS . 'config.php';
      if (!file_exists($ConfigFile))
         file_put_contents($ConfigFile, '');
         
      if (!is_readable($ConfigFile) || !is_writable($ConfigFile))
         $this->Form->AddError('Your configuration file does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this file: <code>'.$ConfigFile.'</code>');

      $UploadsFolder = PATH_ROOT . DS . 'uploads';
      if (!is_readable($UploadsFolder) || !is_writable($UploadsFolder))
         $this->Form->AddError('Your uploads folder does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this folder: <code>'.$UploadsFolder.'</code>');

      // Make sure the cache folder is writeable
      if (!is_readable(PATH_CACHE) || !is_writable(PATH_CACHE)) {
         $this->Form->AddError('Your cache folder does not have the correct permissions. PHP needs to be able to <a href="http://vanillaforums.org/docs/FilePermissions/">read and write</a> to this folder and all the files within it: <code>'.PATH_CACHE.'</code>');
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
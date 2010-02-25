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
 * Garden Settings Controller
 */
class SettingsController extends GardenController {
   
   public $Uses = array('Form', 'Database');
   public $ModuleSortContainer = 'Dashboard';
   
   /**
    * Application management screen.
    */
   public function Applications($Filter = '', $TransientKey = '') {
      $Session = Gdn::Session();
      $ApplicationName = $Session->ValidateTransientKey($TransientKey) ? $Filter : '';
      if (!in_array($Filter, array('enabled', 'disabled')))
         $Filter = '';
         
      $this->Filter = $Filter;
      $this->Permission('Garden.Applications.Manage');
      $this->AddSideMenu('garden/settings/applications');

      $this->AddJsFile('applications.js');
      $this->Title(Translate('Applications'));
      
      $AuthenticatedPostBack = $this->Form->AuthenticatedPostBack();
      
      $ApplicationManager = new Gdn_ApplicationManager();
      $this->AvailableApplications = $ApplicationManager->AvailableVisibleApplications();
      $this->EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
      
      // Loop through all of the available visible apps and mark them if they have an update available
      // Retrieve the list of apps that require updates from the config file
      $RequiredUpdates = Format::Unserialize(Gdn::Config('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Format::ObjectAsArray($UpdateInfo);
               
            $NewVersion = ArrayValue('Version', $UpdateInfo, '');
            $Name = ArrayValue('Name', $UpdateInfo, '');
            $Type = ArrayValue('Type', $UpdateInfo, '');
            foreach ($this->AvailableApplications as $App => $Info) {
               $CurrentName = ArrayValue('Name', $Info, $App);
               if (
                  $CurrentName == $Name
                  && $Type == 'Application'
               ) {
                  $Info['NewVersion'] = $NewVersion;
                  $this->AvailableApplications[$App] = $Info;
               }
            }
         }
      }
      
      if ($ApplicationName != '') {
         if (array_key_exists($ApplicationName, $this->EnabledApplications) === TRUE) {
            try {
               $ApplicationManager->DisableApplication($ApplicationName);
            } catch (Exception $e) {
               $this->Form->AddError(strip_tags($e->getMessage()));
            }
         } else {
            try {
               $ApplicationManager->CheckRequirements($ApplicationName);
            } catch (Exception $e) {
               $this->Form->AddError(strip_tags($e->getMessage()));
            }
            if ($this->Form->ErrorCount() == 0) {
               $Validation = new Gdn_Validation();
               $ApplicationManager->RegisterPermissions($ApplicationName, $Validation);
               $ApplicationManager->EnableApplication($ApplicationName, $Validation);
               if ($ApplicationManager->ApplicationHasSetup($ApplicationName))
                  $ApplicationManager->ApplicationSetup($ApplicationName, $this->ControllerName, $Validation);
            }
            $this->Form->SetValidationResults($Validation->Results());
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('settings/applications');
      }
      $this->Render();
   }
   
   /**
    * Garden management screen.
    */
   public function Configure() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('garden/settings/configure');
      $this->AddJsFile('email.js');
      $this->Title(Translate('General Settings'));
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Garden.Locale',
         'Garden.Title',
         'Garden.RewriteUrls',
         'Garden.Email.SupportName',
         'Garden.Email.SupportAddress',
         'Garden.Email.UseSmtp',
         'Garden.Email.SmtpHost',
         'Garden.Email.SmtpUser',
         'Garden.Email.SmtpPassword',
         'Garden.Email.SmtpPort'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load the locales for the locale dropdown
      $Locale = Gdn::Locale();
      $AvailableLocales = $Locale->GetAvailableLocaleSources();
      $this->LocaleData = ArrayCombine($AvailableLocales, $AvailableLocales);
      
      // Check to see if mod_rewrit is enabled.
      if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
         $this->SetData('HasModRewrite', TRUE);
      } else {
         $this->SetData('HasModRewrite', FALSE);
      }
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Locale', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Title', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.RewriteUrls', 'Boolean');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportName', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Email');
         
         // If changing locale, redefine locale sources:
         $NewLocale = $this->Form->GetFormValue('Garden.Locale', FALSE);
         if ($NewLocale !== FALSE && Gdn::Config('Garden.Locale') != $NewLocale) {
            $ApplicationManager = new Gdn_ApplicationManager();
            $PluginManager = Gdn::Factory('PluginManager');
            $Locale = Gdn::Locale();
            $Locale->Set($NewLocale, $ApplicationManager->EnabledApplicationFolders(), $PluginManager->EnabledPluginFolders(), TRUE);
         }
         
         if ($this->Form->Save() !== FALSE) {
            $this->StatusMessage = Translate("Your settings have been saved.");
         }
      }
      
      $this->Render();      
   }      
   
   /**
    * Garden settings dashboard.
    */
   var $RequiredAdminPermissions = array();
   public function xIndex() {
      $this->AddJsFile('settings.js');
      $this->Title(Translate('Dashboard'));
         
      $this->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Routes.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Applications.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Plugins.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Themes.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Registration.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Applicants.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Roles.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $this->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $this->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $this->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $this->FireEvent('DefineAdminPermissions');
      $this->Permission($this->RequiredAdminPermissions, '', FALSE);
      $this->AddSideMenu('garden/settings');

      $UserModel = Gdn::UserModel();
      
      // Load some data to display on the dashboard
      $this->BuzzData = array();
      // Get the number of users in the database
      $CountUsers = $UserModel->GetCountLike();
      $this->AddDefinition('CountUsers', $CountUsers);
      $this->BuzzData[Translate('Users')] = number_format($CountUsers);
      // Get the number of new users in the last day
      $this->BuzzData[Translate('New users in the last day')] = number_format($UserModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 day')))));
      // Get the number of new users in the last week
      $this->BuzzData[Translate('New users in the last week')] = number_format($UserModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 week')))));
      
      // Get recently active users
      $this->ActiveUserData = $UserModel->GetActiveUsers(5);
      
      // Check for updates
      $this->AddUpdateCheck();
      
      // Fire an event so other applications can add some data to be displayed
      $this->FireEvent('DashboardData');
      $this->Render();
   }
   
   /**
    * Adds information to the definition list that causes the app to "phone
    * home" and see if there are upgrades available. Currently added to the
    * dashboard only.
    * Nothing renders with this method. It is public so it can be added by
    * plugins.
    */
   public function AddUpdateCheck() {
      // Check to see if the application needs to phone-home for updates. Doing
      // this here because this method is always called when admin pages are
      // loaded regardless of the application loading them.
      $UpdateCheckDate = Gdn::Config('Garden.UpdateCheckDate', '');
      if (
         $UpdateCheckDate == '' // was not previous defined
         || !IsTimestamp($UpdateCheckDate) // was not a valid timestamp
         || $UpdateCheckDate < strtotime("-1 day") // was not done within the last day
      ) {
         $UpdateData = array();
         
         // Grab all of the plugins & versions
         $PluginManager = Gdn::Factory('PluginManager');
         $Plugins = $PluginManager->AvailablePlugins();
         foreach ($Plugins as $Plugin => $Info) {
            $Name = ArrayValue('Name', $Info, $Plugin);
            $Version = ArrayValue('Version', $Info, '');
            if ($Version != '')
               $UpdateData[] = array(
                  'Name' => $Name,
                  'Version' => $Version,
                  'Type' => 'Plugin'
               );
         }
         
         // Grab all of the applications & versions
         $ApplicationManager = Gdn::Factory('ApplicationManager');
         $Applications = $ApplicationManager->AvailableApplications();
         foreach ($Applications as $Application => $Info) {
            $Name = ArrayValue('Name', $Info, $Application);
            $Version = ArrayValue('Version', $Info, '');
            if ($Version != '')
               $UpdateData[] = array(
                  'Name' => $Name,
                  'Version' => $Version,
                  'Type' => 'Application'
               );
         }

         // Grab all of the themes & versions
         $ThemeManager = new Gdn_ThemeManager;
         $Themes = $ThemeManager->AvailableThemes();
         foreach ($Themes as $Theme => $Info) {
            $Name = ArrayValue('Name', $Info, $Theme);
            $Version = ArrayValue('Version', $Info, '');
            if ($Version != '')
               $UpdateData[] = array(
                  'Name' => $Name,
                  'Version' => $Version,
                  'Type' => 'Theme'
               );
         }

         // Dump the entire set of information into the definition list (jQuery
         // will pick it up and ping the VanillaForums.org server with this info).
         $this->AddDefinition('UpdateChecks', Format::Serialize($UpdateData));
      }
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }
   
   public function Plugins($Filter = '', $TransientKey = '') {
      $this->Title(Translate('Plugins'));
         
      $Session = Gdn::Session();
      $PluginName = $Session->ValidateTransientKey($TransientKey) ? $Filter : '';
      if (!in_array($Filter, array('enabled', 'disabled')))
         $Filter = '';
         
      $this->Filter = $Filter;
      $this->Permission('Garden.Plugins.Manage');
      $this->AddSideMenu('garden/settings/plugins');
      
      // Retrieve all available plugins from the plugins directory
      $PluginManager = Gdn::Factory('PluginManager');
      $this->EnabledPlugins = $PluginManager->EnabledPlugins;
      $this->AvailablePlugins = $PluginManager->AvailablePlugins();
      
      // Loop through all of the available plugins and mark them if they have an update available
      // Retrieve the list of plugins that require updates from the config file
      $RequiredUpdates = Format::Unserialize(Gdn::Config('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Format::ObjectAsArray($UpdateInfo);
               
            $NewVersion = ArrayValue('Version', $UpdateInfo, '');
            $Name = ArrayValue('Name', $UpdateInfo, '');
            $Type = ArrayValue('Type', $UpdateInfo, '');
            foreach ($this->AvailablePlugins as $Plugin => $Info) {
               $CurrentName = ArrayValue('Name', $Info, $Plugin);
               if (
                  $CurrentName == $Name
                  && $Type == 'Plugin'
               ) {
                  $Info['NewVersion'] = $NewVersion;
                  $this->AvailablePlugins[$Plugin] = $Info;
               }
            }
         }
      }
      
      if ($PluginName != '') {
         try {
            if (array_key_exists($PluginName, $this->EnabledPlugins) === TRUE) {
               $PluginManager->DisablePlugin($PluginName);
            } else {
               $Validation = new Gdn_Validation();
               if (!$PluginManager->EnablePlugin($PluginName, $Validation))
                  $this->Form->SetValidationResults($Validation->Results());
            }
         } catch (Exception $e) {
            $this->Form->AddError(strip_tags($e->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('/settings/plugins');
      }
      $this->Render();
   }
   
   /**
    * Configuration of registration settings.
    */
   public function Registration($RedirectUrl = '') {
      $this->Permission('Garden.Registration.Manage');
      $this->AddSideMenu('garden/settings/registration');
      
      $this->AddJsFile('registration.js');
      $this->Title(Translate('Registration'));
      
      // Create a model to save configuration settings
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.Registration.Method', 'Garden.Registration.DefaultRoles', 'Garden.Registration.CaptchaPrivateKey', 'Garden.Registration.CaptchaPublicKey', 'Garden.Registration.InviteExpiration'));
      
      // Set the model on the forms.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load roles with sign-in permission
      $RoleModel = new Gdn_RoleModel();
      $this->RoleData = $RoleModel->GetByPermission('Garden.SignIn.Allow');
      
      // Get the currently selected default roles
      $this->ExistingRoleData = Gdn::Config('Garden.Registration.DefaultRoles');
      if (is_array($this->ExistingRoleData) === FALSE)
         $this->ExistingRoleData = array();
         
      // Get currently selected InvitationOptions
      $this->ExistingRoleInvitations = Gdn::Config('Garden.Registration.InviteRoles');
      if (is_array($this->ExistingRoleInvitations) === FALSE)
         $this->ExistingRoleInvitations = array();
         
      // Get the currently selected Expiration Length
      $this->InviteExpiration = Gdn::Config('Garden.Registration.InviteExpiration', '');
      
      // Registration methods.
      $this->RegistrationMethods = array(
         'Closed' => "Registration is closed.",
         'Basic' => "The applicants are granted access immediately.",
         'Captcha' => "The applicants must copy the text from a captcha image, proving that they are not a robot.",
         'Approval' => "The applicants must be approved by an administrator before they are granted access.",
         'Invitation' => "Existing members send out invitations to new members. Any person who receives an invitation is granted access immediately. Invitations are permission-based (defined below). Monthly invitations are NOT cumulative."
      );

      // Options for how many invitations a role can send out per month.
      $this->InvitationOptions = array(
         '0' => Gdn::Translate('None'),
         '1' => '1',
         '2' => '2',
         '5' => '5',
         '-1' => Gdn::Translate('Unlimited')
      );
      
      // Options for when invitations should expire.
      $this->InviteExpirationOptions = array(
        '-1 week' => Gdn::Translate('1 week after being sent'),
        '-2 weeks' => Gdn::Translate('2 weeks after being sent'),
        '-1 month' => Gdn::Translate('1 month after being sent'),
        'FALSE' => Gdn::Translate('never')
      );
      
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($ConfigurationModel->Data);
      } else {   
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Registration.Method', 'Required');   
         if($this->Form->GetValue('Garden.Registration.Method') != 'Closed')
            $ConfigurationModel->Validation->ApplyRule('Garden.Registration.DefaultRoles', 'RequiredArray');
         
         // Define the Garden.Registration.RoleInvitations setting based on the postback values
         $InvitationRoleIDs = $this->Form->GetValue('InvitationRoleID');
         $InvitationCounts = $this->Form->GetValue('InvitationCount');
         $this->ExistingRoleInvitations = ArrayCombine($InvitationRoleIDs, $InvitationCounts);
         $ConfigurationModel->ForceSetting('Garden.Registration.InviteRoles', $this->ExistingRoleInvitations);
         
         // Save!
         if ($this->Form->Save() !== FALSE) {
            $this->StatusMessage = Translate("Your settings have been saved.");
            if ($RedirectUrl != '')
               $this->RedirectUrl = $RedirectUrl;
         }
      }
      
      $this->Render();
   }

   /**
    * Theme management screen.
    */
   public function Themes($ThemeFolder = '', $TransientKey = '') {
      $this->Title(Translate('Themes'));
         
      $this->Permission('Garden.Themes.Manage');
      $this->AddSideMenu('garden/settings/themes');

      $Session = Gdn::Session();
      $ThemeManager = new Gdn_ThemeManager();
      $this->AvailableThemes = $ThemeManager->AvailableThemes();
      $this->EnabledThemeFolder = $ThemeManager->EnabledTheme();
      $this->EnabledTheme = $ThemeManager->EnabledThemeInfo();
      $Name = array_keys($this->EnabledTheme);
      $Name = ArrayValue(0, $Name, 'undefined');
      $this->EnabledTheme = ArrayValue($Name, $this->EnabledTheme);
      $this->EnabledThemeName = ArrayValue('Name', $this->EnabledTheme, $Name);
      
      // Loop through all of the available themes and mark them if they have an update available
      // Retrieve the list of themes that require updates from the config file
      $RequiredUpdates = Format::Unserialize(Gdn::Config('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Format::ObjectAsArray($UpdateInfo);
               
            $NewVersion = ArrayValue('Version', $UpdateInfo, '');
            $Name = ArrayValue('Name', $UpdateInfo, '');
            $Type = ArrayValue('Type', $UpdateInfo, '');
            foreach ($this->AvailableThemes as $Theme => $Info) {
               $CurrentName = ArrayValue('Name', $Info, $Theme);
               if (
                  $CurrentName == $Name
                  && $Type == 'Theme'
               ) {
                  $Info['NewVersion'] = $NewVersion;
                  $this->AvailableThemes[$Theme] = $Info;
               }
            }
         }
      }
      
      if ($Session->ValidateTransientKey($TransientKey) && $ThemeFolder != '') {
         try {
            foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
               if ($ThemeInfo['Folder'] == $ThemeFolder) {
                  $Session->SetPreference('PreviewTheme', ''); // Clear out the preview
                  $ThemeManager->EnableTheme($ThemeName);
               }
            }
         } catch (Exception $e) {
            $this->Form->AddError(strip_tags($e->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('/settings/themes');

      }
      $this->Render();
   }
   
   public function PreviewTheme($ThemeFolder = '') {
      $this->Permission('Garden.Themes.Manage');
      $ThemeManager = new Gdn_ThemeManager();
      $this->AvailableThemes = $ThemeManager->AvailableThemes();
      $PreviewThemeName = '';
      $PreviewThemeFolder = $ThemeFolder;
      foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
         if ($ThemeInfo['Folder'] == $ThemeFolder)
            $PreviewThemeName = $ThemeName;
      }
      // If we failed to get the requested theme, default back to the one currently enabled
      if ($PreviewThemeName == '') {
         $this->ThemeName = $ThemeManager->EnabledTheme();
         foreach ($this->AvailableThemes as $ThemeName => $ThemeInfo) {
            if ($ThemeName == $PreviewThemeName)
               $PreviewThemeFolder = $ThemeInfo['Folder'];
         }
      }

      $Session = Gdn::Session();
      $Session->SetPreference(array('PreviewThemeName' => $PreviewThemeName, 'PreviewThemeFolder' => $PreviewThemeFolder));
      Redirect('/');
   }
   
   public function CancelPreview() {
      $Session = Gdn::Session();
      $Session->SetPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => ''));
      Redirect('settings/themes');
   }
   
   public function RemoveAddon($Type, $Name, $TransientKey = '') {
      switch ($Type) {
         case SettingsModule::TYPE_APPLICATION:
            $Manager = Gdn::Factory('ApplicationManager');
            $Enabled = 'EnabledApplications';
            $Remove  = 'RemoveApplication';
         break;
         case SettingsModule::TYPE_PLUGIN:
            $Manager = Gdn::Factory('PluginManager');
            $Enabled = 'EnabledPlugins';
            $Remove  = 'RemovePlugin';
         break;
      }
      
      if (Gdn::Session()->ValidateTransientKey($TransientKey)) {
         try {
            if (array_key_exists($Name, $Manager->$Enabled) === FALSE) {
               $Manager->$Remove($Name);
            }
         } catch (Exception $e) {
            $this->Form->AddError(strip_tags($e->getMessage()));
         }
      }
      if ($this->Form->ErrorCount() == 0)
         Redirect('/settings/plugins');
   }
}

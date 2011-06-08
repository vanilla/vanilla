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
class SettingsController extends DashboardController {
   
   public $Uses = array('Form', 'Database');
   public $ModuleSortContainer = 'Dashboard';

   /**
    *
    * @var Gdn_Form
    */
   public $Form;
   
   /**
    * Application management screen.
    */
   public function Applications($Filter = '', $ApplicationName = '', $TransientKey = '') {
      $this->AddJsFile('addons.js');
      $Session = Gdn::Session();
      $ApplicationName = $Session->ValidateTransientKey($TransientKey) ? $ApplicationName : '';
      if (!in_array($Filter, array('enabled', 'disabled')))
         $Filter = 'all';
         
      $this->Filter = $Filter;
      $this->Permission('Garden.Applications.Manage');
      $this->AddSideMenu('dashboard/settings/applications');

      $this->AddJsFile('applications.js');
      $this->Title(T('Applications'));
      
      $AuthenticatedPostBack = $this->Form->AuthenticatedPostBack();
      
      $ApplicationManager = new Gdn_ApplicationManager();
      $this->AvailableApplications = $ApplicationManager->AvailableVisibleApplications();
      $this->EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
      
      // Loop through all of the available visible apps and mark them if they have an update available
      // Retrieve the list of apps that require updates from the config file
      $RequiredUpdates = Gdn_Format::Unserialize(C('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Gdn_Format::ObjectAsArray($UpdateInfo);
               
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
         $this->EventArguments['ApplicationName'] = $ApplicationName;
         if (array_key_exists($ApplicationName, $this->EnabledApplications) === TRUE) {
            try {
               $ApplicationManager->DisableApplication($ApplicationName);
               Gdn_LibraryMap::ClearCache();
               $this->FireEvent('AfterDisableApplication');
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
               Gdn_LibraryMap::ClearCache();
               $this->Form->SetValidationResults($Validation->Results());
               
               $this->EventArguments['Validation'] = $Validation;
               $this->FireEvent('AfterEnableApplication');
            }
            
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('settings/applications/'.$this->Filter);
      }
      $this->Render();
   }

   protected function _BanFilter($Ban) {
      $BanModel = $this->_BanModel;
      $BanWhere = $BanModel->BanWhere($Ban);
      foreach ($BanWhere as $Name => $Value) {
         if (!in_array($Name, array('u.Admin', 'u.Deleted')))
            return "$Name $Value";
      }
   }
   
   /**
    * Banner management screen.
    */
   public function Banner() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/settings/banner');
      $this->Title(T('Banner'));
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Garden.Title'));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);

      // Get the current logo.
      $Logo = C('Garden.Logo');
      if ($Logo) {
         $Logo = ltrim($Logo, '/');
         // Fix the logo path.
         if (StringBeginsWith($Logo, 'uploads/'))
            $Logo = substr($Logo, strlen('uploads/'));
         $this->SetData('Logo', $Logo);
      }
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Title', 'Required');
         
         if ($this->Form->Save() !== FALSE) {
            $Upload = new Gdn_Upload();
            try {
               // Validate the upload
               $TmpImage = $Upload->ValidateUpload('Logo', FALSE);
               if ($TmpImage) {
                  // Generate the target image name
                  $TargetImage = $Upload->GenerateTargetName(PATH_ROOT . DS . 'uploads');
                  $ImageBaseName = pathinfo($TargetImage, PATHINFO_BASENAME);
                  
                  // Delete any previously uploaded images.
                  if ($Logo)
                     $Upload->Delete($Logo);
                  
                  // Save the uploaded image
                  $Parts = $Upload->SaveAs(
                     $TmpImage,
                     $ImageBaseName
                  );
                  $ImageBaseName = $Parts['SaveName'];
               }
            } catch (Exception $ex) {
               $this->Form->AddError($ex->getMessage());
            }
            // If there were no errors, save the path to the logo in the config
            if ($this->Form->ErrorCount() == 0 && $Upload->GetUploadedFileName() != '') {
               SaveToConfig('Garden.Logo', $ImageBaseName);
               $this->SetData('Logo', $ImageBaseName);
            }
            
            $this->InformMessage(T("Your settings have been saved."));
         }
      }
      
      $this->Render();      
   }

   public function Bans($Action = '', $Search = '', $Page = '', $ID = '') {
      $this->Permission('Garden.Moderation.Manage');
      $this->AddSideMenu();
      $this->Title(T('Ban List'));
      $this->AddJsFile('bans.js');

      list($Offset, $Limit) = OffsetLimit($Page, 20);

      $BanModel = new BanModel();
      $this->_BanModel = $BanModel;

      switch (strtolower($Action)) {
         case 'add':
         case 'edit':
            $this->Form->SetModel($BanModel);

            if ($this->Form->AuthenticatedPostBack()) {
               if ($ID)
                  $this->Form->SetFormValue('BanID', $ID);
               try {
                  // Save the ban.
                  $this->Form->Save();
               } catch (Exception $Ex) {
                  $this->Form->AddError($Ex);
               }
            } else {
               if ($ID)
               $this->Form->SetData($BanModel->GetID($ID));
            }
            $this->SetData('_BanTypes', array('IPAddress' => 'IP Address', 'Email' => 'Email', 'Name' => 'Name'));
            $this->View = 'Ban';
            break;
         case 'delete':
            $BanModel->Delete(array('BanID' => $ID));
            $this->View = 'BanDelete';
            break;
         default:
            $Bans = $BanModel->GetWhere(array(), 'BanType, BanValue', 'asc', $Limit, $Offset)->ResultArray();
            $this->SetData('Bans', $Bans);
            break;
      }

      $this->Render();
   }
   
   /**
    * Homepage management screen.
    */
   public function Homepage() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/settings/homepage');
      $this->Title(T('Homepage'));
      $this->AddJsFile('homepage.js');
      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Route = Gdn::Router()->GetRoute('DefaultController');
         $this->Form->SetData(array(
            'Target' => $this->Route['Destination']
         ));
      } else {
            Gdn::Router()->DeleteRoute('DefaultController');
            Gdn::Router()->SetRoute(
               'DefaultController',
               ArrayValue('Target', $this->Form->FormValues()),
               'Internal'
            );

            $this->InformMessage(T("The homepage was saved successfully."));
         }
      
      $this->Render();      
   }      

   /**
    * Outgoing Email management screen.
    */
   public function Email() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/settings/email');
      $this->AddJsFile('email.js');
      $this->Title(T('Outgoing Email'));
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Garden.Email.SupportName',
         'Garden.Email.SupportAddress',
         'Garden.Email.UseSmtp',
         'Garden.Email.SmtpHost',
         'Garden.Email.SmtpUser',
         'Garden.Email.SmtpPassword',
         'Garden.Email.SmtpPort',
         'Garden.Email.SmtpSecurity'
      ));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load the locales for the locale dropdown
      $Locale = Gdn::Locale();
      $AvailableLocales = $Locale->GetAvailableLocaleSources();
      $this->LocaleData = ArrayCombine($AvailableLocales, $AvailableLocales);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportName', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Email');
         
         // If changing locale, redefine locale sources:
         /*
         $NewLocale = $this->Form->GetFormValue('Garden.Locale', FALSE);
         if ($NewLocale !== FALSE && Gdn::Config('Garden.Locale') != $NewLocale) {
            $ApplicationManager = new Gdn_ApplicationManager();
            $Locale = Gdn::Locale();
            $Locale->Set($NewLocale, $ApplicationManager->EnabledApplicationFolders(), Gdn::PluginManager()->EnabledPluginFolders(), TRUE);
         }
         */
         
         if ($this->Form->Save() !== FALSE)
            $this->InformMessage(T("Your settings have been saved."));

      }
      
      $this->Render();      
   }      

   /**
    * Garden settings dashboard.
    */
   var $RequiredAdminPermissions = array();
   public function xIndex() {
      $this->AddJsFile('settings.js');
      $this->Title(T('Dashboard'));
         
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
      $this->Permission($this->RequiredAdminPermissions, FALSE);
      $this->AddSideMenu('dashboard/settings');

      $UserModel = Gdn::UserModel();
      
      // Load some data to display on the dashboard
      $this->BuzzData = array();
      // Get the number of users in the database
      $CountUsers = $UserModel->GetCountLike();
      $this->AddDefinition('CountUsers', $CountUsers);
      $this->BuzzData[T('Users')] = number_format($CountUsers);
      // Get the number of new users in the last day
      $this->BuzzData[T('New users in the last day')] = number_format($UserModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 day')))));
      // Get the number of new users in the last week
      $this->BuzzData[T('New users in the last week')] = number_format($UserModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 week')))));
      
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
      if (C('Garden.NoUpdateCheck'))
         return;

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
         $Plugins = Gdn::PluginManager()->AvailablePlugins();
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
         $this->AddDefinition('UpdateChecks', Gdn_Format::Serialize($UpdateData));
      }
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }

   public function Locales($Op = NULL, $LocaleKey = NULL, $TransientKey = NULL) {
      $this->Permission('Garden.Settings.Manage');

      $this->Title(T('Locales'));
      $this->AddSideMenu('dashboard/settings/locales');
      $this->AddJsFile('addons.js');

      $LocaleModel = new LocaleModel();

      // Get the available locale packs.
      $AvailableLocales = $LocaleModel->AvailableLocalePacks();

      // Get the enabled locale packs.
      $EnabledLocales = $LocaleModel->EnabledLocalePacks();

      // Check to enable/disable a locale.
      if (Gdn::Session()->ValidateTransientKey($TransientKey) || $this->Form->AuthenticatedPostBack()) {
         if ($Op) {
            $Refresh = FALSE;
            switch(strtolower($Op)) {
               case 'enable':
                  $Locale = GetValue($LocaleKey, $AvailableLocales);
                  if (!is_array($Locale)) {
                     $this->Form->AddError('@'.sprintf(T('The %s locale pack does not exist.'), htmlspecialchars($LocaleKey)), 'LocaleKey');
                  } elseif (!isset($Locale['Locale'])) {
                     $this->Form->AddError('ValidateRequired', 'Locale');
                  } else {
                     SaveToConfig("EnabledLocales.$LocaleKey", $Locale['Locale']);
                     $EnabledLocales[$LocaleKey] = $Locale['Locale'];
                     $Refresh = TRUE;
                  }
                  break;
               case 'disable':
                  RemoveFromConfig("EnabledLocales.$LocaleKey");
                  unset($EnabledLocales[$LocaleKey]);
                  $Refresh = TRUE;
                  break;
            }
         } elseif ($this->Form->IsPostBack()) {
            // Save the default locale.
            SaveToConfig('Garden.Locale', $this->Form->GetFormValue('Locale'));
            $Refresh = TRUE;
            $this->InformMessage(T("Your changes have been saved."));
         }

         if ($Refresh)
            Gdn::Locale()->Refresh();
      } elseif (!$this->Form->IsPostBack()) {
         $this->Form->SetFormValue('Locale', C('Garden.Locale', 'en-CA'));
      }

      // Check for the default locale warning.
      $DefaultLocale = C('Garden.Locale');
      if ($DefaultLocale != 'en-CA') {
         $LocaleFound = FALSE;
         $MatchingLocales = array();
         foreach ($AvailableLocales as $Key => $LocaleInfo) {
            $Locale = GetValue('Locale', $LocaleInfo);
            if ($Locale == $DefaultLocale)
               $MatchingLocales[] = GetValue('Name', $LocaleInfo, $Key);

            if (GetValue($Key, $EnabledLocales) == $DefaultLocale)
               $LocaleFound = TRUE;
               
         }
         $this->SetData('DefaultLocaleWarning', !$LocaleFound);
         $this->SetData('MatchingLocalePacks', htmlspecialchars(implode(', ', $MatchingLocales)));
      }
      
      $this->SetData('AvailableLocales', $AvailableLocales);
      $this->SetData('EnabledLocales', $EnabledLocales);
      $this->SetData('Locales', $LocaleModel->AvailableLocales());
      $this->Render();
   }
   
   public function Plugins($Filter = '', $PluginName = '', $TransientKey = '') {
      $this->AddJsFile('addons.js');
      $this->Title(T('Plugins'));
      
      $Session = Gdn::Session();
      $PluginName = $Session->ValidateTransientKey($TransientKey) ? $PluginName : '';
      if (!in_array($Filter, array('enabled', 'disabled')))
         $Filter = 'all';
         
      $this->Filter = $Filter;
      $this->Permission('Garden.Plugins.Manage');
      $this->AddSideMenu('dashboard/settings/plugins');
      
      // Retrieve all available plugins from the plugins directory
      $this->EnabledPlugins = Gdn::PluginManager()->EnabledPlugins();
      self::SortAddons($this->EnabledPlugins);
      $this->AvailablePlugins = Gdn::PluginManager()->AvailablePlugins();
      self::SortAddons($this->AvailablePlugins);
      
      // Loop through all of the available plugins and mark them if they have an update available
      // Retrieve the list of plugins that require updates from the config file
      $RequiredUpdates = Gdn_Format::Unserialize(Gdn::Config('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Gdn_Format::ObjectAsArray($UpdateInfo);
               
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
            $this->EventArguments['PluginName'] = $PluginName;
            if (array_key_exists($PluginName, $this->EnabledPlugins) === TRUE) {
               Gdn::PluginManager()->DisablePlugin($PluginName);
               Gdn_LibraryMap::ClearCache();
               $this->FireEvent('AfterDisablePlugin');
            } else {
               $Validation = new Gdn_Validation();
               if (!Gdn::PluginManager()->EnablePlugin($PluginName, $Validation))
                  $this->Form->SetValidationResults($Validation->Results());
               else
                  Gdn_LibraryMap::ClearCache();
               
               $this->EventArguments['Validation'] = $Validation;
               $this->FireEvent('AfterEnablePlugin');
            }
         } catch (Exception $e) {
            $this->Form->AddError(strip_tags($e->getMessage()));
         }
         if ($this->Form->ErrorCount() == 0)
            Redirect('/settings/plugins/'.$this->Filter);
      }
      $this->Render();
   }
   
   /**
    * Configuration of registration settings.
    */
   public function Registration($RedirectUrl = '') {
      $this->Permission('Garden.Registration.Manage');
		if(!C('Garden.Registration.Manage', TRUE))
			return Gdn::Dispatcher()->Dispatch('Default404');
      $this->AddSideMenu('dashboard/settings/registration');
      
      $this->AddJsFile('registration.js');
      $this->Title(T('Registration'));
      
      // Create a model to save configuration settings
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Garden.Registration.Method' => 'Captcha',
         'Garden.Registration.CaptchaPrivateKey',
         'Garden.Registration.CaptchaPublicKey',
         'Garden.Registration.InviteExpiration',
         'Garden.Registration.ConfirmEmail',
         'Garden.Registration.ConfirmEmailRole'
      ));
      
      // Set the model on the forms.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load roles with sign-in permission
      $RoleModel = new RoleModel();
      $this->RoleData = $RoleModel->GetByPermission('Garden.SignIn.Allow');
      $this->SetData('_Roles', ConsolidateArrayValuesByKey($this->RoleData->ResultArray(), 'RoleID', 'Name'));
      
      // Get the currently selected default roles
      // $this->ExistingRoleData = Gdn::Config('Garden.Registration.DefaultRoles');
      // if (is_array($this->ExistingRoleData) === FALSE)
      //    $this->ExistingRoleData = array();
         
      // Get currently selected InvitationOptions
      $this->ExistingRoleInvitations = Gdn::Config('Garden.Registration.InviteRoles');
      if (is_array($this->ExistingRoleInvitations) === FALSE)
         $this->ExistingRoleInvitations = array();
         
      // Get the currently selected Expiration Length
      $this->InviteExpiration = Gdn::Config('Garden.Registration.InviteExpiration', '');
      
      // Registration methods.
      $this->RegistrationMethods = array(
         // 'Closed' => "Registration is closed.",
         // 'Basic' => "The applicants are granted access immediately.",
         'Captcha' => "New users fill out a simple form and are granted access immediately.",
         'Approval' => "New users are reviewed and approved by an administrator (that's you!).",
         'Invitation' => "Existing members send invitations to new members.",
         'Connect' => "New users are only registered through SSO plugins."
      );

      // Options for how many invitations a role can send out per month.
      $this->InvitationOptions = array(
         '0' => T('None'),
         '1' => '1',
         '2' => '2',
         '5' => '5',
         '-1' => T('Unlimited')
      );
      
      // Options for when invitations should expire.
      $this->InviteExpirationOptions = array(
        '-1 week' => T('1 week after being sent'),
        '-2 weeks' => T('2 weeks after being sent'),
        '-1 month' => T('1 month after being sent'),
        'FALSE' => T('never')
      );
      
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($ConfigurationModel->Data);
      } else {   
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Registration.Method', 'Required');   
         // if($this->Form->GetValue('Garden.Registration.Method') != 'Closed')
         //    $ConfigurationModel->Validation->ApplyRule('Garden.Registration.DefaultRoles', 'RequiredArray');

         if ($this->Form->GetValue('Garden.Registration.ConfirmEmail'))
            $ConfigurationModel->Validation->ApplyRule('Garden.Registration.ConfirmEmailRole', 'Required');
         
         // Define the Garden.Registration.RoleInvitations setting based on the postback values
         $InvitationRoleIDs = $this->Form->GetValue('InvitationRoleID');
         $InvitationCounts = $this->Form->GetValue('InvitationCount');
         $this->ExistingRoleInvitations = ArrayCombine($InvitationRoleIDs, $InvitationCounts);
         $ConfigurationModel->ForceSetting('Garden.Registration.InviteRoles', $this->ExistingRoleInvitations);
         
         // Save!
         if ($this->Form->Save() !== FALSE) {
            $this->InformMessage(T("Your settings have been saved."));
            if ($RedirectUrl != '')
               $this->RedirectUrl = $RedirectUrl;
         }
      }
      
      $this->Render();
   }

   public static function SortAddons(&$Array, $Filter = TRUE) {
      // Make sure every addon has a name.
      foreach ($Array as $Key => $Value) {
         if ($Filter && GetValue('Hidden', $Value)) {
            unset($Array[$Key]);
            continue;
         }

         $Name = GetValue('Name', $Value, $Key);
         SetValue('Name', $Array[$Key], $Name);
      }
      uasort($Array, array('SettingsController', 'CompareAddonName'));
   }

   public static function CompareAddonName($A, $B) {
      return strcasecmp(GetValue('Name', $A), GetValue('Name', $B));
   }
   
   /**
    * Test and addon to see if there are any fatal errors during install.
    */
   public function TestAddon($AddonType = '', $AddonName = '', $TransientKey = '') {
      if (!in_array($AddonType, array('Plugin', 'Application', 'Theme', 'Locale')))
         $AddonType = 'Plugin';
         
      $Session = Gdn::Session();
      $AddonName = $Session->ValidateTransientKey($TransientKey) ? $AddonName : '';
      if ($AddonType == 'Locale') {
         $AddonManager = new LocaleModel();
         $TestMethod = 'TestLocale';
      } else {
         $AddonManagerName = $AddonType.'Manager';
         $TestMethod = 'Test'.$AddonType;
         $AddonManager = Gdn::Factory($AddonManagerName);
      }
      if ($AddonName != '') {
         $Validation = new Gdn_Validation();

         try {
            $AddonManager->$TestMethod($AddonName, $Validation);
         } catch (Exception $Ex) {
            if (Debug())
               throw $Ex;
            else {
               echo $Ex->getMessage();
               return;
            }
         }
      }
      
      ob_clean();
      echo 'Success';
   }

   public function ThemeOptions($Style = NULL) {
      $this->Permission('Garden.Themes.Manage');

      try {

         $this->AddJsFile('addons.js');
         $this->AddSideMenu('dashboard/settings/themeoptions');

         $ThemeManager = new Gdn_ThemeManager();
         $this->SetData('ThemeInfo', $ThemeManager->EnabledThemeInfo());

         if ($this->Form->IsPostBack()) {
            // Save the styles to the config.
            $StyleKey = $this->Form->GetFormValue('StyleKey');

            SaveToConfig(array(
               'Garden.ThemeOptions.Styles.Key' => $StyleKey,
               'Garden.ThemeOptions.Styles.Value' => $this->Data("ThemeInfo.Options.Styles.$StyleKey.Basename")));
            // Save the text to the locale.
            $Translations = array();
            foreach ($this->Data('ThemeInfo.Options.Text', array()) as $Key => $Default) {
               $Value = $this->Form->GetFormValue($this->Form->EscapeString('Text_'.$Key));
               $Translations['Theme_'.$Key] = $Value;
               //$this->Form->SetFormValue('Text_'.$Key, $Value);
            }
            if (count($Translations) > 0) {
               try {
                  Gdn::Locale()->SaveTranslations($Translations);
                  Gdn::Locale()->Refresh();
               } catch (Exception $Ex) {
                  $this->Form->AddError($Ex);
               }
            }

            $this->InformMessage(T("Your changes have been saved."));
         } elseif ($Style) {
            SaveToConfig(array(
               'Garden.ThemeOptions.Styles.Key' => $Style,
               'Garden.ThemeOptions.Styles.Value' => $this->Data("ThemeInfo.Options.Styles.$Style.Basename")));
         }

         $this->SetData('ThemeOptions', C('Garden.ThemeOptions'));
         $StyleKey = $this->Data('ThemeOptions.Styles.Key');

         if (!$this->Form->IsPostBack()) {
            foreach ($this->Data('ThemeInfo.Options.Text', array()) as $Key => $Options) {
               $Default = GetValue('Default', $Options, '');
               $Value = T('Theme_'.$Key, '#DEFAULT#');
               if ($Value === '#DEFAULT#')
                  $Value = $Default;

               $this->Form->SetFormValue($this->Form->EscapeString('Text_'.$Key), $Value);
            }
         }

         $this->SetData('ThemeFolder', $ThemeManager->EnabledTheme());
         $this->Title(T('Theme Options'));
         $this->Form->AddHidden('StyleKey', $StyleKey);
      } catch (Exception $Ex) {
         $this->Form->AddError($Ex);
      }

      $this->Render();
   }

   /**
    * Theme management screen.
    */
   public function Themes($ThemeName = '', $TransientKey = '') {
      $this->AddJsFile('addons.js');
      $this->SetData('Title', T('Themes'));
         
      $this->Permission('Garden.Themes.Manage');
      $this->AddSideMenu('dashboard/settings/themes');
      
      $ThemeInfo = Gdn::ThemeManager()->EnabledThemeInfo(TRUE);
      $this->SetData('EnabledThemeFolder', GetValue('Folder', $ThemeInfo));
      $this->SetData('EnabledTheme', Gdn::ThemeManager()->EnabledThemeInfo());
      $this->SetData('EnabledThemeName', GetValue('Name', $ThemeInfo, GetValue('Index', $ThemeInfo)));
      
      // Loop through all of the available themes and mark them if they have an update available
      // Retrieve the list of themes that require updates from the config file
      $RequiredUpdates = Gdn_Format::Unserialize(Gdn::Config('Garden.RequiredUpdates', ''));
      if (is_array($RequiredUpdates)) {
         foreach ($RequiredUpdates as $UpdateInfo) {
            if (is_object($UpdateInfo))
               $UpdateInfo = Gdn_Format::ObjectAsArray($UpdateInfo);
               
            $NewVersion = ArrayValue('Version', $UpdateInfo, '');
            $Name = ArrayValue('Name', $UpdateInfo, '');
            $Type = ArrayValue('Type', $UpdateInfo, '');
            foreach (Gdn::ThemeManager()->AvailableThemes() as $Theme => $Info) {
               $CurrentName = ArrayValue('Name', $Info, $Theme);
               if (
                  $CurrentName == $Name
                  && $Type == 'Theme'
               ) {
                  $Info['NewVersion'] = $NewVersion;
                  $AvailableThemes[$Theme] = $Info;
               }
            }
         }
      }
      $this->SetData('AvailableThemes', Gdn::ThemeManager()->AvailableThemes());
      
      if (Gdn::Session()->ValidateTransientKey($TransientKey) && $ThemeName != '') {
         try {
            $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo($ThemeName);
            if ($ThemeInfo === FALSE)
               throw new Exception(sprintf(T("Could not find a theme identified by '%s'"), $ThemeName));
            
            Gdn::Session()->SetPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => '')); // Clear out the preview
            Gdn::ThemeManager()->EnableTheme($ThemeName);
            $this->EventArguments['ThemeName'] = $ThemeName;
            $this->EventArguments['ThemeInfo'] = $ThemeInfo;
            $this->FireEvent('AfterEnableTheme');
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         
         if ($this->Form->ErrorCount() == 0)
            Redirect('/settings/themes');

      }
      $this->Render();
   }
   
   public function PreviewTheme($ThemeName = '') {
      $this->Permission('Garden.Themes.Manage');
      $ThemeInfo = Gdn::ThemeManager()->GetThemeInfo($ThemeName);
      
      $PreviewThemeName = $ThemeName;
      $PreviewThemeFolder = GetValue('Folder', $ThemeInfo);
      
      // If we failed to get the requested theme, cancel preview
      if ($ThemeInfo === FALSE) {
         $PreviewThemeName = '';
         $PreviewThemeFolder = '';
      }
      
      Gdn::Session()->SetPreference(array('PreviewThemeName' => $PreviewThemeName, 'PreviewThemeFolder' => $PreviewThemeFolder));
      Redirect('/');
   }
   
   public function CancelPreview() {
      $Session = Gdn::Session();
      $Session->SetPreference(array('PreviewThemeName' => '', 'PreviewThemeFolder' => ''));
      Redirect('settings/themes');
   }
   
   public function RemoveAddon($Type, $Name, $TransientKey = '') {
      $RequiredPermission = 'Undefined';
      switch ($Type) {
         case SettingsModule::TYPE_APPLICATION:
            $Manager = Gdn::Factory('ApplicationManager');
            $Enabled = 'EnabledApplications';
            $Remove  = 'RemoveApplication';
            $RequiredPermission = 'Garden.Applications.Manage';
         break;
         case SettingsModule::TYPE_PLUGIN:
            $Manager = Gdn::Factory('PluginManager');
            $Enabled = 'EnabledPlugins';
            $Remove  = 'RemovePlugin';
            $RequiredPermission = 'Garden.Plugins.Manage';
         break;
      }
      
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey) && $Session->CheckPermission($RequiredPermission)) {
         try {
            if (array_key_exists($Name, $Manager->$Enabled()) === FALSE) {
               $Manager->$Remove($Name);
            }
         } catch (Exception $e) {
            $this->Form->AddError(strip_tags($e->getMessage()));
         }
      }
      if ($this->Form->ErrorCount() == 0)
         Redirect('/settings/plugins');
   }
   
   public function RemoveLogo($TransientKey = '') {
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey) && $Session->CheckPermission('Garden.Themes.Manage')) {
         $Logo = C('Garden.Logo', '');
         RemoveFromConfig('Garden.Logo');
         @unlink(PATH_ROOT . DS . $Logo);
      }

      Redirect('/settings/banner');
   }
   
   public function GettingStarted() {
      $this->SetData('Title', T('Getting Started'));
      $this->AddSideMenu('dashboard/settings/gettingstarted');
      $this->TextEnterEmails = T('Type email addresses separated by commas here...');
      
      if ($this->Form->AuthenticatedPostBack()) {
         $Message = $this->Form->GetFormValue('InvitationMessage');
         $Message .= "\n\n".Gdn::Request()->Url('/', TRUE);
         $Message = trim($Message);
         $Recipients = $this->Form->GetFormValue('Recipients');
         if ($Recipients == $this->TextEnterEmails)
            $Recipients = '';
            
         $Recipients = explode(',', $Recipients);
         $CountRecipients = 0;
         foreach ($Recipients as $Recipient) {
            if (trim($Recipient) != '') {
               $CountRecipients++;
               if (!ValidateEmail($Recipient))
                  $this->Form->AddError(sprintf(T('%s is not a valid email address'), $Recipient));
            }
         }
         if ($CountRecipients == 0)
            $this->Form->AddError(T('You must provide at least one recipient'));
         if ($this->Form->ErrorCount() == 0) {
            $Email = new Gdn_Email();
            $Email->Subject(T('Check out my new community!'));
            $Email->Message($Message);
            foreach ($Recipients as $Recipient) {
               if (trim($Recipient) != '') {
                  $Email->To($Recipient);
                  try {
                     $Email->Send();
                  } catch (Exception $ex) {
                     $this->Form->AddError($ex);
                  }
               }
            }
         }
         if ($this->Form->ErrorCount() == 0)
            $this->InformMessage(T('Your invitations were sent successfully.'));
      }
      
      $this->Render();
   }
   
}

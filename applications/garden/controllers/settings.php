<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * Garden Settings Controller
 */
class SettingsController extends GardenController {
   
   public $Uses = array('Form', 'Database');
   
   /**
    * Application management screen.
    */
   public function Applications($Action = '', $ApplicationName = '', $TransientKey = '') {
      $this->Permission('Garden.Applications.Manage');
      $this->AddSideMenu('garden/settings/applications');

      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/applications.js');
         
      $Session = Gdn::Session();
      $AuthenticatedPostBack = $this->Form->AuthenticatedPostBack();
      // Todd: Is this part necessary?
      if (!$Session->ValidateTransientKey($TransientKey)) {
      // if(!$AuthenticatedPostBack) {
         $Action = '';
         $ApplicationName = '';
      } else {
         $AuthenticatedPostBack = TRUE;
      }
      $FinalRedirect = FALSE;   
      $SetupRender = FALSE;
      
      $ApplicationManager = new Gdn_ApplicationManager();
      $this->AvailableApplications = $ApplicationManager->AvailableApplications();
      
      // Check the update server for updates to these applications
      $this->UpdateManager = new Gdn_UpdateManager();
      // TODO: FIX UP THE PHONE-HOME CODE - AJAX, PERHAPS?
      // $this->CurrentVersions = $this->UpdateManager->Check(ADDON_TYPE_APPLICATION, array_keys($this->AvailableApplications));
      
      if ($AuthenticatedPostBack) {
         $ApplicationName = $this->Form->GetValue('ApplicationName', $ApplicationName);
         if ($ApplicationName != '') {
            if (array_key_exists($ApplicationName, $this->EnabledApplications) === TRUE) {
               try {
                  $ApplicationManager->DisableApplication($ApplicationName);
               } catch (Exception $e) {
                  $this->Form->AddError(strip_tags($e->getMessage()));
               }
               $FinalRedirect = TRUE;
            } else {
               if ($Action == '') {
                  // 1. Make sure that the application's requirements are met
                  try {
                     $ApplicationManager->CheckRequirements($ApplicationName);
                  } catch (Exception $e) {
                     $this->Form->AddError(strip_tags($e->getMessage()));
                  }
                  if ($this->Form->ErrorCount() == 0) {
                     // 2. Register any specified permissions
                     $Validation = new Gdn_Validation();
                     $ApplicationManager->RegisterPermissions($ApplicationName, $Validation);
                     if (count($Validation->Results()) == 0) {
                        if (!$ApplicationManager->ApplicationSetup($ApplicationName, $this, $Validation, TRUE)) {
                           $SetupUrl = '/garden/settings/applications/setup/'.$ApplicationName.'/'.$Session->TransientKey();
                           if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
                              Redirect($SetupUrl);
                           } else {
                              $this->SetJson('Go', Url($SetupUrl)); // <---- THIS IS WHAT MAKES IT GET PICKED UP BY THE POPUP
                              $this->Database->CloseConnection();
                              exit(json_encode($this->GetJson()));
                           }
                        } else {
                           $FinalRedirect = TRUE;
                        }
                     }
                  }
                  $this->Form->SetValidationResults($Validation->Results());
               } elseif ($Action == 'setup') {
                  // 3. Run the setup method of the application
                  $SetupRender = TRUE;
                  $Validation = new Gdn_Validation();
                  if ($ApplicationManager->ApplicationSetup($ApplicationName, $this, $Validation)) {
                     $SetupRender = FALSE;
                     $FinalRedirect = TRUE;
                  }
               }
            }
            if ($FinalRedirect && $this->Form->ErrorCount() == 0) {
               $this->StatusMessage = Gdn::Translate('Finalizing changes...');
               $this->RedirectUrl = Url('/garden/settings/applications/');
            }
         }
      }
      if (!$SetupRender)
         $this->Render();

   }
   
   /**
    * Garden management screen.
    */
   public function Configure() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('garden/settings/configure');
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel('Configuration', PATH_CONF . DS . 'config.php', $Validation);
      $ConfigurationModel->SetField(array('Garden.Locale', 'Garden.Title', 'Garden.RewriteUrls'));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load the locales for the locale dropdown
      $Locale = Gdn::Locale();
      $AvailableLocales = $Locale->GetAvailableLocaleSources();
      $this->LocaleData = array_combine($AvailableLocales, $AvailableLocales);
      
      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Locale', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Title', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.RewriteUrls', 'Boolean');
         
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
    * Garden management screen.
    */
   public function Email() {
      $this->Permission('Garden.Email.Manage');
      $this->AddSideMenu('garden/settings/email');
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/email.js');      
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel('Configuration', PATH_CONF . DS . 'config.php', $Validation);
      $ConfigurationModel->SetField(array(
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
      
      // If seeing the form for the first time...
      if (!$this->Form->AuthenticatedPostBack()) {
         // Apply the config settings to the form.
         $this->Form->SetData($ConfigurationModel->Data);
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportName', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Garden.Email.SupportAddress', 'Email');
         if ($this->Form->Save() !== FALSE)
            $this->StatusMessage = Translate("Your settings have been saved.");

      }
      
      $this->Render();      
   }      
   
   /**
    * Garden settings dashboard.
    */
   var $RequiredAdminPermissions = array();
   public function Index() {
      $this->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $this->RequiredAdminPermissions[] = 'Garden.Email.Manage';
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
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }
   
   public function Plugins() {
      $this->Permission('Garden.Plugins.Manage');
      $this->AddSideMenu('garden/settings/plugins');
      
      // Retrieve all available plugins from the plugins directory
      $PluginManager = Gdn::Factory('PluginManager');
      $this->EnabledPlugins = $PluginManager->EnabledPlugins;
      $this->AvailablePlugins = $PluginManager->AvailablePlugins();
      
      // Check the update server for updates to these plugins
      $this->UpdateManager = new Gdn_UpdateManager();
      // TODO: FIX UP THE PHONE-HOME CODE - AJAX, PERHAPS?
      // $this->CurrentVersions = $this->UpdateManager->Check(ADDON_TYPE_PLUGIN, array_keys($this->AvailablePlugins));
      
      if ($this->Form->AuthenticatedPostBack()) {
         $PluginName = $this->Form->GetValue('PluginName', '');
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
            if ($this->Form->ErrorCount() == 0) {
               $this->StatusMessage = Gdn::Translate('Finalizing changes...');
               $this->RedirectUrl = Url($this->SelfUrl);
            }
         }
      }
      $this->Render();
   }
   
   /**
    * Configuration of registration settings.
    */
   public function Registration($RedirectUrl = '') {
      $this->Permission('Garden.Registration.Manage');
      $this->AddSideMenu('garden/settings/registration');
      
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/registration.js');
      
      // Create a model to save configuration settings
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel('Configuration', PATH_CONF . DS . 'config.php', $Validation);
      $ConfigurationModel->SetField(array('Garden.Registration.Method', 'Garden.Registration.DefaultRoles', 'Garden.Registration.CaptchaPrivateKey', 'Garden.Registration.CaptchaPublicKey', 'Garden.Registration.InviteExpiration'));
      
      // Define some validation rules for the fields being saved
      $ConfigurationModel->Validation->ApplyRule('Garden.Registration.Method', 'Required');
      $ConfigurationModel->Validation->ApplyRule('Garden.Registration.DefaultRoles', 'RequiredArray');

      // Set the model on the forms.
      $this->Form->SetModel($ConfigurationModel);
      
      // Load roles with sign-in permission
      $RoleModel = new RoleModel();
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
         // Define the Garden.Registration.RoleInvitations setting based on the postback values
         $InvitationRoleIDs = $this->Form->GetValue('InvitationRoleID');
         $InvitationCounts = $this->Form->GetValue('InvitationCount');
         $this->ExistingRoleInvitations = array_combine($InvitationRoleIDs, $InvitationCounts);
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
   public function Themes($Preview = '') {
      $this->Permission('Garden.Themes.Manage');
      $this->AddSideMenu('garden/settings/themes');
      
      if ($Preview != '')
         $this->Theme = $Preview;
         
      $ThemeManager = new Gdn_ThemeManager();
      $this->AvailableThemes = $ThemeManager->AvailableThemes();
      $this->EnabledTheme = $ThemeManager->EnabledTheme();
      
      if ($this->Form->AuthenticatedPostBack()) {
         $ThemeName = $this->Form->GetValue('ThemeName', '');
         if ($ThemeName != '') {
            try {
               if (array_key_exists($ThemeName, $this->AvailableThemes) === TRUE) {
                  $ThemeManager->EnableTheme($ThemeName);
               }
            } catch (Exception $e) {
               $this->Form->AddError(strip_tags($e->getMessage()));
            }
            if ($this->Form->ErrorCount() == 0) {
               $this->StatusMessage = Gdn::Translate('Finalizing changes...');
               $this->RedirectUrl = Url($this->SelfUrl);
            }
         }
      }
      $this->Render();
   }   
}
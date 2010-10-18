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
class AuthenticationController extends DashboardController {

   public $Uses = array('Form', 'Database');
   public $ModuleSortContainer = 'Dashboard';

   /**
    *
    * @var Gdn_Form
    */
   public $Form;
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/authentication');
         
      $this->EnableSlicing($this);
      
      $Authenticators = Gdn::Authenticator()->GetAvailable();
      $this->ChooserList = array();
      $this->ConfigureList = array();
      foreach ($Authenticators as $AuthAlias => $AuthConfig) {
         $this->ChooserList[$AuthAlias] = $AuthConfig['Name'];
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthAlias);
         $ConfigURL = (is_a($Authenticator, "Gdn_Authenticator") && method_exists($Authenticator, 'AuthenticatorConfiguration')) ? $Authenticator->AuthenticatorConfiguration($this) : FALSE;
         $this->ConfigureList[$AuthAlias] = $ConfigURL;
      }
      $this->CurrentAuthenticationAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();
   }

   public function Index($AuthenticationSchemeAlias = NULL) {
      $this->View = 'choose';
      $this->Choose($AuthenticationSchemeAlias);
   }
   
   public function Choose($AuthenticationSchemeAlias = NULL) {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/authentication');
      $this->Title(T('Authentication'));
      $this->AddCssFile('authentication.css');
      
      $PreFocusAuthenticationScheme = NULL;
      if (!is_null($AuthenticationSchemeAlias))
         $PreFocusAuthenticationScheme = $AuthenticationSchemeAlias;
      
      if ($this->Form->AuthenticatedPostback()) {
         $NewAuthSchemeAlias = $this->Form->GetValue('Garden.Authentication.Chooser');
         $AuthenticatorInfo = Gdn::Authenticator()->GetAuthenticatorInfo($NewAuthSchemeAlias);
         if ($AuthenticatorInfo !== FALSE) {
            $CurrentAuthenticatorAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();
            
            // Disable current
            $AuthenticatorDisableEvent = "DisableAuthenticator".ucfirst($CurrentAuthenticatorAlias);
            $this->FireEvent($AuthenticatorDisableEvent);
            
            // Enable new
            $AuthenticatorEnableEvent = "EnableAuthenticator".ucfirst($NewAuthSchemeAlias);
            $this->FireEvent($AuthenticatorEnableEvent);
            
            $PreFocusAuthenticationScheme = $NewAuthSchemeAlias;
            $this->CurrentAuthenticationAlias = Gdn::Authenticator()->AuthenticateWith('default')->GetAuthenticationSchemeAlias();
         }
      }
      
      $this->SetData('AuthenticationConfigureList', $this->ConfigureList);
      $this->SetData('PreFocusAuthenticationScheme', $PreFocusAuthenticationScheme);
      $this->Render();
   }

   public function Configure($AuthenticationSchemeAlias = NULL) {
      $Message = T("Please choose an authenticator to configure.");
      if (!is_null($AuthenticationSchemeAlias)) {
         $AuthenticatorInfo = Gdn::Authenticator()->GetAuthenticatorInfo($AuthenticationSchemeAlias);
         if ($AuthenticatorInfo !== FALSE) {
            $this->AuthenticatorChoice = $AuthenticationSchemeAlias;
            if (array_key_exists($AuthenticationSchemeAlias, $this->ConfigureList) && $this->ConfigureList[$AuthenticationSchemeAlias] !== FALSE) {
               echo Gdn::Slice($this->ConfigureList[$AuthenticationSchemeAlias]);
               return;
            } else
               $Message = sprintf(T("The %s Authenticator does not have any custom configuration options."),$AuthenticatorInfo['Name']);
         }
      }
      
      $this->SetData('ConfigureMessage', $Message);
      $this->Render();
   }
   
}
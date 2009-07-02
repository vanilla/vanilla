<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class EntryController extends GardenController {
   
   // Make sure the database class is loaded (class.controller.php takes care of this).
   // Note: The User refers to the UserModel because it is being provided by Gdn.
   public $Uses = array('Database', 'Form', 'Session', 'Gdn_UserModel');
   
   public function Index() {
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/entry.js');
         
      // Define gender dropdown options (for registration)
      $this->GenderOptions = array(
         'm' => Gdn::Translate('Male'),
         'f' => Gdn::Translate('Female')
      );
      $this->InvitationCode = $this->Form->GetValue('InvitationCode');
      if ($this->_RegistrationView() == 'RegisterCaptcha') {
         include(CombinePaths(array(PATH_LIBRARY, 'vendors/recaptcha', 'functions.recaptchalib.php')));
      }
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', ''));
      $this->Render();
   }

   /// <summary>
   /// This is a good example of how to use the form, model, and validator to
   /// validate a form that does use the model, but doesn't save data to the
   /// model.
   /// </summary>
   public function SignIn() {
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/entry.js');
         
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', 'test'));
      // If the form has been posted back...
      if ($this->Form->IsPostBack() === TRUE) {
         // If there were no errors...
         if ($this->Form->ValidateModel() == 0) {
            // Attempt to authenticate...
            $Authenticator = Gdn::Authenticator();
            $AuthenticatedUserID = $Authenticator->Authenticate($this->Form->FormValues());
            
            /*$AuthenticatedUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE),
               $this->Form->GetValue('ClientHour', ''));*/
            
            if ($AuthenticatedUserID < 0) {
               $this->Form->AddError('ErrorPermission');
            } else if ($AuthenticatedUserID == 0) {
               $this->Form->AddError('ErrorCredentials');
            } else {
               // AddActivity($AuthenticatedUserID, 'SignIn');
               $this->FireEvent('Authenticated');
               $Route = $this->RedirectTo();
               if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                  $this->RedirectUrl = Url($Route);
               } else {
                  if ($Route !== FALSE)
                     Redirect($Route);
               }
            }
         }
      }
      $this->Render();
   }
   
   /// <summary>
   /// Calls the appropriate registration method based on the configuration setting.
   /// </summary>
   public function Register($InvitationCode = '') {
      $this->Form->SetModel($this->UserModel);

      // Define gender dropdown options
      $this->GenderOptions = array(
         'm' => Gdn::Translate('Male'),
         'f' => Gdn::Translate('Female')
      );

      // Make sure that the hour offset for new users gets defined when their account is created
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/entry.js');
         
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', 'test'));

      $RegistrationMethod = $this->_RegistrationView();
      $this->View = $RegistrationMethod;
      $this->$RegistrationMethod($InvitationCode);
   }
   
   protected function _RegistrationView() {
      $RegistrationMethod = Gdn::Config('Garden.Registration.Method');
      if (!in_array($RegistrationMethod, array('Closed', 'Basic','Captcha','Approval','Invitation')))
         $RegistrationMethod = 'Basic';
         
      return 'Register'.$RegistrationMethod;
   }
   
   private function RegisterApproval() {
      // If the form has been posted back...
      if ($this->Form->IsPostBack()) {
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', 'Username can only contain letters, numbers, and underscores.');
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         $this->UserModel->Validation->ApplyRule('DiscoveryText', 'Required', 'Tell us why you want to join!');
         $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForApproval($this->Form->FormValues()))
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         else
            $this->View = "RegisterThanks"; // Tell the user their application will be reviewed by an administrator.
      }
      $this->Render();
   }
   
   private function RegisterBasic() {
      if ($this->Form->IsPostBack() === TRUE) {
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', 'Username can only contain letters, numbers, and underscores.');
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForBasic($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator();
            $AuthUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE)
            );
            
            /// ... and redirect them appropriately
            $Route = $this->RedirectTo();
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE)
                  Redirect($Route);
            }
         }
      }
      $this->Render();
   }
   
   private function RegisterCaptcha() {
      include(CombinePaths(array(PATH_LIBRARY, 'vendors/recaptcha', 'functions.recaptchalib.php')));
      if ($this->Form->IsPostBack() === TRUE) {
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', 'Username can only contain letters, numbers, and underscores.');
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForBasic($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            if($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->_DeliveryType = DELIVERY_TYPE_MESSAGE;
            }
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator();
            $AuthUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE)
            );
            
            /// ... and redirect them appropriately
            $Route = $this->RedirectTo();
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE)
                  Redirect($Route);
            }
         }
      }
      $this->Render();
   }
   
   private function RegisterClosed() {
      $this->Render();
   }
   
   private function RegisterInvitation($InvitationCode) {
      if ($this->Form->IsPostBack() === TRUE) {
         $this->InvitationCode = $this->Form->GetValue('InvitationCode');
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', 'Username can only contain letters, numbers, and underscores.');
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForInvite($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator();
            $AuthUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE));
            
            /// ... and redirect them appropriately
            $Route = $this->RedirectTo();
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE)
                  Redirect($Route);
            }
         }
      } else {
         $this->InvitationCode = $InvitationCode;
      }
      $this->Render();      
   }
   
   public function PasswordRequest() {
      $this->Form->SetModel($this->UserModel);
      if (
         $this->Form->IsPostBack() === TRUE
         && $this->Form->ValidateModel() == 0)
      {
         try {
            $this->UserModel->PasswordRequest($this->Form->GetFormValue('Email', ''));
         } catch (Exception $ex) {
            $this->Form->AddError($ex->getMessage());
         }
         if ($this->Form->ErrorCount() == 0) {
            $this->Form->AddError('Success!');
            $this->View = 'PasswordRequestSent';
         }
      } else {
         $this->Form->AddError('That email address was not found.');
      }
      $this->Render();
   }

   public function PasswordReset($UserID = '', $PasswordResetKey = '') {
      if (!is_numeric($UserID)
          || $PasswordResetKey == ''
          || $this->UserModel->GetAttribute($UserID, 'PasswordResetKey', '') != $PasswordResetKey
         ) $this->Form->AddError('Failed to authenticate your password reset request. Try using the reset request form again.');
      
      if ($this->Form->ErrorCount() == 0
         && $this->Form->IsPostBack() === TRUE
      ) {
         $Password = $this->Form->GetFormValue('Password', '');
         $Confirm = $this->Form->GetFormValue('Confirm', '');
         if ($Password == '')
            $this->Form->AddError('Your new password is invalid');
         else if ($Password != $Confirm)
            $this->Form->AddError('Your passwords did not match.');

         if ($this->Form->ErrorCount() == 0) {
            $User = $this->UserModel->PasswordReset($UserID, $Password);
            $Authenticator = Gdn::Authenticator();
            $Authenticator->Authenticate($User->Name, $Password, FALSE);
            $this->StatusMessage = Gdn::Translate('Password saved. Signing you in now...');
            $this->RedirectUrl = Url('/');
         }
      }
      $this->Render();
   }

   public function Leave($TransientKey = '') {
      // Only sign the user out if this is an authenticated postback!
      $Session = Gdn::Session();
      $this->Leaving = FALSE;
      if ($Session->ValidateTransientKey($TransientKey)) {
         $Authenticator = Gdn::Authenticator();
         $Authenticator->DeAuthenticate();
         $this->Leaving = TRUE;
         $this->RedirectUrl = Url('/entry');
      }
      $this->Render();
   }
   
   private function RedirectTo() {
      $IncomingTarget = $this->Form->GetValue('Target', '');
      return $IncomingTarget == '' ? ArrayValueI('DefaultController', $this->Routes) : $IncomingTarget;
   }
   
}
<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class EntryController extends Gdn_Controller {
   
   // Make sure the database class is loaded (class.controller.php takes care of this).
   // Note: The User refers to the UserModel because it is being provided by Gdn.
   public $Uses = array('Database', 'Form', 'Session', 'Gdn_UserModel');
   
   public function Index() {
      $this->View = 'signin';
      $this->SignIn();
   }
   
   public function Handshake() {
      $this->AddJsFile('entry.js');
      
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', '/'));
      
      $Target = GetIncomingValue('Target', '/');
      
      if ($this->Form->IsPostBack() === TRUE) {
         $FormValues = $this->Form->FormValues();
         if (ArrayValue('NewAccount', $FormValues)) {
            // Try and synchronize the user with the new username/email.
            $FormValues['Name'] = $FormValues['NewName'];
            $FormValues['Email'] = $FormValues['NewEmail'];
            $UserID = $this->UserModel->Synchronize($FormValues['UniqueID'], $FormValues);
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // Try and sign the user in.
            $Password = new Gdn_PasswordAuthenticator();
            $UserID = $Password->Authenticate(array('Email' => ArrayValue('SignInEmail', $FormValues, ''), 'Password' => ArrayValue('SignInPassword', $FormValues, '')));
            
            if ($UserID < 0) {
               $this->Form->AddError('ErrorPermission');
            } else if ($UserID == 0) {
               $this->Form->AddError('ErrorCredentials');
            }
            
            if($UserID) {
               $Data = $FormValues;
               $Data['UserID'] = $UserID;
               $Data['Email'] = ArrayValue('SignInEmail', $FormValues, '');
               $this->UserModel->Synchronize(ArrayValue('UniqueID', $FormValues, ''), $Data);
            }
         }
         
         if($UserID) {
            $Authenticator = Gdn::Authenticator();
            // The user has been created successfully, so sign in now
            $AuthUserID = $Authenticator->Authenticate(array('UserID' => $UserID));
            
            /// ... and redirect them appropriately
            $Route = $this->RedirectTo();
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE)
                  Redirect($Route);
            }
         } else {
            // Add the hidden inputs back into the form.
            foreach($FormValues as $Key => $Value) {
               if(in_array($Key, array('UniqueID', 'DateOfBirth', 'HourOffset', 'Gender', 'Name', 'Email')))
                  $this->Form->AddHidden($Key, $Value);
            }
         }
      } else {
         $Authenticator = Gdn::Authenticator();
         
         // Clear out the authentication and try and get the authentication again.
         $Id = $Authenticator->GetIdentity(TRUE);
         if($Id > 0) {
            // The user is signed in so we can just go back to the homepage.
            Redirect($Target);
         }
         
         if ($Authenticator->State() == Gdn_HandshakeAuthenticator::SignedOut) {
            // Clear out the authentication so it will fetch when we come back here.
            $Authenticator->SetIdentity(NULL);
            // Once signed in, we need to come back here to make sure there was no problem with the handshake.
            $Target = Url('/entry/handshake/?Target='.urlencode($Target), TRUE);
            // echo $Target;
            // Redirect to the external server to sign in.
            $SignInUrl = $Authenticator->RemoteSignInUrl($Target);
            Redirect($SignInUrl);
         }
         
         // There was a handshake error so we need to allow the user to fix the problems.
         $HandshakeData = $Authenticator->GetHandshakeData();
         
         // Check to see if there is a problem with the handshake.
         // $this->UserModel->ValidateUniqueFields($HandshakeData['Name'], $HandshakeData['Email']);
         // $ValidationResults = $this->UserModel->ValidationResults();
         // $this->Form->SetValidationResults($ValidationResults);
         
         // Set the defaults for a new user.
         $this->Form->SetFormValue('NewName', $HandshakeData['Name']);
         $this->Form->SetFormValue('NewEmail', $HandshakeData['Email']);
         
         // Set the default for the login.
         $this->Form->SetFormValue('SignInEmail', $HandshakeData['Email']);
         $this->Form->SetFormValue('Handshake', 'NEW');
         
         // Add the handshake data as hidden fields.
         foreach($HandshakeData as $Key => $Value) {
            $this->Form->AddHidden($Key, $Value);
         }
         
      }
      
      $this->SetData('Name', $this->Form->HiddenInputs['Name']);
      $this->SetData('Email', $this->Form->HiddenInputs['Email']);
      
      $this->Render();
   }

   /**
    * This is a good example of how to use the form, model, and validator to
    * validate a form that does use the model, but doesn't save data to the
    * model.
    */
   public function SignIn() {
      $this->AddJsFile('entry.js');
         
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', ''));
      // If the form has been posted back...
      if ($this->Form->IsPostBack() === TRUE) {
         // If there were no errors...
         if ($this->Form->ValidateModel() == 0) {
            // Attempt to authenticate...
            $Authenticator = Gdn::Authenticator();
            $AuthenticatedUserID = $Authenticator->Authenticate($this->Form->FormValues());

            if ($AuthenticatedUserID < 0) {
               $this->Form->AddError('ErrorPermission');
            } else if ($AuthenticatedUserID == 0) {
               $this->Form->AddError('ErrorCredentials');
            } else {
               // AddActivity($AuthenticatedUserID, 'SignIn');
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
   
   /**
    * Calls the appropriate registration method based on the configuration setting.
    */
   public function Register($InvitationCode = '') {
      $this->Form->SetModel($this->UserModel);

      // Define gender dropdown options
      $this->GenderOptions = array(
         'm' => Gdn::Translate('Male'),
         'f' => Gdn::Translate('Female')
      );

      // Make sure that the hour offset for new users gets defined when their account is created
      $this->AddJsFile('entry.js');
         
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', ''));

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
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
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
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForBasic($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator();
            $AuthUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE)
            );
            
            // ... and redirect them appropriately
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
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
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
            
            // ... and redirect them appropriately
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
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForInvite($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator();
            $AuthUserID = $Authenticator->Authenticate($this->Form->GetValue('Name'),
               $this->Form->GetValue('Password'),
               $this->Form->GetValue('RememberMe', FALSE));
            
            // ... and redirect them appropriately
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
            $Authenticator->Authenticate(array('Email' => $User->Email, 'Password' => $Password, 'RememberMe' => FALSE));
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
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('js/library/jquery.js');
      $this->AddJsFile('js/library/jquery.livequery.js');
      $this->AddJsFile('js/library/jquery.form.js');
      $this->AddJsFile('js/library/jquery.popup.js');
      $this->AddJsFile('js/library/jquery.menu.js');
      $this->AddJsFile('js/library/jquery.gardenhandleajaxform.js');
      $this->AddJsFile('js/global.js');
      
      $this->AddCssFile('style.css');
      parent::Initialize();
   }

   
}
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
   public $Uses = array('Database', 'Form', 'UserModel');
	const UsernameError = 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.';
   
   public function Auth($AuthenticationSchemeAlias = 'default') {
      try {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith('default');
      }
      
      // Set up controller
      $this->View = 'auth/'.$Authenticator->GetAuthenticationSchemeAlias();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', ''));
   
      // Import authenticator data source
      switch ($Authenticator->DataSourceType()) {
         case Gdn_Authenticator::DATA_FORM:
            $Authenticator->FetchData($this->Form);
         break;
         
         case Gdn_Authenticator::DATA_REQUEST:
         case Gdn_Authenticator::DATA_COOKIE:
            $Authenticator->FetchData(Gdn::Request());
         break;
      }
      
      // By default, just render the view
      $Reaction = Gdn_Authenticator::REACT_RENDER;
      // Where are we in the process? Still need to gather (render view) or are we validating?
      $AuthenticationStep = $Authenticator->CurrentStep();
      switch ($AuthenticationStep) {
      
         // User is already logged in
         case Gdn_Authenticator::MODE_REPEAT:
         
            $Reaction = $Authenticator->RepeatResponse();
            
         // Not enough information to perform authentication, render input form
         case Gdn_Authenticator::MODE_GATHER:
            $this->AddJsFile('entry.js');
            $Reaction = $Authenticator->LoginResponse();
				if ($this->Form->IsPostBack())
					$this->Form->AddError('ErrorCredentials');
         break;
         
         // All information is present, authenticate
         case Gdn_Authenticator::MODE_VALIDATE:
            
            // Attempt to authenticate.
            try {
               $AuthenticationResponse = $Authenticator->Authenticate();
               switch ($AuthenticationResponse) {
                  case Gdn_Authenticator::AUTH_PERMISSION:
                     $this->Form->AddError('ErrorPermission');
                  break;

                  case Gdn_Authenticator::AUTH_DENIED:
                     $this->Form->AddError('ErrorCredentials');
                  break;

                  case Gdn_Authenticator::AUTH_INSUFFICIENT:
                     // Unable to comply with auth request, more information is needed from user.
                     $this->Form->AddError('ErrorInsufficient');
                  break;

                  case Gdn_Authenticator::AUTH_PARTIAL:
                     // Partial auth completed.
                     $Reaction = $Authenticator->PartialResponse();
                  break;

                  case Gdn_Authenticator::AUTH_SUCCESS:
                  default:
                     // Full auth completed.
                     $UserID = $AuthenticationResponse;
                     $Reaction = $Authenticator->SuccessResponse();
               }
            } catch (Exception $Ex) {
               $this->Form->AddError($Ex);
            }
         break;
      }
      
      // AddActivity($AuthenticatedUserID, 'SignIn');
      switch ($Reaction) {
      
         case Gdn_Authenticator::REACT_RENDER:
            // Do nothing (render the view)
         break;
      
         case Gdn_Authenticator::REACT_EXIT:
            exit();
         break;
      
         case Gdn_Authenticator::REACT_REMOTE:
            // Render the view, but set the delivery type to VIEW
            $this->_DeliveryType= DELIVERY_TYPE_VIEW;
         break;
         
         case Gdn_Authenticator::REACT_REDIRECT:
         default:
         
            if (is_string($Reaction))
               $Route = $Reaction;
            else
               $Route = $this->RedirectTo();
            
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE)
                  Redirect($Route);
               else
                  Redirect(Gdn::Router()->GetDestination('DefaultController'));
            }
         break;
      }
      
      $this->Render();
   }
      
   public function Index() {
      $this->SignIn();
   }
   
   /**
    * This is a good example of how to use the form, model, and validator to
    * validate a form that does use the model, but doesn't save data to the
    * model.
    */
   public function SignIn() {
      $this->Auth('password');
   }
   
   public function Handshake($AuthenticationSchemeAlias = 'default') {
      
      try {
         // Don't show anything if handshaking not turned on by an authenticator
   		if (!Gdn::Authenticator()->CanHandshake())
   			throw new Exception();
         
         // Try to load the authenticator
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
         
         // Try to grab the authenticator data
         $Payload = $Authenticator->GetHandshake();
         if ($Payload === FALSE) {
            Gdn::Request()->WithURI('dashboard/entry/auth/password');
            return Gdn::Dispatcher()->Dispatch();
         }
      } catch (Exception $e) {
         Gdn::Request()->WithURI('/entry/signin');
         return Gdn::Dispatcher()->Dispatch();
      }

      $this->AddJsFile('entry.js');
      $this->View = 'handshake';
      $this->HandshakeScheme = $AuthenticationSchemeAlias;
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $Target = GetIncomingValue('Target', '/');
      $this->Form->AddHidden('Target', GetIncomingValue('Target', '/'));
      
      $UserKey = $Authenticator->GetUserKeyFromHandshake($Payload);
      $ConsumerKey = $Authenticator->GetProviderKeyFromHandshake($Payload);
      $TokenKey = $Authenticator->GetTokenKeyFromHandshake($Payload);
      $UserName = $Authenticator->GetUserNameFromHandshake($Payload);
      $UserEmail = $Authenticator->GetUserEmailFromHandshake($Payload);
      
      $PreservedKeys = array(
         'UserKey', 'Token', 'Consumer', 'Email', 'Name', 'Gender', 'HourOffset'
      );
      
      $UserID = 0;
      
      // Manual user sync is disabled. No hand holding will occur for users.
      if (!C('Garden.Authenticator.SyncScreen', TRUE)) {
         $UserID = $this->UserModel->Synchronize($UserKey, array(
            'Name'   => $UserName,
            'Email'  => $UserEmail
         ));
         
         if ($UserID > 0) {
            // Account created successfully.
            
            // Finalize the link between the forum user and the foreign userkey
            $Authenticator->Finalize($UserKey, $UserID, $ConsumerKey, $TokenKey, $Payload);
            
            /// ... and redirect them appropriately
            $Route = $this->RedirectTo();
            if ($Route !== FALSE)
               Redirect($Route);
            else
               Redirect('/');
               
         } else {
            // Account not created.
            
            $Authenticator->DeleteCookie();
            Gdn::Request()->WithRoute('DefaultController');
            return Gdn::Dispatcher()->Dispatch();
         }
      
      } else {
      
         if ($this->Form->IsPostBack() === TRUE) {
         
            $FormValues = $this->Form->FormValues();
            if (ArrayValue('StopLinking', $FormValues)) {
            
               $Authenticator->DeleteCookie();
               Gdn::Request()->WithRoute('DefaultController');
               return Gdn::Dispatcher()->Dispatch();
               
            } elseif (ArrayValue('NewAccount', $FormValues)) {
            
               // Try and synchronize the user with the new username/email.
               $FormValues['Name'] = $FormValues['NewName'];
               $FormValues['Email'] = $FormValues['NewEmail'];
               $UserID = $this->UserModel->Synchronize($UserKey, $FormValues);
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
               
            } else {
   
               // Try and sign the user in.
               $PasswordAuthenticator = Gdn::Authenticator()->AuthenticateWith('password');
               $PasswordAuthenticator->HookDataField('Email', 'SignInEmail');
               $PasswordAuthenticator->HookDataField('Password', 'SignInPassword');
               $PasswordAuthenticator->FetchData($this->Form);
               
               $UserID = $PasswordAuthenticator->Authenticate();
               
               if ($UserID < 0) {
                  $this->Form->AddError('ErrorPermission');
               } else if ($UserID == 0) {
                  $this->Form->AddError('ErrorCredentials');
               }
               
               if ($UserID > 0) {
                  $Data = $FormValues;
                  $Data['UserID'] = $UserID;
                  $Data['Email'] = ArrayValue('SignInEmail', $FormValues, '');
                  $UserID = $this->UserModel->Synchronize($UserKey, $Data);
               }
            }
            
            if ($UserID > 0) {
               // The user has been created successfully, so sign in now
               
               // Finalize the link between the forum user and the foreign userkey
               $Authenticator->Finalize($UserKey, $UserID, $ConsumerKey, $TokenKey, $Payload);
               
               /// ... and redirect them appropriately
               $Route = $this->RedirectTo();
               if ($Route !== FALSE)
                  Redirect($Route);
            } else {
               // Add the hidden inputs back into the form.
               foreach($FormValues as $Key => $Value) {
                  if (in_array($Key, $PreservedKeys))
                     $this->Form->AddHidden($Key, $Value);
               }
            }
         } else {
            $Id = Gdn::Authenticator()->GetIdentity(TRUE);
            if ($Id > 0) {
               // The user is signed in so we can just go back to the homepage.
               Redirect($Target);
            }
            
            $Name = $UserName;
            $Email = $UserEmail;
            
            // Set the defaults for a new user.
            $this->Form->SetFormValue('NewName', $Name);
            $this->Form->SetFormValue('NewEmail', $Email);
            
            // Set the default for the login.
            $this->Form->SetFormValue('SignInEmail', $Email);
            $this->Form->SetFormValue('Handshake', 'NEW');
            
            // Add the handshake data as hidden fields.
            $this->Form->AddHidden('Name',       $Name);
            $this->Form->AddHidden('Email',      $Email);
            $this->Form->AddHidden('UserKey',    $UserKey);
            $this->Form->AddHidden('Token',      $TokenKey);
            $this->Form->AddHidden('Consumer',   $ConsumerKey);
            
         }
         
         $this->SetData('Name', ArrayValue('Name', $this->Form->HiddenInputs));
         $this->SetData('Email', ArrayValue('Email', $this->Form->HiddenInputs));
         
         $this->Render();
      }
   }
   
   /**
    * Calls the appropriate registration method based on the configuration setting.
    */
   public function Register($InvitationCode = '') {
      $this->Form->SetModel($this->UserModel);

      // Define gender dropdown options
      $this->GenderOptions = array(
         'm' => T('Male'),
         'f' => T('Female')
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
         $this->UserModel->Validation->ApplyRule('Name', 'Username', self::UsernameError);
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
         $this->UserModel->Validation->ApplyRule('Name', 'Username', self::UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForBasic($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
            $Authenticator->FetchData($this->Form);
            $AuthUserID = $Authenticator->Authenticate();
            
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
         $this->UserModel->Validation->ApplyRule('Name', 'Username', self::UsernameError);
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
            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
            $Authenticator->FetchData($this->Form);
            $AuthUserID = $Authenticator->Authenticate();
            
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
         $this->UserModel->Validation->ApplyRule('Name', 'Username', self::UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', 'You must agree to the terms of service.');
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');
         
         if (!$this->UserModel->InsertForInvite($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
         } else {
            // The user has been created successfully, so sign in now
            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
            $Authenticator->FetchData($this->Form);
            $AuthUserID = $Authenticator->Authenticate();
            
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
      if ($this->Form->IsPostBack() === TRUE) {

         if ($this->Form->ValidateModel() == 0) {
            try {
               if (!$this->UserModel->PasswordRequest($this->Form->GetFormValue('Email', ''))) {
                  $this->Form->AddError("Couldn't find an account associated with that email address.");
               }
            } catch (Exception $ex) {
               $this->Form->AddError($ex->getMessage());
            }
            if ($this->Form->ErrorCount() == 0) {
               $this->Form->AddError('Success!');
               $this->View = 'passwordrequestsent';
            }
         } else {
            if ($this->Form->ErrorCount() == 0)
               $this->Form->AddError('That email address was not found.');
         }
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
            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
            $Authenticator->FetchData($Authenticator, array('Email' => $User->Email, 'Password' => $Password, 'RememberMe' => FALSE));
            $AuthUserID = $Authenticator->Authenticate();
				Redirect('/');
         }
      }
      $this->Render();
   }

   public function EmailConfirm($UserID = '', $EmailKey = '') {
      if (!is_numeric($UserID) || $EmailKey != $this->UserModel->GetAttribute($UserID, 'EmailKey', '')) {
         $this->Form->AddError(T('Couldn\'t confirm email.',
            'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'));
      }

      if ($this->Form->ErrorCount() == 0) {
         

      }
      $this->Render();
   }

   public function Leave($AuthenticationSchemeAlias = 'default', $TransientKey = '') {
      try {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith('default');
      }
      
      // Only sign the user out if this is an authenticated postback!
      $Session = Gdn::Session();
      $this->Leaving = FALSE;
      $Result = Gdn_Authenticator::REACT_RENDER;
      $AuthenticatedPostbackRequired = $Authenticator->RequireLogoutTransientKey();
      if (!$AuthenticatedPostbackRequired || $Session->ValidateTransientKey($TransientKey)) {
         $Result = $Authenticator->DeAuthenticate();
         $this->Leaving = TRUE;
      }
      
      if ($Result == Gdn_Authenticator::AUTH_SUCCESS) {
         $this->View = 'auth/'.$Authenticator->GetAuthenticationSchemeAlias();
         if ($Target = GetIncomingValue('Target', ''))
            $Reaction = $Target;
         else
            $Reaction = $Authenticator->SuccessResponse();
      } else {
         $Reaction = $Authenticator->LoginResponse();
      }

      if (is_string($Reaction)) {
         $Route = $Reaction;
         if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
         } else {
            if ($Route !== FALSE)
               Redirect($Route);
            else
               Redirect(Gdn::Router()->GetDestination('DefaultController'));
         }
      } else {
         switch ($Reaction) {

            case Gdn_Authenticator::REACT_RENDER:
            break;

            case Gdn_Authenticator::REACT_EXIT:
               exit();
            break;

            case Gdn_Authenticator::REACT_REMOTE:
               // Render the view, but set the delivery type to VIEW
               $this->_DeliveryType= DELIVERY_TYPE_VIEW;
            break;

            case Gdn_Authenticator::REACT_REDIRECT:
            default:
               $Route = '/entry';

               if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
                  $this->RedirectUrl = Url($Route);
               } else {
                  if ($Route !== FALSE)
                     Redirect($Route);
                  else
                     Redirect(Gdn::Router()->GetDestination('DefaultController'));
               }
            break;
         }
      }
      
      $this->Render();
   }
   
   public function RedirectTo() {
      $IncomingTarget = $this->Form->GetValue('Target', '');
      return $IncomingTarget == '' ? Gdn::Router()->GetDestination('DefaultController') : $IncomingTarget;
   }
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      parent::Initialize();
   }

   
}
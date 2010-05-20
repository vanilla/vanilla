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
         break;
         
         // All information is present, authenticate
         case Gdn_Authenticator::MODE_VALIDATE:
            
            // Attempt to authenticate...
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
                  Redirect('DefaultController');
            }
         break;
      }
      
      $this->Render();
   }
   
   public function Callback($AuthenticationSchemeAlias) {
      // Handle generic auth requests here
      $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
   }
   
   public function Index() {
      $this->Auth();
   }
   
   /**
    * This is a good example of how to use the form, model, and validator to
    * validate a form that does use the model, but doesn't save data to the
    * model.
    */
   public function SignIn() {
      $this->Auth('password');
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
            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
            $Authenticator->Authenticate(array('Email' => $User->Email, 'Password' => $Password, 'RememberMe' => FALSE));
            $this->StatusMessage = T('Password saved. Signing you in now...');
            $this->RedirectUrl = Url('/');
         }
      }
      $this->Render();
   }

   public function Leave($TransientKey = '', $AuthenticationSchemeAlias = 'default') {
   
      try {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith('default');
      }
   
      // Only sign the user out if this is an authenticated postback!
      $Session = Gdn::Session();
      $this->Leaving = FALSE;
      if ($Session->ValidateTransientKey($TransientKey)) {
         $Authenticator->DeAuthenticate();
         $this->Leaving = TRUE;
         $this->RedirectUrl = Url('/entry');
      }
      $this->Render();
   }
   
   public function RedirectTo() {
      $IncomingTarget = $this->Form->GetValue('Target', '');
      return $IncomingTarget == '' ? 'DefaultController' : $IncomingTarget;
   }
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('js/library/jquery.js');
      $this->AddJsFile('js/library/jquery.livequery.js');
      $this->AddJsFile('js/library/jquery.form.js');
      $this->AddJsFile('js/library/jquery.popup.js');
      // $this->AddJsFile('js/library/jquery.menu.js');
      $this->AddJsFile('js/library/jquery.gardenhandleajaxform.js');
      $this->AddJsFile('js/global.js');
      
      $this->AddCssFile('style.css');
      parent::Initialize();
   }

   
}
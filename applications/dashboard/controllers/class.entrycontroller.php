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

   /**
    * @var Gdn_Form The current form.
    */
   public $Form;

   public function  __construct() {
      parent::__construct();

      switch (isset($_GET['display'])) {
         case 'popup':
            $this->MasterView = 'empty';
            break;
      }
   }
   
   public function Auth($AuthenticationSchemeAlias = 'default') {
      $this->EventArguments['AuthenticationSchemeAlias'] = $AuthenticationSchemeAlias;
      $this->FireEvent('BeforeAuth');
      
      // Allow hijacking auth type
      $AuthenticationSchemeAlias = $this->EventArguments['AuthenticationSchemeAlias'];
      
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
         break;
            
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

               $UserInfo = array();
               $UserEventData = array_merge(array(
                  'UserID'    => Gdn::Session()->UserID,
                  'Payload'   => GetValue('HandshakeResponse', $Authenticator, FALSE)
               ),$UserInfo);
               
               Gdn::Authenticator()->Trigger($AuthenticationResponse, $UserEventData);
               switch ($AuthenticationResponse) {
                  case Gdn_Authenticator::AUTH_PERMISSION:
                     $this->Form->AddError('ErrorPermission');
                     $Reaction = $Authenticator->FailedResponse();
                  break;

                  case Gdn_Authenticator::AUTH_DENIED:
                     $this->Form->AddError('ErrorCredentials');
                     $Reaction = $Authenticator->FailedResponse();
                  break;

                  case Gdn_Authenticator::AUTH_INSUFFICIENT:
                     // Unable to comply with auth request, more information is needed from user.
                     $this->Form->AddError('ErrorInsufficient');
                     $Reaction = $Authenticator->FailedResponse();
                  break;

                  case Gdn_Authenticator::AUTH_PARTIAL:
                     // Partial auth completed.
                     $Reaction = $Authenticator->PartialResponse();
                  break;

                  case Gdn_Authenticator::AUTH_SUCCESS:
                  default: 
                     // Full auth completed.
                     if ($AuthenticationResponse == Gdn_Authenticator::AUTH_SUCCESS)
                        $UserID = Gdn::Session()->UserID;
                     else
                        $UserID = $AuthenticationResponse;
                     $Reaction = $Authenticator->SuccessResponse();
               }
            } catch (Exception $Ex) {
               $this->Form->AddError($Ex);
            }
         break;
         
         case Gdn_Authenticator::MODE_NOAUTH:
            $Reaction = Gdn_Authenticator::REACT_REDIRECT;
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
            // Let the authenticator handle generating output, using a blank slate
            $this->_DeliveryType= DELIVERY_TYPE_VIEW;
            
            exit;
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
               
               if ($Route !== FALSE) {
                  Redirect($Route);
               } else {
                  Redirect(Gdn::Router()->GetDestination('DefaultController'));
               }
            }
         break;
      }
      
      $this->SetData('SendWhere', "/entry/auth/{$AuthenticationSchemeAlias}");
      $this->Render();
   }

   /**
    * Connect the user with an external source.
    * This controller method is meant to be used with plugins that set its data array to work.
    */
   public function Connect($Method) {
      $this->AddJsFile('entry.js');
      $this->View = 'connect';
      $IsPostBack = $this->Form->IsPostBack();

      if (!$IsPostBack) {
         // Here are the initial data array values. that can be set by a plugin.
         $Data = array('Provider' => '', 'ProviderName' => '', 'UniqueID' => '', 'FullName' => '', 'Name' => '', 'Email' => '', 'Photo' => '', 'Target' => GetIncomingValue('Target', '/'));
         $this->Form->FormValues($Data);
         $this->Form->AddHidden('Target');
      }

      // The different providers can check to see if they are being used and modify the data array accordingly.
      $this->EventArguments = array($Method);
      try {
         $this->FireEvent('ConnectData');
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
         return $this->Render('ConnectError');
      } catch (Exception $Ex) {
         if (defined('DEBUG'))
            $this->Form->AddError($Ex);
         else
            $this->Form->AddError('There was an error fetching the connection data.');
         return $this->Render('ConnectError');
      }

      $FormData = $this->Form->FormValues(); // debug

      // Make sure the minimum required data has been provided to the connect.
      if (!$this->Form->GetFormValue('Provider'))
         $this->Form->AddError('ValidateRequired', T('Provider'));
      if (!$this->Form->GetFormValue('UniqueID'))
         $this->Form->AddError('ValidateRequired', T('UniqueID'));
      
      if (!$this->Data('Verified')) {
         // Whatever event handler catches this must Set the data 'Verified' to true to prevent a random site from connecting without credentials.
         // This must be done EVERY postback and is VERY important.
         $this->Form->AddError('The connection data has not been verified.');
      }
      
      if ($this->Form->ErrorCount() > 0)
         return $this->Render();

      $UserModel = new UserModel();

      // Check to see if there is an existing user associated with the information above.
      $Auth = $UserModel->GetAuthentication($this->Form->GetFormValue('UniqueID'), $this->Form->GetFormValue('Provider'));
      $UserID = GetValue('UserID', $Auth);

      if ($UserID) {
         // The user is already connected.
         $this->Form->SetFormValue('UserID', $UserID);
         // Synchronize the user's data.
         $UserModel->Save($this->Data);

         // Sign the user in.
         Gdn::Session()->Start($UserID);
         $this->_SetRedirect(TRUE);
      } elseif ($this->Form->GetFormValue('Name') || $this->Form->GetFormValue('Email')) {

         // Get the existing users that match the name or email of the connection.
         $Search = FALSE;
         if ($this->Form->GetFormValue('Name')) {
            $UserModel->SQL->OrWhere('Name', $this->Form->GetFormValue('Name'));
            $Search = TRUE;
         }
         if ($this->Form->GetFormValue('Email')) {
            $UserModel->SQL->OrWhere('Email', $this->Form->GetFormValue('Email'));
            $Search = TRUE;
         }

         if ($Search)
            $ExistingUsers = $UserModel->GetWhere()->ResultArray();
         else
            $ExistingUsers = array();

         $EmailUnique = C('Garden.Registration.EmailUnique', TRUE);
         $CurrentUserID = Gdn::Session()->UserID;

         // Massage the existing users.
         foreach ($ExistingUsers as $Index => $UserRow) {
            if ($EmailUnique && $UserRow['Email'] == $this->Form->GetFormValue('Email')) {
               $EmailFound = $UserRow;
               break;
            }

            if ($CurrentUserID > 0 && $UserRow['UserID'] == $CurrentUserID) {
               unset($ExistingUsers[$Index]);
               $CurrentUserFound = TRUE;
            }
         }

         if (isset($EmailFound)) {
            // The email address was found and can be the only user option.
            $ExistingUsers = array($UserRow);
            $this->SetData('NoConnectName', TRUE);
         } elseif (isset($CurrentUserFound)) {
            $ExistingUsers = array_merge(
               array('UserID' => 'current', 'Name' => sprintf(T('%s (Current)'), Gdn::Session()->User->Name)),
               $ExistingUsers);
         }

         $this->SetData('ExistingUsers', $ExistingUsers);

         if ($this->Form->GetFormValue('Name') && (!is_array($ExistingUsers) || count($ExistingUsers) == 0)) {
            // There is no existing user with the suggested name so we can just create the user.
            $User = $this->Form->FormValues();
            $User['Password'] = RandomString(50); // some password is required
            $User['HashMethod'] = 'Random';

            $UserID = $UserModel->InsertForBasic($User, FALSE);
            $User['UserID'] = $UserID;
            $this->Form->SetValidationResults($UserModel->ValidationResults());

            if ($UserID) {
               $UserModel->SaveAuthentication(array(
                      'UserID' => $UserID,
                      'Provider' => $this->Form->GetFormValue('Provider'),
                      'UniqueID' => $this->Form->GetFormValue('UniqueID')));
               $this->Form->SetFormValue('UserID', $UserID);

               Gdn::Session()->Start($UserID);

               // Send the welcome email.
               $UserModel->SendWelcomeEmail($UserID, '', 'Connect', array('ProviderName' => $this->Form->GetFormValue('ProviderName', $this->Form->GetFormValue('Provider', 'Unknown'))));

               $this->_SetRedirect(TRUE);
            }
         }
      }

      // Save the user's choice.
      if ($IsPostBack) {
         // The user has made their decision.
         $PasswordHash = new Gdn_PasswordHash();

         $UserSelect = $this->Form->GetFormValue('UserSelect');

         if (!$UserSelect || $UserSelect == 'other') {
            // The user entered a username.
            $ConnectNameEntered = TRUE;

            if ($this->Form->ValidateRule('ConnectName', 'ValidateRequired')) {
               $ConnectName = $this->Form->GetFormValue('ConnectName');

               // Check to see if there is already a user with the given name.
               $User = $UserModel->GetWhere(array('Name' => $ConnectName))->FirstRow(DATASET_TYPE_ARRAY);

               if (!$User) {
                  $this->Form->ValidateRule('ConnectName', 'ValidateUsername');
               }
            }
         } else {
            // The user selected an existing user.

            if ($UserSelect == 'current') {
               if (Gdn::Session()->UserID == 0) {
                  // This shouldn't happen, but a use could sign out in another browser and click submit on this form.
                  $this->Form->AddError('@You were uexpectidly signed out.');
               } else {
                  $UserSelect = Gdn::Session()->UserID;
               }
            }
            $User = $UserModel->GetID($UserSelect, DATASET_TYPE_ARRAY);
         }

         if (isset($User) && $User) {
            // Make sure the user authenticates.
            if (!$User['UserID'] == Gdn::Session()->UserID) {

               if ($this->Form->ValidateRule('ConnectPassword', 'ValidateRequired', sprintf(T('ValidateRequired'), T('Password')))
                  && !$PasswordHash->CheckPassword($this->Form->GetFormValue('ConnectPassword'), $User['Password'], $User['HashMethod'])) {

                  if ($ConnectNameEntered) {
                     $this->Form->AddError('The username you entered has already been taken.');
                  } else {
                     $this->Form->AddError('The password you entered is incorrect.');
                  }
               }
            }
         } elseif ($this->Form->ErrorCount() == 0) {
            // The user doesn't exist so we need to add another user.
            $User = $this->Form->FormValues();
            $User['Name'] = $User['ConnectName'];
            $User['Password'] = RandomString(50); // some password is required
            $User['HashMethod'] = 'Random';

            $UserID = $UserModel->InsertForBasic($User, FALSE);
            $User['UserID'] = $UserID;
            $this->Form->SetValidationResults($UserModel->ValidationResults());

            if ($UserID) {
               // Add the user to the default roles.
               $UserModel->SaveRoles($UserID, C('Garden.Registration.DefaultRoles'));

               // Send the welcome email.
               $UserModel->SendWelcomeEmail($UserID, '', 'Connect', array('ProviderName' => $this->Form->GetFormValue('ProviderName', $this->Form->GetFormValue('Provider', 'Unknown'))));
            }
         }

         if ($this->Form->ErrorCount() == 0) {
            // Save the authentication.
            if (isset($User) && GetValue('UserID', $User)) {
               $UserModel->SaveAuthentication(array(
                   'UserID' => $User['UserID'],
                   'Provider' => $this->Form->GetFormValue('Provider'),
                   'UniqueID' => $this->Form->GetFormValue('UniqueID')));
               $this->Form->SetFormValue('UserID', $User['UserID']);
            }

            // Sign the appropriate user in.
            Gdn::Session()->Start($this->Form->GetFormValue('UserID'));
            $this->_SetRedirect(TRUE);
         }
      }

      $this->Render();
   }

   protected function _SetRedirect($CheckPopup = FALSE) {
      $Url = Url($this->RedirectTo(), TRUE);

      $this->RedirectUrl = $Url;
      $this->MasterView = 'empty';
      $this->View = 'redirect';

      if ($this->DeliveryType() != DELIVERY_TYPE_ALL) {
         $this->DeliveryMethod(DELIVERY_METHOD_JSON);
         $this->SetHeader('Content-Type', 'application/json');
      } elseif ($CheckPopup) {
         $this->AddDefinition('CheckPopup', $CheckPopup);
      } else {
         Redirect(Url($this->RedirectUrl));
      }
   }
      
   public function Index() {
      $this->SignIn();
   }
   
   public function Password() {
      $this->Auth('password');
   }
   
   public function SignIn2() {
      $this->FireEvent("SignIn");
      $this->Auth('default');
   }

   public function SignOut($TransientKey = "") {
      $SessionAuthenticator = Gdn::Session()->GetPreference('Authenticator');
      $AuthenticationScheme = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';

      try {
         $Authenticator = Gdn::Authenticator()->GetAuthenticator($AuthenticationScheme);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->GetAuthenticator();
      }
   
      $this->FireEvent("SignOut");
      $this->Leave($AuthenticationScheme, $TransientKey);
   }
  
   /** A version of signin that supports multiple authentication methods.
    *  This method should replace EntryController::SignIn() eventually.
    *
    * @param string $Method
    */
   public function SignIn($Method = FALSE, $Arg1 = FALSE) {
      $this->AddJsFile('entry.js');

      // Additional signin methods are set up with plugins.
      $Methods = array();

      if (in_array($Method, array('connect', 'password')))
         $this->SetData('MainFormMethod', array($this, ucfirst($Method)));
      else
         $this->SetData('MainFormMethod', array($this, 'Password'));

      $this->SetData('MainFormArgs', array($Arg1));
      $this->SetData('Methods', $Methods);
      $this->SetData('FormUrl', Url('entry/signin'));

      $this->FireEvent('SignIn');

      // Figure out the current method.
      $DeliveryType = $this->DeliveryType();
      $this->DeliveryType(DELIVERY_TYPE_VIEW);

      $DeliveryMethod = $this->DeliveryMethod();
      $this->DeliveryMethod(DELIVERY_METHOD_XHTML);

      // Capture the appropriate method.
      ob_start();
      call_user_func_array($this->Data('MainFormMethod'), $this->Data('MainFormArgs', array()));
      $View = ob_get_clean();

      $this->SetData('MainForm', $View);

      $this->DeliveryType($DeliveryType);
      $this->DeliveryMethod($DeliveryMethod);


      return $this->Render('signin2');
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
      
      $UserInfo = array(
         'UserKey'      => $Authenticator->GetUserKeyFromHandshake($Payload),
         'ConsumerKey'  => $Authenticator->GetProviderKeyFromHandshake($Payload),
         'TokenKey'     => $Authenticator->GetTokenKeyFromHandshake($Payload),
         'UserName'     => $Authenticator->GetUserNameFromHandshake($Payload),
         'UserEmail'    => $Authenticator->GetUserEmailFromHandshake($Payload)
      );
      
      // Manual user sync is disabled. No hand holding will occur for users.
      $SyncScreen = C('Garden.Authenticator.SyncScreen', 'on');
      switch ($SyncScreen) {
         case 'on':
         
            // Authenticator events fired inside
            $this->SyncScreen($Authenticator, $UserInfo, $Payload);
            
         break;
         
         case 'off':
         case 'smart':
            $UserID = $this->UserModel->Synchronize($UserInfo['UserKey'], array(
               'Name'   => $UserInfo['UserName'],
               'Email'  => $UserInfo['UserEmail']
            ));
            
            if ($UserID > 0) {
               // Account created successfully.
               
               // Finalize the link between the forum user and the foreign userkey
               $Authenticator->Finalize($UserInfo['UserKey'], $UserID, $UserInfo['ConsumerKey'], $UserInfo['TokenKey'], $Payload);
               
               $UserEventData = array_merge(array(
                  'UserID'       => $UserID,
                  'Payload'      => $Payload
               ),$UserInfo);
               Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_CREATED, $UserEventData);
               
               /// ... and redirect them appropriately
               $Route = $this->RedirectTo();
               if ($Route !== FALSE)
                  Redirect($Route);
               else
                  Redirect('/');
                  
            } else {
               // Account not created.
               if ($SyncScreen == 'smart') {
               
                  $this->StatusMessage = T('There is already an account in this forum using your email address. Please create a new account, or enter the credentials for the existing account.');
                  $this->SyncScreen($Authenticator, $UserInfo, $Payload);
                  
               } else {
                  
                  // Set the memory cookie to allow signinloopback to shortcircuit remote query.
                  $CookiePayload = array(
                     'Sync'   => 'Failed'
                  );
                  $SerializedCookiePayload = Gdn_Format::Serialize($CookiePayload);
                  $Authenticator->Remember($UserInfo['ConsumerKey'], $SerializedCookiePayload);
                  
                  // This resets vanilla's internal "where am I" to the homepage. Needed.
                  Gdn::Request()->WithRoute('DefaultController');
                  $this->SelfUrl = Url('');//Gdn::Request()->Path();
                  
                  $this->View = 'syncfailed';
                  $this->ProviderSite = $Authenticator->GetProviderUrl();
                  $this->Render();
               }
               
            }
         break;
      
      }
   }
   
   public function SyncScreen($Authenticator, $UserInfo, $Payload) {
      $this->AddJsFile('entry.js');
      $this->View = 'handshake';
      $this->HandshakeScheme = $Authenticator->GetAuthenticationSchemeAlias();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('G', time())); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', GetIncomingValue('Target', '/'));
      
      $PreservedKeys = array(
         'UserKey', 'Token', 'Consumer', 'Email', 'Name', 'Gender', 'HourOffset'
      );
      $UserID = 0;
      $Target = GetIncomingValue('Target', '/');
   
      if ($this->Form->IsPostBack() === TRUE) {
            
         $FormValues = $this->Form->FormValues();
         if (ArrayValue('StopLinking', $FormValues)) {
            $AuthResponse = Gdn_Authenticator::AUTH_ABORTED;
            
            $UserEventData = array_merge(array(
               'UserID'       => $UserID,
               'Payload'      => $Payload
            ),$UserInfo);
            Gdn::Authenticator()->Trigger($AuthResponse, $UserEventData);
            
            $Authenticator->DeleteCookie();
            Gdn::Request()->WithRoute('DefaultController');
            return Gdn::Dispatcher()->Dispatch();
            
         } elseif (ArrayValue('NewAccount', $FormValues)) {
            $AuthResponse = Gdn_Authenticator::AUTH_CREATED;
         
            // Try and synchronize the user with the new username/email.
            $FormValues['Name'] = $FormValues['NewName'];
            $FormValues['Email'] = $FormValues['NewEmail'];
            $UserID = $this->UserModel->Synchronize($UserInfo['UserKey'], $FormValues);
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            
         } else {
            $AuthResponse = Gdn_Authenticator::AUTH_SUCCESS;
   
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
               $UserID = $this->UserModel->Synchronize($UserInfo['UserKey'], $Data);
            }
         }
         
         if ($UserID > 0) {
            // The user has been created successfully, so sign in now
            
            // Finalize the link between the forum user and the foreign userkey
            $Authenticator->Finalize($UserInfo['UserKey'], $UserID, $UserInfo['ConsumerKey'], $UserInfo['TokenKey'], $Payload);
            
            $UserEventData = array_merge(array(
               'UserID'       => $UserID,
               'Payload'      => $Payload
            ),$UserInfo);
            Gdn::Authenticator()->Trigger($AuthResponse, $UserEventData);
            
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
         
         $Name = $UserInfo['UserName'];
         $Email = $UserInfo['UserEmail'];
         
         // Set the defaults for a new user.
         $this->Form->SetFormValue('NewName', $Name);
         $this->Form->SetFormValue('NewEmail', $Email);
         
         // Set the default for the login.
         $this->Form->SetFormValue('SignInEmail', $Email);
         $this->Form->SetFormValue('Handshake', 'NEW');
         
         // Add the handshake data as hidden fields.
         $this->Form->AddHidden('Name',       $Name);
         $this->Form->AddHidden('Email',      $Email);
         $this->Form->AddHidden('UserKey',    $UserInfo['UserKey']);
         $this->Form->AddHidden('Token',      $UserInfo['TokenKey']);
         $this->Form->AddHidden('Consumer',   $UserInfo['ConsumerKey']);
         
/*
         $this->Form->AddHidden('Payload',    serialize($Payload));
         $this->Form->AddHidden('UserInfo',   serialize($UserInfo));
*/
         
      }
      
      $this->SetData('Name', ArrayValue('Name', $this->Form->HiddenInputs));
      $this->SetData('Email', ArrayValue('Email', $this->Form->HiddenInputs));
      
      $this->Render();
   }
   
   /**
    * Calls the appropriate registration method based on the configuration setting.
    */
   public function Register($InvitationCode = '') {
      $this->FireEvent("Register");
      
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
         
         if (!$this->UserModel->InsertForApproval($this->Form->FormValues())) {
            $this->Form->SetValidationResults($this->UserModel->ValidationResults());
			} else {
				$this->FireEvent('RegistrationPending');
            $this->View = "RegisterThanks"; // Tell the user their application will be reviewed by an administrator.
			}
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

            try {
               $this->UserModel->SendWelcomeEmail($AuthUserID, '', 'Register');
            } catch (Exception $Ex) {
            }
				
				$this->FireEvent('RegistrationSuccessful');
            
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

            try {
               $this->UserModel->SendWelcomeEmail($AuthUserID, '', 'Register');
            } catch (Exception $Ex) {
            }
				
				$this->FireEvent('RegistrationSuccessful');
            
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
				
				$this->FireEvent('RegistrationSuccessful');
            
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
      $this->EventArguments['AuthenticationSchemeAlias'] = $AuthenticationSchemeAlias;
      $this->FireEvent('BeforeLeave');
      
      // Allow hijacking deauth type
      $AuthenticationSchemeAlias = $this->EventArguments['AuthenticationSchemeAlias'];
      
      try {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith('default');
      }
      
      // Only sign the user out if this is an authenticated postback! Start off pessimistic
      $this->Leaving = FALSE;
      $Result = Gdn_Authenticator::REACT_RENDER;
      
      // Build these before doing anything desctructive as they are supposed to have user context
      $LogoutResponse = $Authenticator->LogoutResponse();
      $LoginResponse = $Authenticator->LoginResponse();
      
      $AuthenticatedPostbackRequired = $Authenticator->RequireLogoutTransientKey();
      if (!$AuthenticatedPostbackRequired || Gdn::Session()->ValidateTransientKey($TransientKey)) {
         $Result = $Authenticator->DeAuthenticate();
         $this->Leaving = TRUE;
      }
      
      if ($Result == Gdn_Authenticator::AUTH_SUCCESS) {
         $this->View = 'leave';
         $Reaction = $LogoutResponse;
      } else {
         $this->View = 'auth/'.$Authenticator->GetAuthenticationSchemeAlias();
         $Reaction = $LoginResponse;
      }
      
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
            // If we're just told to redirect, but not where... try to figure out somewhere that makes sense.
            if ($Reaction == Gdn_Authenticator::REACT_REDIRECT) {
               $Route = '/';
               $Target = GetIncomingValue('Target', NULL);
               if (!is_null($Target)) 
                  $Route = $Target;
            } else {
               $Route = $Reaction;
            }
            
            if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
               $this->RedirectUrl = Url($Route);
            } else {
               if ($Route !== FALSE) {
                  Redirect($Route);
               } else {
                  Redirect(Gdn::Router()->GetDestination('DefaultController'));
               }
            }
         break;
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

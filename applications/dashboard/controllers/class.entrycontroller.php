<?php if (!defined('APPLICATION')) exit();

/**
 * Manages users manually authenticating (signing in).
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class EntryController extends Gdn_Controller {
   /**
    * Models to include.
    *
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'Form', 'UserModel');


   /**
    * @var Gdn_Form
    */
   public $Form;

   /**
    *
    * @var UserModel
    */
   public $UserModel;

   /**
    * Resuable username requirement error message.
    *
    * @since 2.0.17
    * @access public
    * @var string
    */
	public $UsernameError = '';

   /**
    * Place to store DeliveryType.
    *
    * @since 2.0.0
    * @access protected
    * @var string
    */
   protected $_RealDeliveryType;

   /**
    * Setup error message & override MasterView for popups.
    *
    * @since 2.0.0
    * @access public
    */
   public function  __construct() {
      parent::__construct();

      // Set error message here so it can run thru T()
      $this->UsernameError = T('UsernameError', 'Username can only contain letters, numbers, underscores, and must be between 3 and 20 characters long.');

      switch (isset($_GET['display'])) {
         case 'popup':
            $this->MasterView = 'popup';
            break;
      }
   }

   /**
    * Include JS and CSS used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));

      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');

      $this->AddCssFile('style.css');
      parent::Initialize();
      Gdn_Theme::Section('Entry');
   }

   /**
    * Authenticate the user attempting to sign in.
    *
    * Events: BeforeAuth
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $AuthenticationSchemeAlias Type of authentication we're attempting.
    */
   public function Auth($AuthenticationSchemeAlias = 'default') {
      Gdn::Session()->EnsureTransientKey();

      $this->EventArguments['AuthenticationSchemeAlias'] = $AuthenticationSchemeAlias;
      $this->FireEvent('BeforeAuth');

      // Allow hijacking auth type
      $AuthenticationSchemeAlias = $this->EventArguments['AuthenticationSchemeAlias'];

      // Attempt to set Authenticator with requested method or fallback to default
      try {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith($AuthenticationSchemeAlias);
      } catch (Exception $e) {
         $Authenticator = Gdn::Authenticator()->AuthenticateWith('default');
      }

      // Set up controller
      $this->View = 'auth/'.$Authenticator->GetAuthenticationSchemeAlias();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default.

      $Target = $this->Target();

      $this->Form->AddHidden('Target', $Target);

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
               if (!$this->Request->IsAuthenticatedPostBack()) {
                  $this->Form->AddError('Please try again.');
                  $Reaction = $Authenticator->FailedResponse();
               } else {
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

                        safeHeader("X-Vanilla-Authenticated: yes");
                        safeHeader("X-Vanilla-TransientKey: ".Gdn::Session()->TransientKey());
                        $Reaction = $Authenticator->SuccessResponse();
                  }
               }
            } catch (Exception $Ex) {
               $this->Form->AddError($Ex);
            }
         break;

         case Gdn_Authenticator::MODE_NOAUTH:
            $Reaction = Gdn_Authenticator::REACT_REDIRECT;
         break;
      }

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

            if ($this->_RealDeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryType != DELIVERY_TYPE_ALL) {
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
    * Check the default provider to see if it overrides one of the entry methods and then redirect.
    * @param string $Type One of the following.
    *  - SignIn
    *  - Register
    *  - SignOut (not complete)
    */
   protected function CheckOverride($Type, $Target, $TransientKey = NULL) {
      if (!$this->Request->Get('override', TRUE))
         return;

      $Provider = Gdn_AuthenticationProviderModel::GetDefault();
      if (!$Provider)
         return;

      $this->EventArguments['Target'] = $Target;
      $this->EventArguments['DefaultProvider'] =& $Provider;
      $this->EventArguments['TransientKey'] = $TransientKey;
      $this->FireEvent("Override{$Type}");

      $Url = $Provider[$Type.'Url'];
      if ($Url) {
         switch ($Type) {
            case 'Register':
            case 'SignIn':
               // When the other page comes back it needs to go through /sso to force a sso check.
               $Target = '/sso?target='.urlencode($Target);
               break;
            case 'SignOut':
               $Cookie = C('Garden.Cookie.Name');
               if (strpos($Url, '?') === FALSE)
                  $Url .= '?vfcookie='.urlencode($Cookie);
               else
                  $Url .= '&vfcookie='.urlencode($Cookie);

               // Check to sign out here.
               $SignedOut = !Gdn::Session()->IsValid();
               if (!$SignedOut && (Gdn::Session()->ValidateTransientKey($TransientKey) || $this->Form->IsPostBack())) {
                  Gdn::Session()->End();
                  $SignedOut = TRUE;
               }

               // Sign out is a bit of a tricky thing so we configure the way it works.
               $SignoutType = C('Garden.SSO.Signout');
               switch ($SignoutType) {
                  case 'redirect-only':
                     // Just redirect to the url.
                     break;
                  case 'post-only':
                     $this->SetData('Method', 'POST');
                     break;
                  case 'post':
                     // Post to the url after signing out here.
                     if (!$SignedOut)
                        return;
                     $this->SetData('Method', 'POST');
                     break;
                  case 'none':
                     return;
                  case 'redirect':
                  default:
                     if (!$SignedOut)
                        return;
                     break;
               }

               break;
            default:
               throw new Exception("Unknown entry type $Type.");
         }

         $Url = str_ireplace('{target}', rawurlencode(Url($Target, TRUE)), $Url);

         if ($this->DeliveryType() == DELIVERY_TYPE_ALL && strcasecmp($this->Data('Method'), 'POST') != 0)
            Redirect($Url, 302);
         else {
            $this->SetData('Url', $Url);
            $this->Render('Redirect', 'Utility');
            die();
         }
      }
   }

   /**
    * Connect the user with an external source.
    *
    * This controller method is meant to be used with plugins that set its data array to work.
    * Events: ConnectData
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $Method Used to register multiple providers on ConnectData event.
    */
   public function Connect($Method) {
      $this->AddJsFile('entry.js');
      $this->View = 'connect';
      $IsPostBack = $this->Form->IsPostBack() && $this->Form->GetFormValue('Connect', NULL) !== NULL;

      if (!$IsPostBack) {
         // Here are the initial data array values. that can be set by a plugin.
         $Data = array('Provider' => '', 'ProviderName' => '', 'UniqueID' => '', 'FullName' => '', 'Name' => '', 'Email' => '', 'Photo' => '', 'Target' => $this->Target());
         $this->Form->SetData($Data);
         $this->Form->AddHidden('Target', $this->Request->Get('Target', '/'));
      }

      // The different providers can check to see if they are being used and modify the data array accordingly.
      $this->EventArguments = array($Method);

      // Fire ConnectData event & error handling.
      $CurrentData = $this->Form->FormValues();
      try {
         $this->FireEvent('ConnectData');
      } catch (Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
         return $this->Render('ConnectError');
      } catch (Exception $Ex) {
         if (Debug())
            $this->Form->AddError($Ex);
         else
            $this->Form->AddError('There was an error fetching the connection data.');
         return $this->Render('ConnectError');
      }

      if (!UserModel::NoEmail()) {
         if (!$this->Form->GetFormValue('Email') || $this->Form->GetFormValue('EmailVisible')) {
            $this->Form->SetFormValue('EmailVisible', TRUE);
            $this->Form->AddHidden('EmailVisible', TRUE);

            if ($IsPostBack) {
               $this->Form->SetFormValue('Email', GetValue('Email', $CurrentData));
            }
         }
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

      $UserModel = Gdn::UserModel();

      // Check to see if there is an existing user associated with the information above.
      $Auth = $UserModel->GetAuthentication($this->Form->GetFormValue('UniqueID'), $this->Form->GetFormValue('Provider'));
      $UserID = GetValue('UserID', $Auth);

      // Check to synchronise roles upon connecting.
      if (($this->Data('Trusted') || C('Garden.SSO.SynchRoles')) && $this->Form->GetFormValue('Roles', NULL) !== NULL) {
         $SaveRoles = TRUE;

         // Translate the role names to IDs.
         $Roles = $this->Form->GetFormValue('Roles', NULL);
         $Roles = RoleModel::GetByName($Roles);
         $RoleIDs = array_keys($Roles);

         if (empty($RoleIDs)) {
            // The user must have at least one role. This protects that.
            $RoleIDs = $this->UserModel->NewUserRoleIDs();
         }

         $this->Form->SetFormValue('RoleID', $RoleIDs);
      } else {
         $SaveRoles = FALSE;
      }

      if ($UserID) {
         // The user is already connected.
         $this->Form->SetFormValue('UserID', $UserID);

         if (C('Garden.Registration.ConnectSynchronize', TRUE)) {
            $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            $Data = $this->Form->FormValues();

            // Don't overwrite the user photo if the user uploaded a new one.
            $Photo = GetValue('Photo', $User);
            if (!GetValue('Photo', $Data) || ($Photo && !StringBeginsWith($Photo, 'http'))) {
               unset($Data['Photo']);
            }

            // Synchronize the user's data.
            $UserModel->Save($Data, array('NoConfirmEmail' => TRUE, 'FixUnique' => TRUE, 'SaveRoles' => $SaveRoles));
         }

         // Always save the attributes because they may contain authorization information.
         if ($Attributes = $this->Form->GetFormValue('Attributes')) {
            $UserModel->SaveAttribute($UserID, $Attributes);
         }

         // Sign the user in.
         Gdn::Session()->Start($UserID, TRUE, (bool)$this->Form->GetFormValue('RememberMe', TRUE));
         Gdn::UserModel()->FireEvent('AfterSignIn');
//         $this->_SetRedirect(TRUE);
         $this->_SetRedirect($this->Request->Get('display') == 'popup');
      } elseif ($this->Form->GetFormValue('Name') || $this->Form->GetFormValue('Email')) {
         $NameUnique = C('Garden.Registration.NameUnique', TRUE);
         $EmailUnique = C('Garden.Registration.EmailUnique', TRUE);
         $AutoConnect = C('Garden.Registration.AutoConnect');

         // Get the existing users that match the name or email of the connection.
         $Search = FALSE;
         if ($this->Form->GetFormValue('Name') && $NameUnique) {
            $UserModel->SQL->OrWhere('Name', $this->Form->GetFormValue('Name'));
            $Search = TRUE;
         }
         if ($this->Form->GetFormValue('Email') && ($EmailUnique || $AutoConnect)) {
            $UserModel->SQL->OrWhere('Email', $this->Form->GetFormValue('Email'));
            $Search = TRUE;
         }

         if ($Search)
            $ExistingUsers = $UserModel->GetWhere()->ResultArray();
         else
            $ExistingUsers = array();

         // Check to automatically link the user.
         if ($AutoConnect && count($ExistingUsers) > 0) {
            foreach ($ExistingUsers as $Row) {
               if ($this->Form->GetFormValue('Email') == $Row['Email']) {
                  $UserID = $Row['UserID'];
                  $this->Form->SetFormValue('UserID', $UserID);
                  $Data = $this->Form->FormValues();

                  if (C('Garden.Registration.ConnectSynchronize', TRUE)) {
                     // Don't overwrite a photo if the user has already uploaded one.
                     $Photo = GetValue('Photo', $Row);
                     if (!GetValue('Photo', $Data) || ($Photo && !StringBeginsWith($Photo, 'http'))) {
                        unset($Data['Photo']);
                     }
                     $UserModel->Save($Data, array('NoConfirmEmail' => TRUE, 'FixUnique' => TRUE, 'SaveRoles' => $SaveRoles));
                  }

                  if ($Attributes = $this->Form->GetFormValue('Attributes')) {
                     $UserModel->SaveAttribute($UserID, $Attributes);
                  }

                  // Save the userauthentication link.
                  $UserModel->SaveAuthentication(array(
                      'UserID' => $UserID,
                      'Provider' => $this->Form->GetFormValue('Provider'),
                      'UniqueID' => $this->Form->GetFormValue('UniqueID')));

                  // Sign the user in.
                  Gdn::Session()->Start($UserID, TRUE, (bool)$this->Form->GetFormValue('RememberMe', TRUE));
                  Gdn::UserModel()->FireEvent('AfterSignIn');
         //         $this->_SetRedirect(TRUE);
                  $this->_SetRedirect($this->Request->Get('display') == 'popup');
                  $this->Render();
                  return;
               }
            }
         }

         $CurrentUserID = Gdn::Session()->UserID;

         // Massage the existing users.
         foreach ($ExistingUsers as $Index => $UserRow) {
            if ($EmailUnique && $UserRow['Email'] == $this->Form->GetFormValue('Email')) {
               $EmailFound = $UserRow;
               break;
            }

            if ($UserRow['Name'] == $this->Form->GetFormValue('Name')) {
               $NameFound = $UserRow;
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

         if (!isset($NameFound) && !$IsPostBack) {
            $this->Form->SetFormValue('ConnectName', $this->Form->GetFormValue('Name'));
         }

         $this->SetData('ExistingUsers', $ExistingUsers);

         if (UserModel::NoEmail())
            $EmailValid = TRUE;
         else
            $EmailValid = ValidateRequired($this->Form->GetFormValue('Email'));

         if ($this->Form->GetFormValue('Name') && $EmailValid && (!is_array($ExistingUsers) || count($ExistingUsers) == 0)) {
            // There is no existing user with the suggested name so we can just create the user.
            $User = $this->Form->FormValues();
            $User['Password'] = RandomString(50); // some password is required
            $User['HashMethod'] = 'Random';
            $User['Source'] = $this->Form->GetFormValue('Provider');
            $User['SourceID'] = $this->Form->GetFormValue('UniqueID');
            $User['Attributes'] = $this->Form->GetFormValue('Attributes', NULL);
            $User['Email'] = $this->Form->GetFormValue('ConnectEmail', $this->Form->GetFormValue('Email', NULL));

//            $UserID = $UserModel->InsertForBasic($User, FALSE, array('ValidateEmail' => FALSE, 'NoConfirmEmail' => TRUE, 'SaveRoles' => $SaveRoles));
            $UserID = $UserModel->Register($User, array('CheckCaptcha' => FALSE, 'ValidateEmail' => FALSE, 'NoConfirmEmail' => TRUE, 'SaveRoles' => $SaveRoles));

            $User['UserID'] = $UserID;
            $this->Form->SetValidationResults($UserModel->ValidationResults());

            if ($UserID) {
               $UserModel->SaveAuthentication(array(
                      'UserID' => $UserID,
                      'Provider' => $this->Form->GetFormValue('Provider'),
                      'UniqueID' => $this->Form->GetFormValue('UniqueID')));

               $this->Form->SetFormValue('UserID', $UserID);

               Gdn::Session()->Start($UserID, TRUE, (bool)$this->Form->GetFormValue('RememberMe', TRUE));
               Gdn::UserModel()->FireEvent('AfterSignIn');

               // Send the welcome email.
               if (C('Garden.Registration.SendConnectEmail', FALSE)) {
                  try {
                     $UserModel->SendWelcomeEmail($UserID, '', 'Connect', array('ProviderName' => $this->Form->GetFormValue('ProviderName', $this->Form->GetFormValue('Provider', 'Unknown'))));
                  } catch (Exception $Ex) {
                     // Do nothing if emailing doesn't work.
                  }
               }

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

               $User = FALSE;
               if (C('Garden.Registration.NameUnique')) {
                  // Check to see if there is already a user with the given name.
                  $User = $UserModel->GetWhere(array('Name' => $ConnectName))->FirstRow(DATASET_TYPE_ARRAY);
               }

               if (!$User) {
                  $this->Form->ValidateRule('ConnectName', 'ValidateUsername');
               }
            }
         } else {
            // The user selected an existing user.
            $ConnectNameEntered = FALSE;

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
               if ($this->Form->ValidateRule('ConnectPassword', 'ValidateRequired', sprintf(T('ValidateRequired'), T('Password')))) {
                  try {
                     if (!$PasswordHash->CheckPassword($this->Form->GetFormValue('ConnectPassword'), $User['Password'], $User['HashMethod'], $this->Form->GetFormValue('ConnectName'))) {
                        if ($ConnectNameEntered) {
                           $this->Form->AddError('The username you entered has already been taken.');
                        } else {
                           $this->Form->AddError('The password you entered is incorrect.');
                        }
                     }
                  } catch (Gdn_UserException $Ex) {
                     $this->Form->AddError($Ex);
                  }
               }
            }
         } elseif ($this->Form->ErrorCount() == 0) {
            // The user doesn't exist so we need to add another user.
            $User = $this->Form->FormValues();
            $User['Name'] = $User['ConnectName'];
            $User['Password'] = RandomString(50); // some password is required
            $User['HashMethod'] = 'Random';
            $UserID = $UserModel->Register($User, array('CheckCaptcha' => FALSE, 'NoConfirmEmail' => TRUE, 'SaveRoles' => $SaveRoles));
            $User['UserID'] = $UserID;
            $this->Form->SetValidationResults($UserModel->ValidationResults());

            if ($UserID) {
//               // Add the user to the default roles.
//               $UserModel->SaveRoles($UserID, C('Garden.Registration.DefaultRoles'));

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
            Gdn::Session()->Start($this->Form->GetFormValue('UserID'), TRUE, (bool)$this->Form->GetFormValue('RememberMe', TRUE));
            Gdn::UserModel()->FireEvent('AfterSignIn');
            $this->_SetRedirect(TRUE);
         }
      }

      $this->Render();
   }

   /**
    * After sign in, send them along.
    *
    * @since 2.0.0
    * @access protected
    *
    * @param bool $CheckPopup
    */
   protected function _SetRedirect($CheckPopup = FALSE) {
      $Url = Url($this->RedirectTo(), TRUE);

      $this->RedirectUrl = $Url;
      $this->MasterView = 'popup';
      $this->View = 'redirect';

      if ($this->_RealDeliveryType != DELIVERY_TYPE_ALL && $this->DeliveryType() != DELIVERY_TYPE_ALL) {
         $this->DeliveryMethod(DELIVERY_METHOD_JSON);
         $this->SetHeader('Content-Type', 'application/json');
      } elseif ($CheckPopup) {
         $this->AddDefinition('CheckPopup', $CheckPopup);
      } else {
         Redirect(Url($this->RedirectUrl));
      }
   }

   /**
    * Default to SignIn().
    *
    * @access public
    * @since 2.0.0
    */
   public function Index() {
      $this->View = 'SignIn';
      $this->SignIn();
   }

   /**
    * Auth via password.
    *
    * @access public
    * @since 2.0.0
    */
   public function Password() {
      $this->Auth('password');
   }

   /**
    * Auth via default method. Simpler, old version of SignIn().
    *
    * Events: SignIn
    *
    * @access public
    * @return void
    */
   public function SignIn2() {
      $this->FireEvent("SignIn");
      $this->Auth('default');
   }

   /**
    * Good afternoon, good evening, and goodnight.
    *
    * Events: SignOut
    *
    * @access public
    * @since 2.0.0
    *
    * @param string $TransientKey (default: "")
    */
   public function SignOut($TransientKey = "", $Override = "0") {
      $this->CheckOverride('SignOut', $this->Target(), $TransientKey);

      if (Gdn::Session()->ValidateTransientKey($TransientKey) || $this->Form->IsPostBack()) {
         $User = Gdn::Session()->User;

         $this->EventArguments['SignoutUser'] = $User;
         $this->FireEvent("BeforeSignOut");

         // Sign the user right out.
         Gdn::Session()->End();
         $this->SetData('SignedOut', TRUE);

         $this->EventArguments['SignoutUser'] = $User;
         $this->FireEvent("SignOut");

         $this->_SetRedirect();
      } elseif (!Gdn::Session()->IsValid())
         $this->_SetRedirect();

      $this->SetData('Override', $Override);
      $this->SetData('Target', $this->Target());
      $this->Leaving = FALSE;
      $this->Render();
   }

   /**
    * Signin process that multiple authentication methods.
    *
    * @access public
    * @since 2.0.0
    * @author Tim Gunter
    *
    * @param string $Method
    * @param array $Arg1
    * @return string Rendered XHTML template.
    */
   public function SignIn($Method = FALSE, $Arg1 = FALSE) {
      if (!$this->Request->IsPostBack())
         $this->CheckOverride('SignIn', $this->Target());

      Gdn::Session()->EnsureTransientKey();

      $this->AddJsFile('entry.js');
      $this->SetData('Title', T('Sign In'));
		$this->Form->AddHidden('Target', $this->Target());
      $this->Form->AddHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default.

      // Additional signin methods are set up with plugins.
      $Methods = array();

      $this->SetData('Methods', $Methods);
      $this->SetData('FormUrl', Url('entry/signin'));

      $this->FireEvent('SignIn');

      if ($this->Form->IsPostBack()) {
         $this->Form->ValidateRule('Email', 'ValidateRequired', sprintf(T('%s is required.'), T(UserModel::SigninLabelCode())));
         $this->Form->ValidateRule('Password', 'ValidateRequired');

         if (!$this->Request->IsAuthenticatedPostBack()) {
            $this->Form->AddError('Please try again.');
         }

         // Check the user.
         if ($this->Form->ErrorCount() == 0) {
            $Email = $this->Form->GetFormValue('Email');
            $User = Gdn::UserModel()->GetByEmail($Email);
            if (!$User)
               $User = Gdn::UserModel()->GetByUsername($Email);

            if (!$User) {
               $this->Form->AddError('@'.sprintf(T('User not found.'), strtolower(T(UserModel::SigninLabelCode()))));
            } else {
               // Check the password.
               $PasswordHash = new Gdn_PasswordHash();
               $Password = $this->Form->GetFormValue('Password');
               try {
                  $PasswordChecked = $PasswordHash->CheckPassword($Password, GetValue('Password', $User), GetValue('HashMethod', $User));

                  // Rate limiting
                  Gdn::UserModel()->RateLimit($User, $PasswordChecked);

                  if ($PasswordChecked) {
                     // Update weak passwords
                     $HashMethod = GetValue('HashMethod', $User);
                     if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
                        $Pw = $PasswordHash->HashPassword($Password);
                        Gdn::UserModel()->SetField(GetValue('UserID', $User), array('Password' => $Pw, 'HashMethod' => 'Vanilla'));
                     }

                     Gdn::Session()->Start(GetValue('UserID', $User), TRUE, (bool)$this->Form->GetFormValue('RememberMe'));
                     if (!Gdn::Session()->CheckPermission('Garden.SignIn.Allow')) {
                        $this->Form->AddError('ErrorPermission');
                        Gdn::Session()->End();
                     } else {
                     $ClientHour = $this->Form->GetFormValue('ClientHour');
                     $HourOffset = Gdn::Session()->User->HourOffset;
                     if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
                        $HourOffset = $ClientHour - date('G', time());
                     }

                        if ($HourOffset != Gdn::Session()->User->HourOffset) {
                           Gdn::UserModel()->SetProperty(Gdn::Session()->UserID, 'HourOffset', $HourOffset);
                        }

                        Gdn::UserModel()->FireEvent('AfterSignIn');

                        $this->_SetRedirect();
                     }
                  } else {
                     $this->Form->AddError('Invalid password.');
                  }
               } catch (Gdn_UserException $Ex) {
                  $this->Form->AddError($Ex);
               }
            }
         }

      } else {
         if ($Target = $this->Request->Get('Target'))
            $this->Form->AddHidden('Target', $Target);
         $this->Form->SetValue('RememberMe', TRUE);
      }

      return $this->Render();
   }

   /**
    * Create secure handshake with remote authenticator.
    *
    * @access public
    * @since 2.0.?
    * @author Tim Gunter
    *
    * @param string $AuthenticationSchemeAlias (default: 'default')
    */
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

      if (method_exists($Authenticator, 'GetRolesFromHandshake')) {
         $RemoteRoles = $Authenticator->GetRolesFromHandshake($Payload);
         if (!empty($RemoteRoles))
            $UserInfo['Roles'] = $RemoteRoles;
      }

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
               'Email'  => $UserInfo['UserEmail'],
               'Roles'  => GetValue('Roles', $UserInfo)
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

                  $this->InformMessage(T('There is already an account in this forum using your email address. Please create a new account, or enter the credentials for the existing account.'));
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

   /**
    * Attempt to syncronize user data from remote system into Dashboard.
    *
    * @access public
    * @since 2.0.?
    * @author Tim Gunter
    *
    * @param object $Authenticator
    * @param array $UserInfo
    * @param array $Payload
    */
   public function SyncScreen($Authenticator, $UserInfo, $Payload) {
      $this->AddJsFile('entry.js');
      $this->View = 'handshake';
      $this->HandshakeScheme = $Authenticator->GetAuthenticationSchemeAlias();
      $this->Form->SetModel($this->UserModel);
      $this->Form->AddHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', $this->Target());

      $PreservedKeys = array(
         'UserKey', 'Token', 'Consumer', 'Email', 'Name', 'Gender', 'HourOffset'
      );
      $UserID = 0;
      $Target = $this->Target();

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
    *
    * Events: Register
    *
    * @access public
    * @since 2.0.0
    *
    * @param string $InvitationCode Unique code given to invited user.
    */
   public function Register($InvitationCode = '') {
      if (!$this->Request->IsPostBack())
         $this->CheckOverride('Register', $this->Target());

      $this->FireEvent("Register");

      $this->Form->SetModel($this->UserModel);

      // Define gender dropdown options
      $this->GenderOptions = array(
         'u' => T('Unspecified'),
         'm' => T('Male'),
         'f' => T('Female')
      );

      // Make sure that the hour offset for new users gets defined when their account is created
      $this->AddJsFile('entry.js');

      $this->Form->AddHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', $this->Target());

      $this->SetData('NoEmail', UserModel::NoEmail());

      $RegistrationMethod = $this->_RegistrationView();
      $this->View = $RegistrationMethod;
      $this->$RegistrationMethod($InvitationCode);
   }

   /**
    * Select view/method to be used for registration (from config).
    *
    * @access protected
    * @since 2.0.0
    *
    * @return string Method name.
    */
   protected function _RegistrationView() {
      $RegistrationMethod = Gdn::Config('Garden.Registration.Method');
      if (!in_array($RegistrationMethod, array('Closed', 'Basic','Captcha','Approval','Invitation','Connect')))
         $RegistrationMethod = 'Basic';

      return 'Register'.$RegistrationMethod;
   }

   /**
    * Registration that requires approval.
    *
    * Events: RegistrationPending
    *
    * @access private
    * @since 2.0.0
    */
   private function RegisterApproval() {
      Gdn::UserModel()->AddPasswordStrength($this);

      // If the form has been posted back...
      if ($this->Form->IsPostBack()) {

         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', $this->UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', T('You must agree to the terms of service.'));
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Strength');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         $this->UserModel->Validation->ApplyRule('DiscoveryText', 'Required', 'Tell us why you want to join!');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');

         $this->FireEvent('RegisterValidation');

         try {
            $Values = $this->Form->FormValues();
            unset($Values['Roles']);
            $AuthUserID = $this->UserModel->Register($Values);
            if (!$AuthUserID) {
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            } else {
               // The user has been created successfully, so sign in now.
               Gdn::Session()->Start($AuthUserID);

               if ($this->Form->GetFormValue('RememberMe'))
                  Gdn::Authenticator()->SetIdentity($AuthUserID, TRUE);

               // Notification text
               $Label = T('NewApplicantEmail', 'New applicant:');
               $Story = Anchor(Gdn_Format::Text($Label.' '.$Values['Name']), ExternalUrl('dashboard/user/applicants'));

               $this->EventArguments['AuthUserID'] = $AuthUserID;
               $this->EventArguments['Story'] = &$Story;
               $this->FireEvent('RegistrationPending');
               $this->View = "RegisterThanks"; // Tell the user their application will be reviewed by an administrator.

               // Grab all of the users that need to be notified.
               $Data = Gdn::Database()->SQL()->GetWhere('UserMeta', array('Name' => 'Preferences.Email.Applicant'))->ResultArray();
               $ActivityModel = new ActivityModel();
               foreach ($Data as $Row) {
                  $ActivityModel->Add($AuthUserID, 'Applicant', $Story, $Row['UserID'], '', '/dashboard/user/applicants', 'Only');
               }
            }
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
         $this->Render();
      } else {
         $this->Render();
      }
   }

   /**
    * Basic/simple registration. Allows immediate access.
    *
    * Events: RegistrationSuccessful
    *
    * @access private
    * @since 2.0.0
    */
   private function RegisterBasic() {
      Gdn::UserModel()->AddPasswordStrength($this);

      if ($this->Form->IsPostBack() === TRUE) {
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', $this->UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', T('You must agree to the terms of service.'));
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Strength');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');

         $this->FireEvent('RegisterValidation');

         try {
            $Values = $this->Form->FormValues();
            unset($Values['Roles']);
            $AuthUserID = $this->UserModel->Register($Values);
            if ($AuthUserID == UserModel::REDIRECT_APPROVE) {
               $this->Form->SetFormValue('Target', '/entry/registerthanks');
               $this->_SetRedirect();
               return;
            } elseif (!$AuthUserID) {
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            } else {
               // The user has been created successfully, so sign in now.
               Gdn::Session()->Start($AuthUserID);

               if ($this->Form->GetFormValue('RememberMe'))
                  Gdn::Authenticator()->SetIdentity($AuthUserID, TRUE);

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
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
      }
      $this->Render();
   }

   /**
    * Deprecated since 2.0.18.
    */
   private function RegisterConnect() {
      throw NotFoundException();
   }

   /**
    * Captcha-authenticated registration. Used by default.
    *
    * Events: RegistrationSuccessful
    *
    * @access private
    * @since 2.0.0
    */
   private function RegisterCaptcha() {
      Gdn::UserModel()->AddPasswordStrength($this);

      include(CombinePaths(array(PATH_LIBRARY, 'vendors/recaptcha', 'functions.recaptchalib.php')));
      if ($this->Form->IsPostBack() === TRUE) {
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', $this->UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', T('You must agree to the terms of service.'));
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Strength');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');

         $this->FireEvent('RegisterValidation');

         try {
            $Values = $this->Form->FormValues();
            unset($Values['Roles']);
            $AuthUserID = $this->UserModel->Register($Values);
            if ($AuthUserID == UserModel::REDIRECT_APPROVE) {
               $this->Form->SetFormValue('Target', '/entry/registerthanks');
               $this->_SetRedirect();
               return;
            } elseif (!$AuthUserID) {
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
               if ($this->_DeliveryType != DELIVERY_TYPE_ALL)
                  $this->_DeliveryType = DELIVERY_TYPE_MESSAGE;

            } else {
               // The user has been created successfully, so sign in now.
					if (!Gdn::Session()->IsValid())
						Gdn::Session()->Start($AuthUserID, TRUE, (bool)$this->Form->GetFormValue('RememberMe'));

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
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
      }
      $this->Render();
   }

   /**
    * Registration not allowed.
    *
    * @access private
    * @since 2.0.0
    */
   private function RegisterClosed() {
      $this->Render();
   }

   /**
    * Invitation-only registration. Requires code.
    *
    * @param int $InvitationCode
    * @since 2.0.0
    */
   public function RegisterInvitation($InvitationCode = 0) {
      $this->Form->SetModel($this->UserModel);

      // Define gender dropdown options
      $this->GenderOptions = array(
         'u' => T('Unspecified'),
         'm' => T('Male'),
         'f' => T('Female')
      );

      if (!$this->Form->IsPostBack()) {
         $this->Form->SetValue('InvitationCode', $InvitationCode);
      }

      $InvitationModel = new InvitationModel();

      // Look for the invitation.
      $Invitation = $InvitationModel
         ->GetWhere(array('Code' => $this->Form->GetValue('InvitationCode')))
         ->FirstRow(DATASET_TYPE_ARRAY);

      if (!$Invitation) {
         $this->Form->AddError('Invitation not found.', 'Code');
      } else {
         if ($Expires = GetValue('DateExpires', $Invitation)) {
            $Expires = Gdn_Format::ToTimestamp($Expires);
            if ($Expires <= time()) {

            }
         }
      }

      $this->Form->AddHidden('ClientHour', date('Y-m-d H:00')); // Use the server's current hour as a default
      $this->Form->AddHidden('Target', $this->Target());

      Gdn::UserModel()->AddPasswordStrength($this);

      if ($this->Form->IsPostBack() === TRUE) {
         $this->InvitationCode = $this->Form->GetValue('InvitationCode');
         // Add validation rules that are not enforced by the model
         $this->UserModel->DefineSchema();
         $this->UserModel->Validation->ApplyRule('Name', 'Username', $this->UsernameError);
         $this->UserModel->Validation->ApplyRule('TermsOfService', 'Required', T('You must agree to the terms of service.'));
         $this->UserModel->Validation->ApplyRule('Password', 'Required');
         $this->UserModel->Validation->ApplyRule('Password', 'Strength');
         $this->UserModel->Validation->ApplyRule('Password', 'Match');
         // $this->UserModel->Validation->ApplyRule('DateOfBirth', 'MinimumAge');

         $this->FireEvent('RegisterValidation');

         try {
            $Values = $this->Form->FormValues();
            unset($Values['Roles']);
            $AuthUserID = $this->UserModel->Register($Values, array('Method' => 'Invitation'));

            if (!$AuthUserID) {
               $this->Form->SetValidationResults($this->UserModel->ValidationResults());
            } else {
               // The user has been created successfully, so sign in now.
               Gdn::Session()->Start($AuthUserID);
               if ($this->Form->GetFormValue('RememberMe'))
                  Gdn::Authenticator()->SetIdentity($AuthUserID, TRUE);

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
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
      } else {
         // Set some form defaults.
         if ($Name = GetValue('Name', $Invitation)) {
            $this->Form->SetValue('Name', $Name);
         }

         $this->InvitationCode = $InvitationCode;
      }

      // Make sure that the hour offset for new users gets defined when their account is created
      $this->AddJsFile('entry.js');
      $this->Render();
   }

   /**
    * @since 2.1
    */
   public function RegisterThanks() {
      $this->CssClass = 'SplashMessage NoPanel';
      $this->SetData('_NoMessages', TRUE);
      $this->SetData('Title', T('Thank You!'));
      $this->Render();
   }

   /**
    * Request password reset.
    *
    * @access public
    * @since 2.0.0
    */
   public function PasswordRequest() {
      Gdn::Locale()->SetTranslation('Email', T(UserModel::SigninLabelCode()));
      if ($this->Form->IsPostBack() === TRUE) {
         $this->Form->ValidateRule('Email', 'ValidateRequired');

         if ($this->Form->ErrorCount() == 0) {
            try {
               $Email = $this->Form->GetFormValue('Email');
               if (!$this->UserModel->PasswordRequest($Email)) {
                  $this->Form->SetValidationResults($this->UserModel->ValidationResults());
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
               $this->Form->AddError("Couldn't find an account associated with that email/username.");
         }
      }
      $this->Render();
   }

   /**
    * Do password reset.
    *
    * @access public
    * @since 2.0.0
    *
    * @param int $UserID Unique.
    * @param string $PasswordResetKey Authenticate with unique, 1-time code sent via email.
    */
   public function PasswordReset($UserID = '', $PasswordResetKey = '') {
      $PasswordResetKey = trim($PasswordResetKey);

      if (!is_numeric($UserID)
          || $PasswordResetKey == ''
          || $this->UserModel->GetAttribute($UserID, 'PasswordResetKey', '') != $PasswordResetKey
         ) {
         $this->Form->AddError('Failed to authenticate your password reset request. Try using the reset request form again.');
      }

      $Expires = $this->UserModel->GetAttribute($UserID, 'PasswordResetExpires');
      if ($this->Form->ErrorCount() === 0 && $Expires < time()) {
         $this->Form->AddError('@'.T('Your password reset token has expired.', 'Your password reset token has expired. Try using the reset request form again.'));
      }


      if ($this->Form->ErrorCount() == 0) {
         $User = $this->UserModel->GetID($UserID, DATASET_TYPE_ARRAY);
         if ($User) {
            $User = ArrayTranslate($User, array('UserID', 'Name', 'Email'));
            $this->SetData('User', $User);
         }
      } else {
         $this->SetData('Fatal', TRUE);
      }

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
            Gdn::Session()->Start($User->UserID, TRUE);
//            $Authenticator = Gdn::Authenticator()->AuthenticateWith('password');
//            $Authenticator->FetchData($Authenticator, array('Email' => $User->Email, 'Password' => $Password, 'RememberMe' => FALSE));
//            $AuthUserID = $Authenticator->Authenticate();
				Redirect('/');
         }
      }
      $this->Render();
   }

   /**
    * Confirm email address is valid via sent code.
    *
    * @access public
    * @since 2.0.0
    *
    * @param int $UserID
    * @param string $EmailKey Authenticate with unique, 1-time code sent via email.
    */
   public function EmailConfirm($UserID, $EmailKey = '') {
      $User = $this->UserModel->GetID($UserID);

      if (!$User)
         throw NotFoundException('User');

      $EmailConfirmed = $this->UserModel->ConfirmEmail($User, $EmailKey);
      $this->Form->SetValidationResults($this->UserModel->ValidationResults());

      if ($EmailConfirmed) {
         $UserID = GetValue('UserID', $User);
         Gdn::Session()->Start($UserID);
      }

      $this->SetData('EmailConfirmed', $EmailConfirmed);
      $this->SetData('Email', $User->Email);
      $this->Render();
   }

   /**
    * Send email confirmation message to user.
    *
    * @access public
    * @since 2.0.?
    *
    * @param int $UserID
    */
   public function EmailConfirmRequest($UserID = '') {
      if ($UserID && !Gdn::Session()->CheckPermission('Garden.Users.Edit'))
         $UserID = '';

      try {
         $this->UserModel->SendEmailConfirmationEmail($UserID);
      } catch (Exception $Ex) {}
      $this->Form->SetValidationResults($this->UserModel->ValidationResults());

      $this->Render();
   }

   /**
    * Does actual de-authentication of a user. Used by SignOut().
    *
    * @access public
    * @since 2.0.0
    *
    * @param string $AuthenticationSchemeAlias
    * @param string $TransientKey Unique value to prove intent.
    */
   public function Leave($AuthenticationSchemeAlias = 'default', $TransientKey = '') {
      Deprecated(__FUNCTION__);
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
               $Target = $this->Target();
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

   /**
    * Go to requested Target() or the default controller if none was set.
    *
    * @access public
    * @since 2.0.0
    *
    * @return string URL.
    */
   public function RedirectTo() {
      $Target = $this->Target();
		return $Target == '' ? Gdn::Router()->GetDestination('DefaultController') : $Target;
   }

   /**
    * Set where to go after signin.
    *
    * @access public
    * @since 2.0.0
    *
    * @param string $Target Where we're requested to go to.
    * @return string URL to actually go to (validated & safe).
    */
   public function Target($Target = FALSE) {
      if ($Target === FALSE) {
         $Target = $this->Form->GetFormValue('Target', FALSE);
         if (!$Target)
            $Target = $this->Request->Get('Target', '/');
      }

      // Make sure that the target is a valid url.
      if (!preg_match('`(^https?://)`', $Target)) {
         $Target = '/'.ltrim($Target, '/');

         // Never redirect back to signin.
         if (preg_match('`^/entry/signin`i', $Target))
            $Target = '/';
      } else {
         $MyHostname = parse_url(Gdn::Request()->Domain(),PHP_URL_HOST);
         $TargetHostname = parse_url($Target, PHP_URL_HOST);

         // Only allow external redirects to trusted domains.
         $TrustedDomains = C('Garden.TrustedDomains', TRUE);

         if (is_array($TrustedDomains)) {
            // Add this domain to the trusted hosts.
            $TrustedDomains[] = $MyHostname;
            $Sender->EventArguments['TrustedDomains'] = &$TrustedDomains;
            $this->FireEvent('BeforeTargetReturn');
         }

         if ($TrustedDomains === TRUE) {
            return $Target;
			} elseif (count($TrustedDomains) == 0) {
				// Only allow http redirects if they are to the same host name.
				if ($MyHostname != $TargetHostname)
					$Target = '';
			} else {
				// Loop the trusted domains looking for a match
				$Match = FALSE;
				foreach ($TrustedDomains as $TrustedDomain) {
					if (StringEndsWith($TargetHostname, $TrustedDomain, TRUE))
						$Match = TRUE;
				}
				if (!$Match)
					$Target = '';
			}
      }
      return $Target;
   }

}

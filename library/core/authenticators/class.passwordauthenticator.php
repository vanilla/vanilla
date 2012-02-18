<?php if (!defined('APPLICATION')) exit();

/**
 * Authentication Module: Local User/Password auth tokens.
 *
 * Validating, Setting, and Retrieving session data in cookies. The HMAC
 * Hashing method used here was inspired by Wordpress 2.5 and this document in
 * particular: http://www.cse.msu.edu/~alexliu/publications/Cookie/cookie.pdf
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_PasswordAuthenticator extends Gdn_Authenticator {
   
   public function __construct() {
      $this->_DataSourceType = Gdn_Authenticator::DATA_FORM;
      
      $this->HookDataField('Email', 'Email');
      $this->HookDataField('Password', 'Password');
      $this->HookDataField('RememberMe', 'RememberMe', FALSE);
      $this->HookDataField('ClientHour', 'ClientHour', FALSE);
      
      // Initialize built-in authenticator functionality
      parent::__construct();
   }

   /**
    * Returns the unique id assigned to the user in the database, 0 if the
    * username/password combination weren't found, or -1 if the user does not
    * have permission to sign in.
    *
    * @param string $Email The email address (or unique username) assigned to the user in the database.
    * @param string $Password The password assigned to the user in the database.
    * @return int The UserID of the authenticated user or 0 if one isn't found.
    */
   public function Authenticate($Email = '', $Password = '') {
      if (!$Email || !$Password) {
      
         // We werent given parameters, check if they exist in our DataSource
         if ($this->CurrentStep() != Gdn_Authenticator::MODE_VALIDATE)
            return Gdn_Authenticator::AUTH_INSUFFICIENT;
         
         // Get the values from the DataSource
         $Email = $this->GetValue('Email');
         $Password = $this->GetValue('Password');
         $PersistentSession = $this->GetValue('RememberMe');
         $ClientHour = $this->GetValue('ClientHour');
      } else {
         $PersistentSession = FALSE;
         $ClientHour = 0;
      }

      $UserID = 0;
   
      // Retrieve matching username/password values
      $UserModel = Gdn::Authenticator()->GetUserModel();
      $UserData = $UserModel->ValidateCredentials($Email, 0, $Password);
      if ($UserData !== FALSE) {
         // Get ID
         $UserID = $UserData->UserID;

         // Get Sign-in permission
         $SignInPermission = $UserData->Admin ? TRUE : FALSE;
         if ($SignInPermission === FALSE && !$UserData->Banned) {
            $PermissionModel = Gdn::Authenticator()->GetPermissionModel();
            foreach($PermissionModel->GetUserPermissions($UserID) as $Permissions) {
               $SignInPermission |= ArrayValue('Garden.SignIn.Allow', $Permissions, FALSE);
            }
         }

         // Update users Information
         $UserID = $SignInPermission ? $UserID : -1;
         if ($UserID > 0) {
            // Create the session cookie
            $this->SetIdentity($UserID, $PersistentSession);

            // Update some information about the user...
            $UserModel->UpdateVisit($UserID, $ClientHour);
            
            Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_SUCCESS);
            $this->FireEvent('Authenticated');
         } else {
            Gdn::Authenticator()->Trigger(Gdn_Authenticator::AUTH_DENIED);
         }
      }
      return $UserID;
   }
   
   public function CurrentStep() {
      // Was data submitted through the form already?
      if (is_object($this->_DataSource) && ($this->_DataSource == $this || $this->_DataSource->IsPostBack() === TRUE)) {
         return $this->_CheckHookedFields();
      }
      
      return Gdn_Authenticator::MODE_GATHER;
   }

   /**
    * Destroys the user's session cookie - essentially de-authenticating them.
    */
   public function DeAuthenticate() {
      $this->SetIdentity(NULL);
      
      return Gdn_Authenticator::AUTH_SUCCESS;
   }
   
   public function LoginResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }
   
   public function PartialResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function SuccessResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function LogoutResponse() {
      return Gdn_Authenticator::REACT_REDIRECT;
   }
   
   public function RepeatResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }
   
   // What to do if the entry/auth/* page is triggered but login is denied or fails
   public function FailedResponse() {
      return Gdn_Authenticator::REACT_RENDER;
   }
   
   public function WakeUp() {
      // Do nothing.
   }
   
   public function GetURL($URLType) {
      // We arent overriding anything
      return FALSE;
   }

}
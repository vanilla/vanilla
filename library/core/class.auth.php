<?php

class Gdn_Auth extends Gdn_Pluggable {

   protected $_AuthenticationSchemes = array();
   protected $_Authenticator = NULL;
   protected $_Authenticators = array();
   
   protected $_Protocol = 'http';
   protected $_Identity = null;
   protected $_UserModel = null;
   protected $_PermissionModel = null;
   
   public function __construct() {
      // Prepare Identity storage container
      $this->Identity();
      
      parent::__construct();
   }
   
   public function StartAuthenticator() {
   
      // Start the 'session'
      Gdn::Session()->Start();
      
      // Get list of enabled authenticators
      $AuthenticationSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      
      // Bring all enabled authenticator classes into the defined scope to allow them to be picked up by the plugin manager
      foreach ($AuthenticationSchemes as $AuthenticationSchemeAlias)
         $Registered = $this->RegisterAuthenticator($AuthenticationSchemeAlias);
   }
   
   public function RegisterAuthenticator($AuthenticationSchemeAlias) {
      $AuthenticatorClassPath = PATH_LIBRARY.'/core/authenticators/class.%sauthenticator.php';
      $Alias = strtolower($AuthenticationSchemeAlias);
      $Path = sprintf($AuthenticatorClassPath, $Alias);
      $AuthenticatorClass = sprintf('Gdn_%sAuthenticator',ucfirst($Alias));
      // Include the class if it exists
      if (!class_exists($AuthenticatorClass) && file_exists($Path)) {
         require_once($Path);
      }

      if (class_exists($AuthenticatorClass)) {
         $this->_AuthenticationSchemes[$Alias] = $Alias;
         
         // Now wake it up so it can do setup work
         $Authenticator = $this->AuthenticateWith($Alias, FALSE);
         $Authenticator->WakeUp();
      }
   }
   
   public function AuthenticateWith($AuthenticationSchemeAlias = 'default', $InheritAuthenticator = TRUE) {
      if ($AuthenticationSchemeAlias == 'default')
         $AuthenticationSchemeAlias = Gdn::Config('Garden.Authenticator.DefaultScheme', 'password');
      
      // Lowercase always, for great justice
      $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);
      
      // Check if we are allowing this kind of authentication right now
      if (!in_array($AuthenticationSchemeAlias, $this->_AuthenticationSchemes))
         throw new Exception("Tried to load authenticator '{$AuthenticationSchemeAlias}' which was not yet registered.");
      
      if (array_key_exists($AuthenticationSchemeAlias,$this->_Authenticators)) {
         if ($InheritAuthenticator)
            $this->_Authenticator = $this->_Authenticators[$AuthenticationSchemeAlias];
         return $this->_Authenticators[$AuthenticationSchemeAlias];
      }
      
      $AuthenticatorClassName = 'Gdn_'.ucfirst($AuthenticationSchemeAlias).'Authenticator';
      if (class_exists($AuthenticatorClassName)) {
         $Authenticator = new $AuthenticatorClassName();
         $this->_Authenticators[$AuthenticationSchemeAlias] = $Authenticator;
         if ($InheritAuthenticator)
            $this->_Authenticator = $this->_Authenticators[$AuthenticationSchemeAlias];
         
         return $this->_Authenticators[$AuthenticationSchemeAlias];
      }
   }
   
   public function GetAuthenticator($Default = 'default') {
      if (!$this->_Authenticator)
         $this->AuthenticateWith($Default);
      
      return $this->_Authenticator;
   }
   
   public function ReplaceAuthPlaceholders($PlaceholderString) {
      $Replacements = array_merge(array(
         'Session_TransientKey'     => '',
         'Username'                 => '',
         'UserID'                   => ''
      ),(Gdn::Session()->IsValid() ? array(
         'Session_TransientKey'     => Gdn::Session()->TransientKey(),
         'Username'                 => Gdn::Session()->User->Name,
         'UserID'                   => Gdn::Session()->User->UserID
      ) : array()));
      return Gdn_Format::VanillaSprintf($PlaceholderString, $Replacements);
   }
   
   /**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity() {
      $Result = $this->_Identity->GetIdentity();
      
      if ($Result < 0)
         $Result = 0;
      
      return $Result;
   }
   
   /**
    * @return PermissionModel
    */
   public function GetPermissionModel() {
      if ($this->_PermissionModel === NULL) {
         $this->_PermissionModel = Gdn::PermissionModel();
      }
      return $this->_PermissionModel;
   }

   /**
    * @return UserModel
    */
   public function GetUserModel() {
      if ($this->_UserModel === NULL) {
         $this->_UserModel = Gdn::UserModel();
      }
      return $this->_UserModel;
   }
   
   public function SetIdentity($Value, $Persist = FALSE) {
      $this->_Identity->SetIdentity($Value, $Persist);
   }
   
   public function Identity() {
      if (is_null($this->_Identity)) {
         $this->_Identity = Gdn::Factory('Identity');
         $this->_Identity->Init();
      }
      
      return $this->_Identity;
   }

   /**
    * @param PermissionModel $PermissionModel
    */
   public function SetPermissionModel($PermissionModel) {
      $this->_PermissionModel = $PermissionModel;
   }

   /**
    * @param UserModel $UserModel
    */
   public function SetUserModel($UserModel) {
      $this->_UserModel = $UserModel;
   }
   
   /**
    * Sets/gets the protocol for authentication (http or https).
    *
    * @return string
    */
   public function Protocol($Value = NULL) {
      if (!is_null($Value) && in_array($Value, array('http', 'https')))
         $this->_Protocol = $Value;
         
      return $this->_Protocol;
   }
   
   public function ReturningUser($User) {
      if ($this->_Identity->HasVolatileMarker($User->UserID))
         return FALSE;
         
      return TRUE;
   }

   /**
    * Returns the url used to register for an account in the application.
    *
    * @return string
    */
   public function RegisterUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_REGISTER, $Redirect);
   }
   
   /**
    * Returns the url used to sign in to the application.
    *
    * @return string
    */
   public function SignInUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_SIGNIN, $Redirect);
   }

   /**
    * Returns the url used to sign out of the application.
    *
    * @return string
    */
   public function SignOutUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_SIGNOUT, $Redirect);
   }
   
   protected function _GetURL($URLType, $Redirect) {
      $SessionAuthenticator = Gdn::Session()->GetPreference('Authenticator');
      $AuthenticationScheme = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';

      try {
         $Authenticator = $this->GetAuthenticator($AuthenticationScheme);
      } catch (Exception $e) {
         $Authenticator = $this->GetAuthenticator();
      }
      
      $Return = $Authenticator->GetURL($URLType);

      if (!$Return)
         $Return = sprintf(Gdn::Config('Garden.Authenticator.'.$URLType), $AuthenticationScheme, $Redirect);
      
      $Return = $this->ReplaceAuthPlaceholders($Return);
      if ($this->Protocol() == 'https')
         $Return = str_replace('http:', 'https:', Url($Return, TRUE));
      
      return $Return;
   }

}
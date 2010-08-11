<?php

class Gdn_Auth extends Gdn_Pluggable {

   protected $_AuthenticationSchemes = array();
   protected $_Authenticator = NULL;
   protected $_Authenticators = array();
   
   protected $_Protocol = 'http';
   protected $_Identity = null;
   protected $_UserModel = null;
   protected $_PermissionModel = null;
   
   protected $_AllowHandshake;
   
   public function __construct() {
      // Prepare Identity storage container
      $this->Identity();
      $this->_AllowHandshake = FALSE;
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
         $this->_AuthenticationSchemes[$Alias] = array(
            'Name'      => C("Garden.Authenticators.{$Alias}.Name", $Alias),
            'Configure' => FALSE
         );
         
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
      if (!array_key_exists($AuthenticationSchemeAlias, $this->_AuthenticationSchemes)) {
         throw new Exception("Tried to load authenticator '{$AuthenticationSchemeAlias}' which was not yet registered.");
      }
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
   
   /**
    * Gets a list of all the currently available authenticators installed
    */
   public function GetAvailable() {
      return $this->_AuthenticationSchemes;
   }
   
   public function GetAuthenticatorInfo($AuthenticationSchemeAlias) {
      return (array_key_exists($AuthenticationSchemeAlias, $this->_AuthenticationSchemes)) ? $this->_AuthenticationSchemes[$AuthenticationSchemeAlias] : FALSE;
   }
   
   public function ReplaceAuthPlaceholders($PlaceholderString, $ExtraReplacements = array()) {
      $Replacements = array_merge(array(
         'Session_TransientKey'     => '',
         'Username'                 => '',
         'UserID'                   => ''
      ),(Gdn::Session()->IsValid() ? array(
         'Session_TransientKey'     => Gdn::Session()->TransientKey(),
         'Username'                 => Gdn::Session()->User->Name,
         'UserID'                   => Gdn::Session()->User->UserID
      ) : array()),$ExtraReplacements);
      return Gdn_Format::VanillaSprintf($PlaceholderString, $Replacements);
   }
   
   public function AssociateUser($ProviderKey, $UserKey, $UserID = 0) {
      $SQL = Gdn::Database()->SQL();
      
      if ($UserID == 0) {
         try {
            $Success = $SQL->Insert('UserAuthentication',array(
               'UserID'          => 0,
               'ForeignUserKey'  => $UserKey,
               'ProviderKey'     => $ProviderKey
            ));
         } catch(Exception $e) { $Success = TRUE; }
      } else {
         $Success = $SQL->Replace('UserAuthentication',array(
               'UserID'          => $UserID
            ), array(
               'ForeignUserKey'  => $UserKey,
               'ProviderKey'     => $ProviderKey
            ));
      }
      
      if (!$Success) return FALSE;
      
      return array(
         'UserID'          => $UserID,
         'ForeignUserKey'  => $UserKey,
         'ProviderKey'     => $ProviderKey
      );
   }
   
   public function GetAssociation($UserKey, $ProviderKey = FALSE, $KeyType = Gdn_Authenticator::KEY_TYPE_TOKEN) {
      //die(print_r(func_get_args(),true));
      $SQL = Gdn::Database()->SQL();
      $Query = $SQL->Select('ua.UserID, ua.ForeignUserKey')
         ->From('UserAuthentication ua')
         ->Where('ua.ForeignUserKey', $UserKey)
         ->Where('UserID >', 0);
         
      if ($ProviderKey && $KeyType == Gdn_Authenticator::KEY_TYPE_TOKEN) {
         $Query->Join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
         ->Where('uat.Token', $ProviderKey);
      }
      
      if ($ProviderKey && $KeyType == Gdn_Authenticator::KEY_TYPE_PROVIDER) {
         $Query->Where('ua.ProviderKey', $ProviderKey);
      }
         
      $UserAssociation = $Query->Get()->FirstRow(DATASET_TYPE_ARRAY);
      return $UserAssociation ? $UserAssociation : FALSE;
   }
   
   public function AllowHandshake() {
      $this->_AllowHandshake = TRUE;
   }
   
   public function CanHandshake() {
      return $this->_AllowHandshake;
   }
   
   /**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity() {
      $Result = $this->GetRealIdentity();
      
      if ($Result < 0)
         $Result = 0;
      
      return $Result;
   }
   
   public function GetRealIdentity() {
      $Result = $this->_Identity->GetIdentity();
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
   
   public function GetURL($URLType, $Redirect) { return $this->_GetURL($URLType, $Redirect); }
   protected function _GetURL($URLType, $Redirect) {
      $SessionAuthenticator = Gdn::Session()->GetPreference('Authenticator');
      $AuthenticationScheme = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';

      try {
         $Authenticator = $this->GetAuthenticator($AuthenticationScheme);
      } catch (Exception $e) {
         $Authenticator = $this->GetAuthenticator();
      }
      
      if ($Redirect == '' || $Redirect == '/')
         $Redirect = Gdn::Router()->GetDestination('DefaultController');
      
      // Ask the authenticator for this URLType
      $Return = $Authenticator->GetURL($URLType);
      // If it doesn't know, get the default from our config file
      if (!$Return) $Return = Gdn::Config('Garden.Authenticator.'.$URLType);
      
      $ExtraReplacementParameters = array(
         'Path'   => $Redirect,
         'Scheme' => $AuthenticationScheme
      );
      
      // Extended return type, allows provider values to be replaced into final URL
      if (is_array($Return)) {
         $ExtraReplacementParameters = array_merge($ExtraReplacementParameters, $Return['Parameters']);
         $Return = $Return['URL'];
      }
      
      $FullRedirect = Url($Redirect, TRUE);
      $ExtraReplacementParameters['Redirect'] = $FullRedirect;
      $ExtraReplacementParameters['CurrentPage'] = $FullRedirect;
      
      // Support legacy sprintf syntax
      $Return = sprintf($Return, $AuthenticationScheme, $Redirect, $FullRedirect);
      
      // Support new named parameter '{}' syntax
      $Return = $this->ReplaceAuthPlaceholders($Return, $ExtraReplacementParameters);
      
      if ($this->Protocol() == 'https')
         $Return = str_replace('http:', 'https:', Url($Return, TRUE));
      
      return $Return;
   }

}
<?php if (!defined('APPLICATION')) exit();

/**
 * Authentication Manager
 * 
 * Manages the authentication system for vanilla, including all authentication
 * modules.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.10
 */

class Gdn_Auth extends Gdn_Pluggable {

   protected $_AuthenticationSchemes = array();
   protected $_Authenticator = NULL;
   protected $_Authenticators = array();
   
   protected $_Protocol = 'http';
   protected $_Identity = NULL;
   protected $_UserModel = NULL;
   protected $_PermissionModel = NULL;
   
   protected $_AllowHandshake;

   protected $_Started = FALSE;
   
   public function __construct() {
      // Prepare Identity storage container
      $this->Identity();
      $this->_AllowHandshake = FALSE;
      parent::__construct();
   }
   
   public function StartAuthenticator() {
      if (!C('Garden.Installed', FALSE)) return;
      // Start the 'session'
      Gdn::Session()->Start(FALSE, FALSE);
      
      // Get list of enabled authenticators
      $AuthenticationSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      
      // Bring all enabled authenticator classes into the defined scope to allow them to be picked up by the plugin manager
      foreach ($AuthenticationSchemes as $AuthenticationSchemeAlias)
         $Registered = $this->RegisterAuthenticator($AuthenticationSchemeAlias);

      $this->_Started = TRUE;
      $this->WakeUpAuthenticators();
      
      if (Gdn::Session()->IsValid() && !Gdn::Session()->CheckPermission('Garden.SignIn.Allow')) {
         return Gdn::Authenticator()->AuthenticateWith('user')->DeAuthenticate();
      }
   }
   
   public function RegisterAuthenticator($AuthenticationSchemeAlias) {
      $AuthenticatorClassPath = PATH_LIBRARY.'/core/authenticators/class.%sauthenticator.php';
      $Alias = strtolower($AuthenticationSchemeAlias);
      $Path = sprintf($AuthenticatorClassPath, $Alias);
      $AuthenticatorClass = sprintf('Gdn_%sAuthenticator',ucfirst($Alias));
      // Include the class if it exists
      if (!class_exists($AuthenticatorClass, FALSE) && file_exists($Path)) {
         require_once($Path);
      }
      
      if (class_exists($AuthenticatorClass)) {
         $this->_AuthenticationSchemes[$Alias] = array(
            'Name'      => C("Garden.Authenticators.{$Alias}.Name", $Alias),
            'Configure' => FALSE
         );
         
         // Now wake it up so it can do setup work
         if ($this->_Started) {
            $Authenticator = $this->AuthenticateWith($Alias, FALSE);
            $Authenticator->WakeUp();
         }
      }
   }

   public function WakeUpAuthenticators() {
      foreach ($this->_AuthenticationSchemes as $Alias => $Properties) {
         $Authenticator = $this->AuthenticateWith($Alias, FALSE);
         $Authenticator->Wakeup();
      }
   }
   
   public function AuthenticateWith($AuthenticationSchemeAlias = 'default', $InheritAuthenticator = TRUE) {
      if ($AuthenticationSchemeAlias == 'user') {
         if (Gdn::Session()->IsValid()) {
            $SessionAuthenticator = Gdn::Session()->GetPreference('Authenticator');
            $AuthenticationSchemeAlias = ($SessionAuthenticator) ? $SessionAuthenticator : 'default';
         }
      }
      
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
   
   public function EnableAuthenticationScheme($AuthenticationSchemeAlias, $SetAsDefault = FALSE) {
      // Get list of currently enabled schemes.
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      $ForceWrite = FALSE;
      
      // If the list is empty (shouldnt ever be empty), add 'password' to it.
      if (!is_array($EnabledSchemes)) {
         $ForceWrite = TRUE;
         $EnabledSchemes = array('password');
      }
      
      // First, loop through the list and remove any instances of the supplied authentication scheme
      $HaveScheme = FALSE;
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == $AuthenticationSchemeAlias) {
            if ($HaveScheme === TRUE)
               unset($EnabledSchemes[$SchemeIndex]);
            $HaveScheme = TRUE;
         }
      }
      
      // Now add the new scheme to the list (once)
      if (!$HaveScheme || $ForceWrite) {
         array_push($EnabledSchemes, $AuthenticationSchemeAlias);
         SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
      }
      
      if ($SetAsDefault == TRUE) {
         $this->SetDefaultAuthenticator($AuthenticationSchemeAlias);
      }
   }
   
   public function DisableAuthenticationScheme($AuthenticationSchemeAlias) {
      $this->UnsetDefaultAuthenticator($AuthenticationSchemeAlias);
      
      $ForceWrite = FALSE;
      
		// Remove this authenticator from the enabled schemes collection.
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      // If the list is empty (shouldnt ever be empty), add 'password' to it.
      if (!is_array($EnabledSchemes)) {
         $ForceWrite = TRUE;
         $EnabledSchemes = array('password');
      }
      
      $HadScheme = FALSE;
      // Loop through the list and remove any instances of the supplied authentication scheme
      foreach ($EnabledSchemes as $SchemeIndex => $SchemeKey) {
         if ($SchemeKey == $AuthenticationSchemeAlias) {
            unset($EnabledSchemes[$SchemeIndex]);
            $HadScheme = TRUE;
         }
      }
      
      if ($HadScheme || $ForceWrite) {
         SaveToConfig('Garden.Authenticator.EnabledSchemes', $EnabledSchemes);
      }
   }
   
   public function UnsetDefaultAuthenticator($AuthenticationSchemeAlias) {
      $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);
      if (C('Garden.Authenticator.DefaultScheme') == $AuthenticationSchemeAlias) {
         RemoveFromConfig('Garden.Authenticator.DefaultScheme');
         return TRUE;
      }
      
      return FALSE;
   }
   
   public function SetDefaultAuthenticator($AuthenticationSchemeAlias) {
      $AuthenticationSchemeAlias = strtolower($AuthenticationSchemeAlias);
      $EnabledSchemes = Gdn::Config('Garden.Authenticator.EnabledSchemes', array());
      if (!in_array($AuthenticationSchemeAlias, $EnabledSchemes)) return FALSE;

      SaveToConfig('Garden.Authenticator.DefaultScheme', $AuthenticationSchemeAlias);
      return TRUE;
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
      if ($UserID == 0) {
         try {
            $Success = Gdn::SQL()->Insert('UserAuthentication',array(
               'UserID'          => 0,
               'ForeignUserKey'  => $UserKey,
               'ProviderKey'     => $ProviderKey
            ));
            $Success = TRUE;
         } catch(Exception $e) { $Success = TRUE; }
      } else {
         $Success = Gdn::SQL()->Replace('UserAuthentication',array(
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
      $Query = Gdn::SQL()->Select('ua.UserID, ua.ForeignUserKey, uat.Token')
         ->From('UserAuthentication ua')
         ->Join('UserAuthenticationToken uat', 'ua.ForeignUserKey = uat.ForeignUserKey', 'left')
         ->Where('ua.ForeignUserKey', $UserKey)
         ->Where('UserID >', 0);
         
      if ($ProviderKey && $KeyType == Gdn_Authenticator::KEY_TYPE_TOKEN) {
         $Query->Where('uat.Token', $ProviderKey);
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
   
   public function IsPrimary($AuthenticationSchemeAlias) {
      return ($AuthenticationSchemeAlias == strtolower(Gdn::Config('Garden.Authenticator.DefaultScheme', 'password')));
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
   
   /**
    *
    * @return type Gdn_CookieIdentity
    */
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
   
   public function RemoteRegisterUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_REMOTE_REGISTER, $Redirect);
   }
   
   public function RemoteSignInUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_REMOTE_SIGNIN, $Redirect);
   }
   
   public function RemoteSignOutUrl($Redirect = '/') {
      return $this->_GetURL(Gdn_Authenticator::URL_REMOTE_SIGNOUT, $Redirect);
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
      
      if (!is_null($Redirect) && ($Redirect == '' || $Redirect == '/')) {
         $Redirect = Gdn::Router()->GetDestination('DefaultController');
      }
         
      if (is_null($Redirect)) {
         $Redirect = '';
      }
         
      // Ask the authenticator for this URLType
      $Return = $Authenticator->GetURL($URLType);
      
      // If it doesn't know, get the default from our config file
      if (!$Return) $Return = C('Garden.Authenticator.'.$URLType, FALSE);
      if (!$Return) return FALSE;
      
      $ExtraReplacementParameters = array(
         'Path'   => $Redirect,
         'Scheme' => $AuthenticationScheme
      );
      
      // Extended return type, allows provider values to be replaced into final URL
      if (is_array($Return)) {
         $ExtraReplacementParameters = array_merge($ExtraReplacementParameters, $Return['Parameters']);
         $Return = $Return['URL'];
      }
      
      $FullRedirect = ($Redirect != '') ? Url($Redirect, TRUE) : '';
      $ExtraReplacementParameters['Redirect'] = $FullRedirect;
      $ExtraReplacementParameters['CurrentPage'] = $FullRedirect;
      
      // Support legacy sprintf syntax
      $Return = sprintf($Return, $AuthenticationScheme, urlencode($Redirect), $FullRedirect);
      
      // Support new named parameter '{}' syntax
      $Return = $this->ReplaceAuthPlaceholders($Return, $ExtraReplacementParameters);
      
      if ($this->Protocol() == 'https')
         $Return = str_replace('http:', 'https:', Url($Return, TRUE));
      
      return $Return;
   }
   
   public function Trigger($AuthResponse, $UserData = NULL) {
      if (!is_null($UserData)) 
         $this->EventArguments['UserData'] = $UserData;
      else
         $this->EventArguments['UserData'] = FALSE;
         
      switch ($AuthResponse) {
         case Gdn_Authenticator::AUTH_SUCCESS:
            $this->FireEvent('AuthSuccess');
         break;
         case Gdn_Authenticator::AUTH_PARTIAL:
            $this->FireEvent('AuthPartial');
         break;
         case Gdn_Authenticator::AUTH_DENIED:
            $this->FireEvent('AuthDenied');
         break;
         case Gdn_Authenticator::AUTH_INSUFFICIENT:
            $this->FireEvent('AuthInsufficient');
         break;
         case Gdn_Authenticator::AUTH_PERMISSION:
            $this->FireEvent('AuthPermission');
         break;
         case Gdn_Authenticator::AUTH_ABORTED:
            $this->FireEvent('AuthAborted');
         break;
         case Gdn_Authenticator::AUTH_CREATED:
            $this->FireEvent('AuthCreated');
         break;
      }
   }

}
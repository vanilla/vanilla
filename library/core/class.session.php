<?php if (!defined('APPLICATION')) exit();

/**
 * Session manager
 * 
 * Handles user information throughout a session. This class is a singleton.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Session {


   /**
    * Unique user identifier.
    *
    * @var int
    */
   public $UserID;


   /**
    * A User object containing properties relevant to session
    *
    * @var object
    */
   public $User;


   /**
    * Attributes of the current user.
    *
    * @var object
    */
   protected $_Attributes;

   /**
    * Permissions of the current user.
    *
    * @var object
    */
   protected $_Permissions;


   /**
    * Preferences of the current user.
    *
    * @var object
    */
   protected $_Preferences;


   /**
    * The current user's transient key.
    *
    * @var object
    */
   protected $_TransientKey;


   /**
    * Private constructor prevents direct instantiation of object
    *
    */
   public function __construct() {
      $this->UserID = 0;
      $this->User = FALSE;
      $this->_Attributes = array();
      $this->_Permissions = array();
      $this->_Preferences = array();
      $this->_TransientKey = FALSE;
   }

   /**
    * Checks the currently authenticated user's permissions for the specified
    * permission. Returns a boolean value indicating if the action is
    * permitted.
    *
    * @param mixed $Permission The permission (or array of permissions) to check.
    * @param int $JunctionID The JunctionID associated with $Permission (ie. A discussion category identifier).
    * @param bool $FullMatch If $Permission is an array, $FullMatch indicates if all permissions specified are required. If false, the user only needs one of the specified permissions.
    * @param string $JunctionTable The name of the junction table for a junction permission.
	 * @param in $JunctionID The ID of the junction permission.
	 * * @return boolean
    */
   public function CheckPermission($Permission, $FullMatch = TRUE, $JunctionTable = '', $JunctionID = '') {
      if (is_object($this->User)) {
         if ($this->User->Banned || GetValue('Deleted', $this->User))
            return FALSE;
         elseif ($this->User->Admin)
            return TRUE;
      }

      // Allow wildcard permission checks (e.g. 'any' Category)
      if ($JunctionID == 'any')
         $JunctionID = '';

      $Permissions = $this->GetPermissions();
      if ($JunctionTable && !C('Garden.Permissions.Disabled.'.$JunctionTable)) {
         // Junction permission ($Permissions[PermissionName] = array(JunctionIDs))
         if (is_array($Permission)) {
            foreach ($Permission as $PermissionName) {
               if($this->CheckPermission($PermissionName, FALSE, $JunctionTable, $JunctionID)) {
						if(!$FullMatch)
							return TRUE;
					} else {
						if($FullMatch)
							return FALSE;
					}
            }
            return TRUE;
         } else {
            if ($JunctionID !== '') {
               $Result = array_key_exists($Permission, $Permissions)
                  && is_array($Permissions[$Permission])
                  && in_array($JunctionID, $Permissions[$Permission]);
            } else {
               $Result = array_key_exists($Permission, $Permissions)
                  && is_array($Permissions[$Permission])
                  && count($Permissions[$Permission]);
            }
            return $Result;
         }
      } else {
         // Non-junction permission ($Permissions = array(PermissionNames))
         if (is_array($Permission)) {
            return ArrayInArray($Permission, $Permissions, $FullMatch);
         } else {
            return in_array($Permission, $Permissions) || array_key_exists($Permission, $Permissions);
         }
      }
   }

   /**
    * End a session
    *
    * @param Gdn_Authenticator $Authenticator
    */
   public function End($Authenticator = NULL) {
      if ($Authenticator == NULL)
         $Authenticator = Gdn::Authenticator();

      $Authenticator->AuthenticateWith()->DeAuthenticate();
      $this->SetCookie('-Vv', NULL, -3600);
      
      $this->UserID = 0;
      $this->User = FALSE;
      $this->_Attributes = array();
      $this->_Permissions = array();
      $this->_Preferences = array();
      $this->_TransientKey = FALSE;
   }

   /**
    * Returns all "allowed" permissions for the authenticated user in a
    * one-dimensional array of permission names.
    *
    * @return array
    */
   public function GetPermissions() {
      return is_array($this->_Permissions) ? $this->_Permissions : array();
   }
   
   public function GetCookie($Suffix, $Default = NULL) {
      return GetValue(C('Garden.Cookie.Name').$Suffix, $_COOKIE, $Default);
   }
   
   public function SetCookie($Suffix, $Value, $Expires) {
      $Name = C('Garden.Cookie.Name').$Suffix;
      $Path = C('Garden.Cookie.Path');
      $Domain = C('Garden.Cookie.Domain');
      
      // If the domain being set is completely incompatible with the current domain then make the domain work.
      $CurrentHost = Gdn::Request()->Host();
      if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
         $Domain = '';
      
      // Allow people to specify up to a year of expiry.
      if (abs($Expires) < 31556926)
         $Expires = time() + $Expires;
      
      setcookie($Name, $Value, $Expires, $Path, $Domain);
      $_COOKIE[$Name] = $Value;
   }
   
   public function NewVisit() {
      static $NewVisit = NULL;
      
      if ($NewVisit !== NULL)
         return $NewVisit;
      
      if (!$this->User)
         return FALSE;
      
      $Current = $this->GetCookie('-Vv');
      $Now = time();
      $TimeToExpire = 1200; // 20 minutes
      $Expires = $Now + $TimeToExpire;
      
      // Figure out if this is a new visit.
      if ($Current)
         $NewVisit = FALSE; // user has cookie, not a new visit.
      elseif (Gdn_Format::ToTimeStamp($this->User->DateLastActive) + $TimeToExpire > $Now)
         $NewVisit = FALSE; // user was last active less than 20 minutes ago, not a new visit.
      else
         $NewVisit = TRUE;
      
      $this->SetCookie('-Vv', $Now, $Expires);
      
      return $NewVisit;
   }

	/**
    *
    *
	* @todo Add description.
	* @param string|array $PermissionName
	* @param mixed $Value
	* @return NULL
	*/

	public function SetPermission($PermissionName, $Value = NULL) {
		if (is_string($PermissionName)) {
			if ($Value === NULL || $Value === TRUE) 
            $this->_Permissions[] = $PermissionName;
         elseif ($Value === FALSE) {
            $Index = array_search($PermissionName, $this->_Permissions);
            if ($Index !== FALSE)
               unset($this->_Permissions[$Index]);
			} elseif (is_array($Value)) 
            $this->_Permissions[$PermissionName] = $Value;
		} elseif (is_array($PermissionName)) {
			if (array_key_exists(0, $PermissionName))
				foreach ($PermissionName as $Name) $this->SetPermission($Name);
			else
				foreach ($PermissionName as $Name => $Value) $this->SetPermission($Name, $Value);
		}
    }

   /**
    * Gets the currently authenticated user's preference for the specified
    * $PreferenceName.
    *
    * @param string $PreferenceName The name of the preference to get.
    * @param mixed $DefaultValue The default value to return if the preference does not exist.
    * @return mixed
    */
   public function GetPreference($PreferenceName, $DefaultValue = FALSE) {
      // WARNING: THIS DOES NOT CHECK THE DEFAULT CONFIG-DEFINED SETTINGS. 
      // IF A USER HAS NEVER SAVED THEIR PREFERENCES, THIS WILL RETURN 
      // INCORRECT VALUES.
      return ArrayValue($PreferenceName, $this->_Preferences, $DefaultValue);
   }

   /**
    * Gets the currently authenticated user's attribute for the specified
    * $AttributeName.
    *
    * @param unknown_type $AttributeName The name of the attribute to get.
    * @param string $DefaultValue The default value to return if the attribute does not exist.
    * @return mixed
    */
   public function GetAttribute($AttributeName, $DefaultValue = FALSE) {
      if (is_array($this->_Attributes)) {
         return ArrayValue($AttributeName, $this->_Attributes, $DefaultValue);
      }
      return $DefaultValue;
   }

   /**
    * @return array
    * @todo add doc
    */
   public function GetAttributes() {
      return is_array($this->_Attributes) ? $this->_Attributes : array();
   }

   /**
    * This is the singleton method that return the static
    * Configuration::Instance.
    *
    * @return Session
    */
   public static function GetInstance() {
      if (!isset(self::$_Instance)) {
         $c = __CLASS__;
         self::$_Instance = new $c();
      }
      return self::$_Instance;
   }

   /**
    * Ensure that there is an active session.
    *
    * If there isn't an active session, send the user to the SignIn Url
    *
    * @return boolean
    */
   public function IsValid() {
      return $this->UserID > 0;
   }

   /**
    * Authenticates the user with the provided Authenticator class.
    *
    * @param int $UserID The UserID to start the session with.
    * @param bool $SetIdentity Whether or not to set the identity (cookie) or make this a one request session.
    * @param bool $Persist If setting an identity, should we persist it beyond browser restart?
    */
   public function Start($UserID = FALSE, $SetIdentity = TRUE, $Persist = FALSE) {
      if (!C('Garden.Installed', FALSE)) return;
      // Retrieve the authenticated UserID from the Authenticator module.
      $UserModel = Gdn::Authenticator()->GetUserModel();
      $this->UserID = $UserID !== FALSE ? $UserID : Gdn::Authenticator()->GetIdentity();
      $this->User = FALSE;

      // Now retrieve user information
      if ($this->UserID > 0) {
         // Instantiate a UserModel to get session info
         $this->User = $UserModel->GetSession($this->UserID);

         if ($this->User) {
            if ($SetIdentity)
               Gdn::Authenticator()->SetIdentity($this->UserID, $Persist);
            
            $UserModel->EventArguments['User'] =& $this->User;
            $UserModel->FireEvent('AfterGetSession');

            $this->_Permissions = Gdn_Format::Unserialize($this->User->Permissions);
            $this->_Preferences = Gdn_Format::Unserialize($this->User->Preferences);
            $this->_Attributes = Gdn_Format::Unserialize($this->User->Attributes);
            $this->_TransientKey = is_array($this->_Attributes) ? ArrayValue('TransientKey', $this->_Attributes) : FALSE;

            if ($this->_TransientKey === FALSE)
               $this->_TransientKey = $UserModel->SetTransientKey($this->UserID);
            
            // Save any visit-level information.
            $UserModel->UpdateVisit($this->UserID);

         } else {
            $this->UserID = 0;
            $this->User = FALSE;
            if ($SetIdentity)
               Gdn::Authenticator()->SetIdentity(NULL);
         }
      } else {
         // Grab the transient key from the cookie. This doesn't always get set but we'll try it here anyway.
         $this->_TransientKey = GetAppCookie('tk');
      }
      // Load guest permissions if necessary
      if ($this->UserID == 0)
         $this->_Permissions = Gdn_Format::Unserialize($UserModel->DefinePermissions(0));
   }

   /**
    * Sets a value in the $this->_Attributes array. This setting will persist
    * only to the end of the page load. It is not intended for making permanent
    * changes to user attributes.
    *
    * @param string|array $Name
    * @param mixed $Value
    * @todo check argument type
    */
   public function SetAttribute($Name, $Value = '') {
      if (!is_array($Name))
         $Name = array($Name => $Value);

      foreach($Name as $Key => $Val) {
         if ($Val === NULL)
            unset($this->_Attributes[$Key]);
         $this->_Attributes[$Key] = $Val;
      }
   }

   /**
    * Sets a value in the $this->_Preferences array. This setting will persist
    * changes to user prefs.
    *
    * @param string|array $Name
    * @param mixed $Value
    * @todo check argument type
    */
   public function SetPreference($Name, $Value = '', $SaveToDatabase = TRUE) {
      if (!is_array($Name))
         $Name = array($Name => $Value);

      foreach($Name as $Key => $Val) {
         $this->_Preferences[$Key] = $Val;
      }

      if ($SaveToDatabase && $this->UserID > 0) {
         $UserModel = Gdn::UserModel();
         $UserModel->SavePreference($this->UserID, $Name);
      }
   }
   
   public function EnsureTransientKey() {
      if (!$this->_TransientKey) {
         // Generate a transient key in the browser.
         $tk = substr(md5(microtime()), 0, 16);
         SetAppCookie('tk', $tk);
         $this->_TransientKey = $tk;
      }
      return $this->_TransientKey;
   }

   /**
    * Returns the transient key for the authenticated user.
    *
    * @return string
    * @todo check return type
    */
   public function TransientKey($NewKey = NULL) {
      if (!is_null($NewKey)) {
         $this->_TransientKey = Gdn::Authenticator()->GetUserModel()->SetTransientKey($this->UserID, $NewKey);
      }

//      if ($this->_TransientKey)
         return $this->_TransientKey;
//      else
//         return RandomString(12); // Postbacks will never be authenticated if transientkey is not defined.
   }

   /**
    * Validates that $ForeignKey was generated by the current user.
    *
    * @param string $ForeignKey The key to validate.
    * @return bool
    */
   public function ValidateTransientKey($ForeignKey, $ValidateUser = TRUE) {
      static $ForceValid = FALSE;
      
      if ($ForeignKey === TRUE)
         $ForceValid = TRUE;
      
      if (!$ForceValid && $ValidateUser && $this->UserID <= 0)
         return FALSE;
      
      // Checking the postback here is a kludge, but is absolutely necessary until we can test the ValidatePostBack more.
      return ($ForceValid && Gdn::Request()->IsPostBack()) || ($ForeignKey == $this->_TransientKey && $this->_TransientKey !== FALSE);
   }
	
	/**
	 * Place a name/value pair into the user's session stash.
	 */
	public function Stash($Name = '', $Value = '', $UnsetOnRetrieve = TRUE) {
		if ($Name == '')
			return;
		
      // Grab the user's session
      $Session = $this->_GetStashSession($Value);
      if (!$Session)
         return;
      
      // Stash or unstash the value depending on inputs
      if ($Name != '' && $Value != '') {
         $Session->Attributes[$Name] = $Value;
      } else if ($Name != '') {
         $Value = GetValue($Name, $Session->Attributes);
			if ($UnsetOnRetrieve)
				unset($Session->Attributes[$Name]);
      }
      // Update the attributes
      if ($Name != '') {
         Gdn::SQL()->Put(
            'Session',
            array(
               'DateUpdated' => Gdn_Format::ToDateTime(),
               'Attributes' => serialize($Session->Attributes)
            ),
            array(
               'SessionID' => $Session->SessionID
            )
         );
      }
      return $Value;
	}
	   
	/**
	 * Used by $this->Stash() to create & manage sessions for users & guests.
	 * This is a stop-gap solution until full session management for users &
	 * guests can be imlemented.
	 */
   private function _GetStashSession($ValueToStash) {
      $CookieName = C('Garden.Cookie.Name', 'Vanilla');
      $Name = $CookieName.'SessionID';

      // Grab the entire session record
      $SessionID = GetValue($Name, $_COOKIE, '');
      
      // If there is no session, and no value for saving, return;
      if ($SessionID == '' && $ValueToStash == '')
         return FALSE;
      
      $Session = Gdn::SQL()
         ->Select()
         ->From('Session')
         ->Where('SessionID', $SessionID)
         ->Get()
         ->FirstRow();

      if (!$Session) {
         $SessionID = md5(mt_rand());
         $TransientKey = substr(md5(mt_rand()), 0, 11).'!';
         // Save the session information to the database.
         Gdn::SQL()->Insert(
            'Session',
            array(
               'SessionID' => $SessionID,
               'UserID' => Gdn::Session()->UserID,
               'TransientKey' => $TransientKey,
               'DateInserted' => Gdn_Format::ToDateTime(),
               'DateUpdated' => Gdn_Format::ToDateTime()
            )
         );
         Trace("Inserting session stash $SessionID");
         
         $Session = Gdn::SQL()
            ->Select()
            ->From('Session')
            ->Where('SessionID', $SessionID)
            ->Get()
            ->FirstRow();
            
         // Save a session cookie
         $Path = C('Garden.Cookie.Path', '/');
         $Domain = C('Garden.Cookie.Domain', '');
         $Expire = 0;
         
         // If the domain being set is completely incompatible with the current domain then make the domain work.
         $CurrentHost = Gdn::Request()->Host();
         if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
            $Domain = '';
         
         setcookie($Name, $SessionID, $Expire, $Path, $Domain);
         $_COOKIE[$Name] = $SessionID;
      }
      $Session->Attributes = @unserialize($Session->Attributes);
      if (!$Session->Attributes)
         $Session->Attributes = array();
      
      return $Session;
   }


}

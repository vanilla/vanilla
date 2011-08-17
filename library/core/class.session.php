<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Handles user information throughout a session. This class is a singleton.
 *
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */


/**
 * Handles user information throughout a session. This class is a singleton.
 *
 * @package Garden
 * @todo update doc to be more specific with properties type if possible.
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
         if ($this->User->Admin)
            return TRUE;
         elseif ($this->User->Banned || GetValue('Deleted', $this->User))
            return FALSE;
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
			if ($Value === NULL) $this->_Permissions[] = $PermissionName;
			elseif (is_array($Value)) $this->_Permissions[$PermissionName] = $Value;
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
      $this->UserID = $UserID ? $UserID : Gdn::Authenticator()->GetIdentity();
      $this->User = FALSE;

      // Now retrieve user information
      if ($this->UserID > 0) {
         // Instantiate a UserModel to get session info
         $this->User = $UserModel->GetSession($this->UserID);

         if ($this->User) {
            if ($SetIdentity) {
               Gdn::Authenticator()->SetIdentity($this->UserID, $Persist);
            }

            $Returning = Gdn::Authenticator()->ReturningUser($this->User);
            if ($Returning) {
               $HourOffset = GetValue('HourOffset', $this->User->Attributes);
               $UserModel->UpdateLastVisit($this->UserID, $this->User->Attributes, $HourOffset);
            }
            
            $UserModel->EventArguments['User'] =& $this->User;
            $UserModel->FireEvent('AfterGetSession');

            $this->_Permissions = Gdn_Format::Unserialize($this->User->Permissions);
            $this->_Preferences = Gdn_Format::Unserialize($this->User->Preferences);
            $this->_Attributes = Gdn_Format::Unserialize($this->User->Attributes);
            $this->_TransientKey = is_array($this->_Attributes) ? ArrayValue('TransientKey', $this->_Attributes) : FALSE;

            if ($this->_TransientKey === FALSE)
               $this->_TransientKey = $UserModel->SetTransientKey($this->UserID);

            // If the user hasn't been active in the session-time, update their date last active
            $SessionLength = Gdn::Config('Garden.Session.Length', '15 minutes');
            if (Gdn_Format::ToTimestamp($this->User->DateLastActive) < strtotime($SessionLength.' ago'))
               $UserModel->Save(array('UserID' => $this->UserID, 'DateLastActive' => Gdn_Format::ToDateTime()));

         } else {
            $this->UserID = 0;
            $this->User = FALSE;
            if ($SetIdentity)
               Gdn::Authenticator()->SetIdentity(NULL);
         }
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

      if ($this->_TransientKey !== FALSE)
         return $this->_TransientKey;
      else
         return RandomString(12); // Postbacks will never be authenticated if transientkey is not defined.
   }

   /**
    * Validates that $ForeignKey was generated by the current user.
    *
    * @param string $ForeignKey The key to validate.
    * @return unknown
    */
   public function ValidateTransientKey($ForeignKey, $ValidateUser = TRUE) {
      if ($ValidateUser && $this->UserID <= 0)
         return FALSE;
      return $ForeignKey == $this->_TransientKey && $this->_TransientKey !== FALSE;
   }
	
	/**
	 * Place a name/value pair into the user's session stash.
	 */
	public function Stash($Name = '', $Value = '', $UnsetOnRetrieve = TRUE) {
		if ($Name == '')
			return;
		
      // Grab the user's session
      $Session = $this->_GetStashSession();
      if (!$Session)
         return;
      
      $Attributes = unserialize($Session->Attributes);
      if (!is_array($Attributes))
         $Attributes = array();

      // Stash or unstash the value depending on inputs
      if ($Name != '' && $Value != '') {
         $Attributes[$Name] = $Value;
      } else if ($Name != '') {
         $Value = GetValue($Name, $Attributes);
			if ($UnsetOnRetrieve)
				unset($Attributes[$Name]);
      }
      // Update the attributes
      if ($Name != '') {
         Gdn::SQL()->Update(
            'Session',
            array(
               'Attributes' => serialize($Attributes)
            ),
            array(
               'SessionID' => $Session->SessionID
            )
         )->Put();
      }
      return $Value;
	}
	   
	/**
	 * Used by $this->Stash() to create & manage sessions for users & guests.
	 * This is a stop-gap solution until full session management for users &
	 * guests can be imlemented.
	 */
   private function _GetStashSession() {
      $CookieName = C('Garden.Cookie.Name', 'Vanilla');

      // Grab the entire session record
      $SessionID = GetValue($CookieName.'SessionID', $_COOKIE, '');
      $Session = Gdn::SQL()
         ->Select()
         ->From('Session')
         ->Where('SessionID', $SessionID)
         ->Get()
         ->FirstRow();

      if (!$Session) {
         $SessionID = md5(mt_rand());
         $TransientKey = substr(md5(mt_rand()), 0, 12);
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
         $Session = Gdn::SQL()
            ->Select()
            ->From('Session')
            ->Where('SessionID', $SessionID)
            ->Get()
            ->FirstRow();
            
         // Save a session cookie
         $Name = $CookieName.'SessionID';
         $Path = C('Garden.Cookie.Path', '/');
         $Domain = C('Garden.Cookie.Domain', '');
         $Expire = 0;
         setcookie($Name, $SessionID, $Expire, $Path, $Domain);
      }
      return $Session;
   }


}

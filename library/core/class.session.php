<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class Gdn_Session extends Gdn_Pluggable {
   /// PROPERTIES ///

   protected $_Session = array();

   public $User = NULL;

   public $UserID = 0;
   
   /// METHODS ///

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
         elseif ($this->User->Banned)
            return FALSE;
      }

      // Allow wildcard permission checks (e.g. 'any' Category)
      if ($JunctionID == 'any')
         $JunctionID = '';

      $Permissions = $this->GetPermissions();
      if ($JunctionID && !C('Garden.Permissions.Disabled.'.$JunctionTable)) {
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

   public function End() {
      $SessionID = $this->SessionID();

      if ($SessionID) {
         $this->SessionID(FALSE);
         $this->SaveCookie(NULL);
         Gdn::SQL()->Delete('Session', array('SessionID' => $SessionID));
      }
   }

   protected $_Attributes = array();
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
         return GetValue($AttributeName, $this->_Attributes, $DefaultValue);
      }
      return $DefaultValue;
   }

   protected $_Permissions = NULL;
   /**
    * Returns all "allowed" permissions for the authenticated user in a
    * one-dimensional array of permission names.
    *
    * @return array
    */
   public function GetPermissions($UserID = FALSE) {
      if ($this->_Permissions === NULL) {
         if (!is_object($this->User))
            $this->_Permissions = Gdn::UserModel()->DefinePermissions($this->UserID, FALSE);
         else {
            $this->_Permissions = $this->User->Permissions;
            if ($this->_Permissions === NULL)
               $this->_Permissions = Gdn::UserModel()->DefinePermissions($this->UserID, FALSE);
         }
      }

      return is_array($this->_Permissions) ? $this->_Permissions : array();
   }

   public function GetPreference($Key, $DefaultValue = FALSE) {
      if (is_object($this->User))
         return GetValue($Key, $this->User->Preferences, $DefaultValue);
      return $DefaultValue;
   }

   public function Initialize() {
      $Override = FALSE;
      $this->EventArguments['Override'] =& $Override;
      $this->FireEvent('Initialize');
      if ($Override)
         return;

      // Load the session from the cookie.
      $this->LoadCookie(TRUE);
//      if (!$this->SessionID()) {
//         // There is no session in the cooke so start a new one as a guest.
//         $this->Start();
//      }
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

   protected function LoadCookie($Clean = FALSE) {
      $Name = C('Garden.Cookie.Name', 'Vanilla');
      $SessionID = GetValue($Name, $_COOKIE);
      if ($SessionID) {
         // Grab the session from the database.
         $Session = Gdn::SQL()->GetWhere('Session', array('SessionID' => $SessionID))->FirstRow(DATASET_TYPE_ARRAY);

         if ($Session) {
            $this->SessionID($SessionID);
            $this->UserID = $Session['UserID'];
            $this->TransientKey($Session['TransientKey']);
            if ($this->UserID) {
               // This is a user session.
               $this->User = Gdn::UserModel()->GetSession($this->UserID);
               $this->_Attributes =& $this->User->Attributes;
               $this->_Permissions =& $this->User->Permissions;
            } else {
               // This is a guest session.
               $Attributes = @unserialize($Session['Attributes']);
               if (!is_array($Attributes))
                  $Attributes = array();
               $this->_Attributes =& $Attributes;
            }
         } elseif($Clean) {
            // Remove the erroneous cookie.
            $this->SaveCookie(TRUE);
         }
      }
   }

   protected function SaveCookie($Persist = FALSE) {
      $Name = C('Garden.Cookie.Name', 'Vanilla');
      $Path = C('Garden.Cookie.Path', '/');
      $Domain = C('Garden.Cookie.Domain', '');
      $SessionID = $this->SessionID();

      if ($Persist === TRUE) {
         $Expire = time() + 2592000;
      } elseif ($Persist === FALSE) {
         $Expire = 0;
      } elseif ($Persist === NULL) {
         $Expire = time() - 3600;
         $SessionID = NULL;
      } else {
         $Expire = $Persist;
      }
      
      setcookie($Name, $SessionID, $Expire, $Path, $Domain);
      if ($SessionID === NULL)
         unset($_COOKIE[$Name]);
      else
         $_COOKIE[$Name] = $SessionID;
   }

   protected $_SessionID = FALSE;

   public function SessionID($NewValue = NULL) {
      if ($NewValue != NULL) {
         $this->_SessionID = $NewValue;
      }

      return $this->_SessionID;
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
      if (!is_object($this->User))
         return;

      if (!is_array($Name))
         $Name = array($Name => $Value);

      foreach($Name as $Key => $Val) {
         $this->User->Preferences[$Key] = $Val;
      }

      if ($SaveToDatabase && $this->UserID > 0) {
         $UserModel = Gdn::UserModel();
         $UserModel->SavePreference($this->UserID, $Name);
      }
   }

//   public static function SetCookie($CookieName, $KeyData, $CookieContents, $Expire, $Path = NULL, $Domain = NULL, $CookieHashMethod = NULL, $CookieSalt = NULL) {
//
//      if (is_null($Path))
//         $Path = Gdn::Config('Garden.Cookie.Path', '/');
//
//      if (is_null($Domain))
//         $Domain = Gdn::Config('Garden.Cookie.Domain', '');
//
//      // If the domain being set is completely incompatible with the current domain then make the domain work.
//      $CurrentHost = Gdn::Request()->Host();
//      if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
//         $Domain = '';
//
//      if (!$CookieHashMethod)
//         $CookieHashMethod = Gdn::Config('Garden.Cookie.HashMethod');
//
//      if (!$CookieSalt)
//         $CookieSalt = Gdn::Config('Garden.Cookie.Salt');
//
//      // Create the cookie contents
//      $Key = self::_Hash($KeyData, $CookieHashMethod, $CookieSalt);
//      $Hash = self::_HashHMAC($CookieHashMethod, $KeyData, $Key);
//      $Cookie = array($KeyData,$Hash,time());
//      if (!is_null($CookieContents)) {
//         if (!is_array($CookieContents)) $CookieContents = array($CookieContents);
//         $Cookie = array_merge($Cookie, $CookieContents);
//      }
//
//      $CookieContents = implode('|',$Cookie);
//
//      // Create the cookie.
//      setcookie($CookieName, $CookieContents, $Expire, $Path, $Domain);
//      $_COOKIE[$CookieName] = $CookieContents;
//   }

   public function Start($UserID = 0, $Save = TRUE, $Persist = FALSE) {
      $SessionID = $this->SessionID();
      if (!$SessionID) {
         // Generate a new session.
         $SessionID = md5(mt_rand());
         $this->SessionID($SessionID);
         $TransientKey = substr(md5(mt_rand()), 0, 12);

         if ($Save) {
            // Save the session information to the database.
            Gdn::SQL()->Insert('Session', array('SessionID' => $SessionID, 'UserID' => $UserID, 'TransientKey' => $TransientKey, 'DateInserted' => Gdn_Format::ToDateTime(), 'DateUpdated' => Gdn_Format::ToDateTime()));

            // Set the session cookie.
            $this->SaveCookie($Persist);
         }
      } else {
         if ($Save) {
            Gdn::SQL()->Put('Session', array('UserID' => $UserID, 'DateUpdated' => Gdn_Format::ToDateTime()), array('SessionID' => $SessionID));
            $this->SaveCookie($Persist);
         }
      }
      $this->UserID = $UserID;
      // Grab the user.
      if ($UserID > 0) {
         $this->User = Gdn::UserModel()->GetSession($UserID);
      } else {
         $this->User = NULL;
      }
   }

   /**
	 * Place a name/value pair into the user's session stash.
	 */
	public function Stash($Name = '', $Value = NULL, $UnsetOnRetrieve = TRUE) {
		if ($Name == '')
			return;

      if ($Value === NULL) {
         $Result = $this->GetAttribute($Name);
         if ($UnsetOnRetrieve) {
            $this->SetAttribute($Name, NULL);
         }
      } else {
         $this->SetAttribute($Name, $Value);
         $Result = $Value;
      }

      return $Result;
	}

   protected $_TransientKey;
   /**
    * Returns the transient key for the authenticated user.
    *
    * @return string
    */
   public function TransientKey($NewValue = NULL) {
      if ($NewValue) {
         $this->_TransientKey = $NewValue;
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
      return $this->_TransientKey && $ForeignKey == $this->_TransientKey;
   }
}
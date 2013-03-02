<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UserModel extends Gdn_Model {
   const DEFAULT_CONFIRM_EMAIL = 'You need to confirm your email address before you can continue. Please confirm your email address by clicking on the following link: {/entry/emailconfirm,url,domain}/{User.UserID,rawurlencode}/{EmailKey,rawurlencode}';
   const USERID_KEY = 'user.{UserID}';
   const USERNAME_KEY = 'user.{Name}.name';
   const USERROLES_KEY = 'user.{UserID}.roles';
   const USERPERMISSIONS_KEY = 'user.{UserID}.permissions.{PermissionsIncrement}';
   const INC_PERMISSIONS_KEY = 'permissions.increment';
   
   static $UserCache = array();
   
   public $SessionColumns;
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('User');
   }

   protected function _AddEmailHeaderFooter($Message, $Data) {
      $Header = T('EmailHeader', '');
      if ($Header)
         $Message = FormatString($Header, $Data)."\n".$Message;

      $Footer = T('EmailFooter', '');
      if ($Footer)
         $Message .= "\n".FormatString($Footer, $Data);

      return $Message;
   }

   public function ConfirmEmail($User, $EmailKey) {
      $Attributes = GetValue('Attributes', $User);
      $StoredEmailKey = GetValue('EmailKey', $Attributes);
      $UserID = GetValue('UserID', $User);
      
      if (!$StoredEmailKey || $EmailKey != $StoredEmailKey) {
         $this->Validation->AddValidationResult('EmailKey', '@'.T('Couldn\'t confirm email.',
            'We couldn\'t confirm your email. Check the link in the email we sent you or try sending another confirmation email.'));
         return FALSE;
      }

      // Update the user's roles.
      $Roles = GetValue('ConfirmedEmailRoles', $Attributes, C('Garden.Registration.DefaultRoles'));
      $this->EventArguments['ConfirmUserID'] = $UserID;
      $this->EventArguments['ConfirmUserRoles'] = &$Roles;
      $this->FireEvent('BeforeConfirmEmail');
      $this->SaveRoles($UserID, $Roles, FALSE);
      
      // Remove the email confirmation attributes.
      unset($Attributes['EmailKey'], $Attributes['ConfirmedEmailRoles']);
      $this->SaveAttribute($UserID, $Attributes);
      
      return TRUE;
   }

   /** Connect a user with a foreign authentication system.
    *
    * @param string $UniqueID The user's unique key in the other authentication system.
    * @param string $ProviderKey The key of the system providing the authentication.
    * @param array $UserData Data to go in the user table.
    * @return int The new/existing user ID.
    */
   public function Connect($UniqueID, $ProviderKey, $UserData) {
      if (!isset($UserData['UserID'])) {
         // Check to see if the user already exists.
         $Auth = $this->GetAuthentication($UniqueID, $ProviderKey);
         $UserID = GetValue('UserID', $Auth);

         if ($UserID)
            $UserData['UserID'] = $UserID;
      }
      
      if (isset($UserID)) {
         // Save the user.
         $this->Save($UserData, array('NoConfirmEmail' => TRUE));
      } else {
         // Create a new user.
         $UserID = $this->InsertForBasic($UserData, FALSE, array('ValidateEmail' => FALSE, 'NoConfirmEmail' => TRUE));
         
         if ($UserID) {
            // Save the authentication.
            $this->SaveAuthentication(array(
                'ForeignUserKey' => $UniqueID, 
                'ProviderKey' => $ProviderKey, 
                'UserID' => $UserID
            ));
            
            if (C('Garden.Registration.SendConnectEmail', TRUE)) {
               $Provider = $this->SQL->GetWhere('UserAuthenticationProvider', array('AuthenticationKey' => $ProviderKey))->FirstRow(DATASET_TYPE_ARRAY);
               if ($Provider) {
                  try {
                     $UserModel->SendWelcomeEmail($UserID, '', 'Connect', array('ProviderName' => GetValue('Name', $Provider, C('Garden.Title'))));
                  } catch (Exception $Ex) {
                     // Do nothing if emailing doesn't work.
                  }
               }
            }
         }
      }
      return $UserID;
   }
   
   public function FilterForm($Data) {
      $Data = parent::FilterForm($Data);
      $Data = array_diff_key($Data, 
         array('Admin' => 0, 'Deleted' => 0, 'CountVisits' => 0, 'CountInvitations' => 0, 'CountNotifications' => 0, 'Preferences' => 0, 
               'Permissions' => 0, 'LastIPAddress' => 0, 'AllIPAddresses' => 0, 'DateFirstVisit' => 0, 'DateLastActive' => 0, 'CountDiscussions' => 0, 'CountComments' => 0,
               'Score' => 0));
      if (!Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
         $Data = array_diff_key($Data, array('Banned' => 0, 'Verified' => 0));
      }
      if (!Gdn::Session()->CheckPermission('Garden.Users.Edit') && !C("Garden.Profile.EditUsernames")) {
         unset($Data['Name']);
      }
      
//      decho($Data);
//      die();
      
      return $Data;
      
   }
   
   /**
    * A convenience method to be called when inserting users (because users
    * are inserted in various methods depending on registration setups).
    */
   protected function _Insert($Fields, $Options = array()) {
      $this->EventArguments['InsertFields'] =& $Fields;
      $this->FireEvent('BeforeInsertUser');
      
      // Massage the roles for email confirmation.
      if (C('Garden.Registration.ConfirmEmail') && !GetValue('NoConfirmEmail', $Options)) {
         TouchValue('Attributes', $Fields, array());
         $ConfirmationCode = RandomString(8);
         $Fields['Attributes']['EmailKey'] = $ConfirmationCode;
         
         if (isset($Fields['Roles'])) {
            $Fields['Attributes']['ConfirmedEmailRoles'] = $Fields['Roles'];
         }
         $Fields['Roles'] = (array)C('Garden.Registration.ConfirmEmailRole');
      }

      // Make sure to encrypt the password for saving...
      if (array_key_exists('Password', $Fields) && !array_key_exists('HashMethod', $Fields)) {
         $PasswordHash = new Gdn_PasswordHash();
         $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
         $Fields['HashMethod'] = 'Vanilla';
      }

      $Roles = GetValue('Roles', $Fields);
      unset($Fields['Roles']);
      
      if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes']))
            $Fields['Attributes'] = serialize($Fields['Attributes']);
      
      $UserID = $this->SQL->Insert($this->Name, $Fields);
      if (is_array($Roles)) {
         $this->SaveRoles($UserID, $Roles, FALSE);
      }

      // Approval registration requires an email confirmation.
      if ($UserID && isset($ConfirmationCode) && strtolower(C('Garden.Registration.Method')) == 'approval') {
         // Send the confirmation email.
         $this->SendEmailConfirmationEmail($UserID);
      }

      // Fire an event for user inserts
      $this->EventArguments['InsertUserID'] = $UserID;
      $this->EventArguments['InsertFields'] = $Fields;
      $this->FireEvent('AfterInsertUser');
      return $UserID;
   }
   
   /**
    * Add user data to a result set.
    *
    * @param object $Data Results we need to associate user data with.
    * @param array $Columns Database columns containing UserIDs to get data for.
    * @param array $Options Optionally pass list of user data to collect with key 'Join'.
    */
   public function JoinUsers(&$Data, $Columns, $Options = array()) {
      // Grab all of the user fields that need to be joined.
      $UserIDs = array();
      foreach ($Data as $Row) {
         foreach ($Columns as $ColumnName) {
            $UserIDs[GetValue($ColumnName, $Row)] = 1;
         }
      }
      
      // Get the users.
      $Users = $this->GetIDs(array_keys($UserIDs));
      
      // Get column name prefix (ex: 'Insert' from 'InsertUserID')
      $Prefixes = array();
      foreach ($Columns as $ColumnName) {
         $Prefixes[] = StringEndsWith($ColumnName, 'UserID', TRUE, TRUE);
      }
      
      // Join the user data using prefixes (ex: 'Name' for 'InsertUserID' becomes 'InsertName')
      $Join = GetValue('Join', $Options, array('Name', 'Email', 'Photo'));
      
      foreach ($Data as &$Row) {
         foreach ($Prefixes as $Px) {
            $ID = GetValue($Px.'UserID', $Row);
            $User = GetValue($ID, $Users, FALSE);
            
            foreach ($Join as $Column) {
               SetValue($Px.$Column, $Row, $User[$Column]);
            }
         }
      }
   }

   /**
    * $SafeData makes sure that the query does not return any sensitive
    * information about the user (password, attributes, preferences, etc).
    */
   public function UserQuery($SafeData = FALSE) {
      if ($SafeData) {
         $this->SQL->Select('u.UserID, u.Name, u.Photo, u.About, u.Gender, u.CountVisits, u.InviteUserID, u.DateFirstVisit, u.DateLastActive, u.DateInserted, u.DateUpdated, u.Score, u.Admin, u.Deleted, u.CountDiscussions, u.CountComments');
      } else {
         $this->SQL->Select('u.*');
      }
      $this->SQL->Select('i.Name', '', 'InviteName')
         ->From('User u')
         ->Join('User as i', 'u.InviteUserID = i.UserID', 'left');
   }

   public function DefinePermissions($UserID, $Serialize = TRUE) {
      
      if (Gdn::Cache()->ActiveEnabled()) {
         $PermissionsIncrement = $this->GetPermissionsIncrement();
         $UserPermissionsKey = FormatString(self::USERPERMISSIONS_KEY, array(
            'UserID' => $UserID,
            'PermissionsIncrement' => $PermissionsIncrement
         ));
         
         $CachePermissions = Gdn::Cache()->Get($UserPermissionsKey);
         if ($CachePermissions !== Gdn_Cache::CACHEOP_FAILURE) 
            return $CachePermissions;
      }
      
      $Data = Gdn::PermissionModel()->CachePermissions($UserID);
      
      $Permissions = array();
      foreach($Data as $i => $Row) {
         $JunctionTable = $Row['JunctionTable'];
         $JunctionColumn = $Row['JunctionColumn'];
         $JunctionID = $Row['JunctionID'];
         unset($Row['JunctionColumn'], $Row['JunctionColumn'], $Row['JunctionID'], $Row['RoleID'], $Row['PermissionID']);
         
         foreach($Row as $PermissionName => $Value) {
            if($Value == 0)
               continue;
            
            if(is_numeric($JunctionID) && $JunctionID !== NULL) {
               if (!array_key_exists($PermissionName, $Permissions))
                  $Permissions[$PermissionName] = array();
                  
               if (!is_array($Permissions[$PermissionName]))
                  $Permissions[$PermissionName] = array();
                  
               $Permissions[$PermissionName][] = $JunctionID;
            } else {
               $Permissions[] = $PermissionName;
            }
         }
      }
      
      // Throw a fatal error if the user has no permissions
      // if (count($Permissions) == 0)
      //    trigger_error(ErrorMessage('The requested user ('.$this->UserID.') has no permissions.', 'Session', 'Start'), E_USER_ERROR);

      $PermissionsSerialized = NULL;
      if (Gdn::Cache()->ActiveEnabled()) {
         Gdn::Cache()->Store($UserPermissionsKey, $Permissions);
      } else {
         // Save the permissions to the user table
         $PermissionsSerialized = Gdn_Format::Serialize($Permissions);
         if ($UserID > 0)
            $this->SQL->Put('User', array('Permissions' => $PermissionsSerialized), array('UserID' => $UserID));
      }
      
      if ($Serialize && is_null($PermissionsSerialized))
         $PermissionsSerialized = Gdn_Format::Serialize($Permissions);
      
      return $Serialize ? $PermissionsSerialized : $Permissions;
   }

   /**
    * Default Gdn_Model::Get() behavior.
    * 
    * Prior to 2.0.18 it incorrectly behaved like GetID.
    * This method can be deleted entirely once it's been deprecated long enough.
    *
    * @since 2.0.0
    * @return object DataSet
    */
   public function Get($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      if (is_numeric($OrderFields)) {
         // They're using the old version that was a misnamed GetID()
         Deprecated('UserModel->Get()', 'UserModel->GetID()');
         $Result = $this->GetID($OrderFields);
      }
      else {
         $Result = parent::Get($OrderFields, $OrderDirection, $Limit, $Offset);
      }
      return $Result;  
   }
   
   public function GetByUsername($Username) {
		if ($Username == '')
		 	return FALSE;
      
      // Check page cache, then memcached
      $User = $this->GetUserFromCache($Username, 'name');
      
      if ($User === Gdn_Cache::CACHEOP_FAILURE) {
         $this->UserQuery();
         $User = $this->SQL->Where('u.Name', $Username)->Get()->FirstRow(DATASET_TYPE_ARRAY);
         if ($User) {
            // If success, build more data, then cache user
            $this->SetCalculatedFields($User);
            $this->UserCache($User);
         }
      }
      
      // By default, FirstRow() gives stdClass
      if ($User !== FALSE)
         $User = (object)$User;
      
      return $User;
   }
	public function GetByEmail($Email) {
      $this->UserQuery();
      $User = $this->SQL->Where('u.Email', $Email)->Get()->FirstRow();
      $this->SetCalculatedFields($User);
      return $User;
   }
   
   public function GetByRole($Role) {
      $RoleID = $Role; // Optimistic
      if (is_string($Role)) {
         $RoleModel = new RoleModel();
         $Roles = $RoleModel->GetArray();
         $RolesByName = array_flip($Roles);
         
         $RoleID = GetValue($Role, $RolesByName, NULL);
         
         // No such role
         if (is_null($RoleID)) return new Gdn_DataSet();
      }

      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $RoleID, TRUE, FALSE)
//         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   public function GetActiveUsers($Limit = 5) {
      $this->UserQuery();
      $this->FireEvent('BeforeGetActiveUsers');
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy('u.DateLastActive', 'desc')
         ->Limit($Limit, 0)
         ->Get();
   }

   public function GetApplicantCount() {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      if ($ApplicantRoleID == 0)
         return 0;

      $Result = $this->SQL->Select('u.UserID', 'count', 'ApplicantCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $ApplicantRoleID, TRUE, FALSE)
         ->Get()->Value('ApplicantCount', 0);
      return $Result;
   }
   
   /**
    * Returns all users in the applicant role
    */
   public function GetApplicants() {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      if ($ApplicantRoleID == 0)
         return new Gdn_DataSet();

      return $this->SQL->Select('u.*')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID')
         ->Where('ur.RoleID', $ApplicantRoleID, TRUE, FALSE)
//         ->GroupBy('UserID')
         ->OrderBy('DateInserted', 'desc')
         ->Get();
   }

   /**
    * Get the a user authentication row.
    *
    * @param string $UniqueID The unique ID of the user in the foreign authentication scheme.
    * @param string $Provider The key of the provider.
    * @return array|false
    */
   public function GetAuthentication($UniqueID, $Provider) {
      return $this->SQL->GetWhere('UserAuthentication',
         array('ForeignUserKey' => $UniqueID, 'ProviderKey' => $Provider))->FirstRow(DATASET_TYPE_ARRAY);
   }

   public function GetCountLike($Like = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');
      if (is_array($Like)){
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		$this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null');
		
		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetCountWhere($Where = FALSE) {
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u')
         ->Join('UserRole ur', 'u.UserID = ur.UserID and ur.RoleID = '.(int)C('Garden.Registration.ApplicantRoleID', 0), 'left');
		
		if (is_array($Where))
         $this->SQL->Where($Where);

		$Data = $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->Get()
         ->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   public function GetID($ID, $DatasetType = DATASET_TYPE_OBJECT) {
      // Check page cache, then memcached
      $User = $this->GetUserFromCache($ID, 'userid');
      
      // If not, query DB
      if ($User === Gdn_Cache::CACHEOP_FAILURE) {
         $User = parent::GetID($ID, DATASET_TYPE_ARRAY);
         
         if ($User) {
            // If success, build more data, then cache user
            $this->SetCalculatedFields($User);
            $this->UserCache($User);
         }
      }
      
      // Allow FALSE returns
      if ($User === FALSE || is_null($User))
         return FALSE;
      
      if (is_array($User) && $DatasetType == DATASET_TYPE_OBJECT)
         $User = (object)$User;
      
      if (is_object($User) && $DatasetType == DATASET_TYPE_ARRAY)
         $User = (array)$User;
      
      return $User;
   }
   
   public function GetIDs($IDs, $SkipCacheQuery = FALSE) {
      
      $DatabaseIDs = $IDs;
      $Data = array();
      
      if (!$SkipCacheQuery) {
         
         // TODO: add $UserCache layer
         
         $Keys = array();
         // Make keys for cache query
         foreach ($IDs as $UserID) {
            if (!$UserID) continue;
            $Keys[] = FormatString(self::USERID_KEY, array('UserID' => $UserID));
         }
         
         // Query cache layer
         $CacheData = Gdn::Cache()->Get($Keys);
         if (!is_array($CacheData))
            $CacheData = array();
            
         foreach ($CacheData as $RealKey => $User) {
            $ResultUserID = GetValue('UserID', $User);
            $Data[$ResultUserID] = $User;
         }
         
         //echo "from cache:\n";
         //print_r($Data);
         
         $DatabaseIDs = array_diff($DatabaseIDs, array_keys($Data));
         unset($CacheData);
      }
      
      // Clean out bogus blank entries
      $DatabaseIDs = array_diff($DatabaseIDs, array(NULL, ''));
      
      // If we are missing any users from cache query, fill em up here
      if (sizeof($DatabaseIDs)) {
         $DatabaseData = $this->SQL->WhereIn('UserID', $DatabaseIDs)->GetWhere('User')->Result(DATASET_TYPE_ARRAY);
         $DatabaseData = Gdn_DataSet::Index($DatabaseData, 'UserID');
         
         //echo "from DB:\n";
         //print_r($DatabaseData);
         
         foreach ($DatabaseData as $DatabaseUserID => $DatabaseUser) {
            $Data[$DatabaseUserID] = $DatabaseUser;
            $this->SetCalculatedFields($DatabaseUser);
            $this->UserCache($DatabaseUser);
         }
      }
      
      return $Data;
   }

   public function GetLike($Like = FALSE, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);

      $this->UserQuery();
      $this->SQL
         ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if (is_array($Like)) {
         $this->SQL
				->BeginWhereGroup()
				->OrLike($Like, '', 'right')
				->EndWhereGroup();
		}
		
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->Where('ur.RoleID is null')
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }

   /**
    * Retries UserMeta information for a UserID / Key pair
    *
    * This method takes a $UserID or array of $UserIDs, and a $Key. It converts the
    * $Key to fully qualified format and then queries for the associated value(s). $Key
    * can contain SQL wildcards, in which case multiple results can be returned.
    *
    * If $UserID is an array, the return value will be a multi dimensional array with the first
    * axis containing UserIDs and the second containing fully qualified UserMetaKeys, associated with
    * their values.
    *
    * If $UserID is a scalar, the return value will be a single dimensional array of $UserMetaKey => $Value
    * pairs.
    *
    * @param $UserID integer UserID or array of UserIDs.
    * @param $Key string relative user meta key.
    * @return array results or $Default
    */
   public static function GetMeta($UserID, $Key, $Prefix = '') {;
      $Sql = Gdn::SQL()
         ->Select('*')
         ->From('UserMeta u');

      if (is_array($UserID))
         $Sql->WhereIn('u.UserID', $UserID);
      else
         $Sql->Where('u.UserID', $UserID);

      if (strpos($Key, '%') !== FALSE)
         $Sql->Like('u.Name', $Key);
      else
         $Sql->Where('u.Name', $Key);

      $Data = $Sql->Get()->ResultArray();

      if (is_array($UserID))
         $Result = array_fill_keys($UserID, array());
      else {
         if (strpos($Key, '%') === FALSE)
            $Result = array(StringBeginsWith($Key, $Prefix, FALSE, TRUE) => $Default);
         else
            $Result = array();
      }

      foreach ($Data as $Row) {
         $Name = StringBeginsWith($Row['Name'], $Prefix, FALSE, TRUE);

         if (is_array($UserID)) {
            $Result[$Row['UserID']][$Name] = $Row['Value'];
         } else {
            $Result[$Name] = $Row['Value'];
         }
      }

      return $Result;
   }

   public function GetRoles($UserID) {
      $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
      $RolesDataArray = Gdn::Cache()->Get($UserRolesKey);
      
      if ($RolesDataArray === Gdn_Cache::CACHEOP_FAILURE) {
         $RolesDataArray = $this->SQL->Select('r.RoleID, r.Name')
            ->From('UserRole ur')
            ->Join('Role r', 'ur.RoleID = r.RoleID')
            ->Where('ur.UserID', $UserID)
            ->Get()->Result(DATASET_TYPE_ARRAY);
      }
      return new Gdn_DataSet($RolesDataArray);
   }

   public function GetSession($UserID, $Refresh = FALSE) {
      // Ask for the user. This will check cache first.
      $User = $this->GetID($UserID, DATASET_TYPE_OBJECT);
      
      // TIM: Removed on Jul 14, 2011 for PennyArcade
      //
      //$this->FireEvent('SessionQuery');
      //if (is_array($this->SessionColumns)) {
      //   $this->SQL->Select($this->SessionColumns);
      //}
      //$User = $this->SQL
      //   ->Get()
      //   ->FirstRow();

      if ($User && $User->Permissions == '')
         $User->Permissions = $this->DefinePermissions($UserID);
      
      // Remove secret info from session
      unset($User->Password, $User->HashMethod);
      
      return $User;
   }

   /**
    * Retrieve a summary of "safe" user information for external API calls.
    */
   public function GetSummary($OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      $this->UserQuery(TRUE);
      return $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   /**
    * Retrieves a "system user" id that can be used to perform non-real-person tasks.
    */
   public function GetSystemUserID() {
      $SystemUserID = C('Garden.SystemUserID');
      if ($SystemUserID)
         return $SystemUserID;
      
      $SystemUser = array(
         'Name' => T('System'),
         'Password' => RandomString('20'),
         'HashMethod' => 'Random',
         'Email' => 'system@domain.com',
         'DateInserted' => Gdn_Format::ToDateTime(),
         'Admin' => '2'
      );
      
      $this->EventArguments['SystemUser'] = &$SystemUser;
      $this->FireEvent('BeforeSystemUser');
      
      $SystemUserID = $this->SQL->Insert($this->Name, $SystemUser);
      
      SaveToConfig('Garden.SystemUserID', $SystemUserID);
      return $SystemUserID;
   }

   public function Register($FormPostValues, $Options = array()) {
      $Valid = TRUE;
      $FormPostValues['LastIPAddress'] = Gdn::Request()->IpAddress();
      
      // Throw an error if the registering user has an active session
      if (Gdn::Session()->IsValid())
         $this->Validation->AddValidationResult('Name', 'You are already registered.');

      // Check for banning first.
      $Valid = BanModel::CheckUser($FormPostValues, $this->Validation, TRUE);

      // Throw an event to allow plugins to block the registration.
      unset($this->EventArguments['User']);
      $this->EventArguments['User'] = $FormPostValues;
      $this->EventArguments['Valid'] =& $Valid;
      $this->FireEvent('BeforeRegister');

      if (!$Valid)
         return FALSE; // plugin blocked registration

      switch (strtolower(C('Garden.Registration.Method'))) {
         case 'captcha':
            $UserID = $this->InsertForBasic($FormPostValues, GetValue('CheckCaptcha', $Options, TRUE), $Options);
            break;
         case 'approval':
            $UserID = $this->InsertForApproval($FormPostValues, $Options);
            break;
         case 'invitation':
            $UserID = $this->InsertForInvite($FormPostValues, $Options);
            break;
         case 'closed':
            $UserID = FALSE;
            $this->Validation->AddValidationResult('Registration', 'Registration is closed.');
            break;
         case 'basic':
         default:
            $UserID = $this->InsertForBasic($FormPostValues, GetValue('CheckCaptcha', $Options, FALSE), $Options);
            break;
      }
      return $UserID;
   }
   
   public function RemovePicture($UserID) {
      $this->SetField($UserID, 'Photo', NULL);
   }

   public function ProfileCount($User, $Column) {
      if (is_numeric($User))
         $User = $this->SQL->GetWhere('User', array('UserID' => $User))->FirstRow(DATASET_TYPE_ARRAY);
      elseif (is_string($User))
         $User = $this->SQL->GetWhere('User', array('Name' => $User))->FirstRow(DATASET_TYPE_ARRAY);
      elseif (is_object($User))
         $User = (array)$User;
      
      if (!$User)
         return FALSE;

      if (array_key_exists($Column, $User) && $User[$Column] === NULL) {
            $UserID = $User['UserID'];
            switch ($Column) {
               case 'CountComments':
                  $Count = $this->SQL->GetCount('Comment', array('InsertUserID' => $UserID));
                  $this->SetField($UserID, 'CountComments', $Count);
                  break;
               case 'CountDiscussions':
                  $Count = $this->SQL->GetCount('Discussion', array('InsertUserID' => $UserID));
                  $this->SetField($UserID, 'CountDiscussions', $Count);
                  break;
               case 'CountBookmarks':
                  $Count = $this->SQL->GetCount('UserDiscussion', array('UserID' => $UserID, 'Bookmarked' => '1'));
                  $this->SetField($UserID, 'CountBookmarks', $Count);
                  break;
               default:
                  $Count = FALSE;
                  break;
            }
            return $Count;
      } elseif ($User[$Column]) {
         return $User[$Column];
      } else {
         return FALSE;
      }
   }

   /**
    * Generic save procedure.
    */
   public function Save($FormPostValues, $Settings = FALSE) {
      
      // See if the user's related roles should be saved or not.
      $SaveRoles = GetValue('SaveRoles', $Settings);

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      if (array_key_exists('Email', $FormPostValues) && GetValue('ValidateEmail', $Settings, TRUE))
         $this->Validation->ApplyRule('Email', 'Email');

      // Custom Rule: This will make sure that at least one role was selected if saving roles for this user.
      if ($SaveRoles) {
         $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
         // $this->Validation->AddValidationField('RoleID', $FormPostValues);
         $this->Validation->ApplyRule('RoleID', 'OneOrMoreArrayItemRequired');
      }

      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      // Validate the form posted values
      $UserID = GetValue('UserID', $FormPostValues);
      $Insert = $UserID > 0 ? FALSE : TRUE;
      if ($Insert) {
         $this->AddInsertFields($FormPostValues);
      } else {
         $this->AddUpdateFields($FormPostValues);
      }
      
      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->FireEvent('BeforeSaveValidation');

      $RecordRoleChange = TRUE;
      if ($this->Validate($FormPostValues, $Insert) && $this->ValidateUniqueFields(GetValue('Name', $FormPostValues), GetValue('Email', $FormPostValues), $UserID)) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $RoleIDs = GetValue('RoleID', $Fields, 0);
         $Username = GetValue('Name', $Fields);
         $Email = GetValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         // Remove the primary key from the fields collection before saving
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         
         if (!$Insert && array_key_exists('Password', $Fields)) {
            // Encrypt the password for saving only if it won't be hashed in _Insert()
            $PasswordHash = new Gdn_PasswordHash();
            $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
            $Fields['HashMethod'] = 'Vanilla';
         }
         
         // Check for email confirmation.
         if (C('Garden.Registration.ConfirmEmail') && !GetValue('NoConfirmEmail', $Settings)) {
            if (isset($Fields['Email']) && $UserID == Gdn::Session()->UserID && $Fields['Email'] != Gdn::Session()->User->Email && !Gdn::Session()->CheckPermission('Garden.Users.Edit')) {
               $User = Gdn::Session()->User;
               $Attributes = Gdn::Session()->User->Attributes;
               
               $ConfirmEmailRoleID = C('Garden.Registration.ConfirmEmailRole');
               if (RoleModel::Roles($ConfirmEmailRoleID)) {
                  // The confirm email role is set and it exists so go ahead with the email confirmation.
                  $EmailKey = TouchValue('EmailKey', $Attributes, RandomString(8));
                  
                  if ($RoleIDs)
                     $ConfirmedEmailRoles = $RoleIDs;
                  else
                     $ConfirmedEmailRoles = ConsolidateArrayValuesByKey($this->GetRoles($UserID), 'RoleID');
                  $Attributes['ConfirmedEmailRoles'] = $ConfirmedEmailRoles;

                  $RoleIDs = (array)C('Garden.Registration.ConfirmEmailRole');

                  $SaveRoles = TRUE;
                  $Fields['Attributes'] = serialize($Attributes);
               }
            } 
         }
         
         $this->EventArguments['Fields'] = $Fields;
         $this->FireEvent('BeforeSave');
         
         // Check the validation results again in case something was added during the BeforeSave event.
         if (count($this->Validation->Results()) == 0) {
            // If the primary key exists in the validated fields and it is a
            // numeric value greater than zero, update the related database row.
            if ($UserID > 0) {
               // If they are changing the username & email, make sure they aren't
               // already being used (by someone other than this user)
               if (ArrayValue('Name', $Fields, '') != '' || ArrayValue('Email', $Fields, '') != '') {
                  if (!$this->ValidateUniqueFields($Username, $Email, $UserID))
                     return FALSE;
               }
               
               if (array_key_exists('Attributes', $Fields) && !is_string($Fields['Attributes'])) {
                  $Fields['Attributes'] = serialize($Fields['Attributes']);
               }
   
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $UserID));
   
               // Record activity if the person changed his/her photo.
               $Photo = ArrayValue('Photo', $FormPostValues);
               if ($Photo !== FALSE) {
                  if (GetValue('CheckExisting', $Settings)) {
                     $User = $this->GetID($UserID);
                     $OldPhoto = GetValue('Photo', $User);
                  }

                  if (isset($OldPhoto) && $OldPhoto != $Photo) {
                     if (strpos($Photo, '//'))
                        $PhotoUrl = $Photo;
                     else
                        $PhotoUrl = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));

                     AddActivity($UserID, 'PictureChange', Img($PhotoUrl, array('alt' => T('Thumbnail'))));
                  }
               }
   
            } else {
               $RecordRoleChange = FALSE;
               if (!$this->ValidateUniqueFields($Username, $Email))
                  return FALSE;
   
               // Define the other required fields:
               $Fields['Email'] = $Email;
               
               $Fields['Roles'] = $RoleIDs;
               // Make sure that the user is assigned to one or more roles:
               $SaveRoles = FALSE;
   
               // And insert the new user.
               $UserID = $this->_Insert($Fields, $Settings);
   
               // Report that the user was created
               $Session = Gdn::Session();
               AddActivity(
                  $Session->UserID,
                  GetValue('ActivityType', $Settings, 'JoinCreated'),
                  T('Welcome Aboard!'),
                  $UserID
               );
            }
            // Now update the role settings if necessary.
            if ($SaveRoles) {
               // If no RoleIDs were provided, use the system defaults
               if (!is_array($RoleIDs))
                  $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
   
               $this->SaveRoles($UserID, $RoleIDs, $RecordRoleChange);
            }

            // Send the confirmation email.
            if (isset($EmailKey)) {
               $this->SendEmailConfirmationEmail((array)Gdn::Session()->User);
            }

            $this->EventArguments['UserID'] = $UserID;
            $this->FireEvent('AfterSave');
         } else {
            $UserID = FALSE;
         }
      } else {
         $UserID = FALSE;
      }
      
      // Clear cached user data
      if (!$Insert && $UserID) {
         $this->ClearCache($UserID, array('user'));
      }
      
      return $UserID;
   }
   
   /**
    * Force the admin user into UserID 1.
    */
   public function SaveAdminUser($FormPostValues) {
      $UserID = 0;

      // Add & apply any extra validation rules:
      $Name = ArrayValue('Name', $FormPostValues, '');
      $FormPostValues['Email'] = ArrayValue('Email', $FormPostValues, strtolower($Name.'@'.Gdn_Url::Host()));
      $FormPostValues['ShowEmail'] = '0';
      $FormPostValues['TermsOfService'] = '1';
      $FormPostValues['DateOfBirth'] = '1975-09-16';
      $FormPostValues['DateLastActive'] = Gdn_Format::ToDateTime();
      $FormPostValues['DateUpdated'] = Gdn_Format::ToDateTime();
      $FormPostValues['Gender'] = 'm';
      $FormPostValues['Admin'] = '1';

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         $UserID = 1;
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields['UserID'] = 1;
         
         if ($this->GetID($UserID) !== FALSE) {
            // Re-hash the password here.
            $PasswordHash = new Gdn_PasswordHash();
            $Fields['Password'] = $PasswordHash->HashPassword($Fields['Password']);
            $Fields['HashMethod'] = 'Vanilla';
            
            $this->SQL->Put($this->Name, $Fields, array('UserID' => 1));
         } else {
            // Insert the new user
            $UserID = $this->_Insert($Fields, array('NoConfirmEmail' => TRUE));
            AddActivity(
               $UserID,
               'Join',
               T('Welcome to Vanilla!')
            );
         }
         $this->SaveRoles($UserID, array(16), FALSE);
      }
      return $UserID;
   }

   public function SaveRoles($UserID, $RoleIDs, $RecordActivity = TRUE) {
      if(is_string($RoleIDs) && !is_numeric($RoleIDs)) {
         // The $RoleIDs are a comma delimited list of role names.
         $RoleNames = array_map('trim', explode(',', $RoleIDs));
         $RoleIDs = $this->SQL
            ->Select('r.RoleID')
            ->From('Role r')
            ->WhereIn('r.Name', $RoleNames)
            ->Get()->ResultArray();
         $RoleIDs = ConsolidateArrayValuesByKey($RoleIDs, 'RoleID');
      }
      
      if (!is_array($RoleIDs))
         $RoleIDs = array($RoleIDs);

      // Get the current roles.
      $OldRoleIDs = array();
      $OldRoleData = $this->SQL
         ->Select('ur.RoleID, r.Name')
         ->From('Role r')
         ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Get()
         ->ResultArray();

      if ($OldRoleData !== FALSE) {
         $OldRoleIDs = ConsolidateArrayValuesByKey($OldRoleData, 'RoleID');
      }
      
      // 1a) Figure out which roles to delete.
      $DeleteRoleIDs = array_diff($OldRoleIDs, $RoleIDs);
      // 1b) Remove old role associations for this user.
      if(count($DeleteRoleIDs) > 0)
         $this->SQL->WhereIn('RoleID', $DeleteRoleIDs)->Delete('UserRole', array('UserID' => $UserID));
      
      // 2a) Figure out which roles to insert.
      $InsertRoleIDs = array_diff($RoleIDs, $OldRoleIDs);
      // 2b) Insert the new role associations for this user.
      foreach($InsertRoleIDs as $InsertRoleID) {
         if (is_numeric($InsertRoleID))
            $this->SQL->Insert('UserRole', array('UserID' => $UserID, 'RoleID' => $InsertRoleID));
      }
      
      $this->ClearCache($UserID, array('roles', 'permissions'));

      if ($RecordActivity && (count($DeleteRoleIDs) > 0 || count($InsertRoleIDs) > 0)) {
         $User = $this->GetID($UserID);
         $Session = Gdn::Session();

         $OldRoles = FALSE;
         if ($OldRoleData !== FALSE)
            $OldRoles = ConsolidateArrayValuesByKey($OldRoleData, 'Name');

         $NewRoles = FALSE;
         $NewRoleData = $this->SQL
            ->Select('r.RoleID, r.Name')
            ->From('Role r')
            ->Join('UserRole ur', 'r.RoleID = ur.RoleID')
            ->Where('ur.UserID', $UserID)
            ->Get()
            ->ResultArray();
         if ($NewRoleData !== FALSE)
            $NewRoles = ConsolidateArrayValuesByKey($NewRoleData, 'Name');


         $RemovedRoles = array_diff($OldRoles, $NewRoles);
         $NewRoles = array_diff($NewRoles, $OldRoles);

         $RemovedCount = count($RemovedRoles);
         $NewCount = count($NewRoles);
         $Story = '';
         if ($RemovedCount > 0 && $NewCount > 0) {
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s and added to the %4$s %5$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles'),
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         } else if ($RemovedCount > 0) {
            $Story = sprintf(T('%1$s was removed from the %2$s %3$s.'),
               $User->Name,
               implode(', ', $RemovedRoles),
               Plural($RemovedCount, 'role', 'roles')
            );
         } else if ($NewCount > 0) {
            $Story = sprintf(T('%1$s was added to the %2$s %3$s.'),
               $User->Name,
               implode(', ', $NewRoles),
               Plural($NewCount, 'role', 'roles')
            );
         }

//         AddActivity(
//            $Session->UserID != 0 ? $Session->UserID : $UserID,
//            'RoleChange',
//            $Story,
//            $UserID
//         );
      }
   }

   public function Search($Keywords, $OrderFields = '', $OrderDirection = 'asc', $Limit = FALSE, $Offset = FALSE) {
      if (C('Garden.Registration.Method') == 'Approval')
         $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      else
         $ApplicantRoleID = 0;

      if (is_array($Keywords)) {
         $Where = $Keywords;
         $Keywords = $Where['Keywords'];
         unset($Where['Keywords']);
      }

      // Check for an IP address.
      if (preg_match('`\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}`', $Keywords)) {
         $IPAddress = $Keywords;
      } else {
         // Check to see if the search exactly matches a role name.
         $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');
      }

      $this->UserQuery();
      if ($ApplicantRoleID != 0) {
         $this->SQL
            ->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');
      }

      if (isset($Where))
         $this->SQL->Where($Where);

      if (isset($RoleID) && $RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } elseif (isset($IPAddress)) {
         $this->SQL
            ->BeginWhereGroup()
            ->OrWhere('u.InsertIPAddress', $IPAddress)
            ->OrWhere('u.LastIPAddress', $IPAddress)
            ->EndWhereGroup();
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);
         
         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

      if ($ApplicantRoleID != 0)
         $this->SQL->Where('ur.RoleID is null');

      return $this->SQL
         ->Where('u.Deleted', 0)
         ->OrderBy($OrderFields, $OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }

   public function SearchCount($Keywords = FALSE) {
      if (C('Garden.Registration.Method') == 'Approval')
         $ApplicantRoleID = (int)C('Garden.Registration.ApplicantRoleID', 0);
      else
         $ApplicantRoleID = 0;

      if (is_array($Keywords)) {
         $Where = $Keywords;
         $Keywords = $Where['Keywords'];
         unset($Where['Keywords']);
      }

      // Check to see if the search exactly matches a role name.
      $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Keywords))->Value('RoleID');

      if (isset($Where))
         $this->SQL->Where($Where);
      
      $this->SQL
         ->Select('u.UserID', 'count', 'UserCount')
         ->From('User u');
      if ($ApplicantRoleID != 0)
         $this->SQL->Join('UserRole ur', "u.UserID = ur.UserID and ur.RoleID = $ApplicantRoleID", 'left');

      if ($RoleID) {
         $this->SQL->Join('UserRole ur2', "u.UserID = ur2.UserID and ur2.RoleID = $RoleID");
      } else {
         // Search on the user table.
         $Like = trim($Keywords) == '' ? FALSE : array('u.Name' => $Keywords, 'u.Email' => $Keywords);

         if (is_array($Like)) {
            $this->SQL
               ->BeginWhereGroup()
               ->OrLike($Like, '', 'right')
               ->EndWhereGroup();
         }
      }

		$this->SQL
         ->Where('u.Deleted', 0);
      
      if ($ApplicantRoleID != 0)
         $this->SQL->Where('ur.RoleID is null');

		$Data =  $this->SQL->Get()->FirstRow();

      return $Data === FALSE ? 0 : $Data->UserCount;
   }

   /**
    * To be used for invitation registration
    */
   public function InsertForInvite($FormPostValues, $Options = array()) {
      $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
      if (!is_array($RoleIDs) || count($RoleIDs) == 0)
         throw new Exception(T('The default role has not been configured.'), 400);

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure that the checkbox val for email is saved as the appropriate enum
      // TODO: DO I REALLY NEED THIS???
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      // Make sure that the user has a valid invitation code, and also grab
      // the user's email from the invitation:
      $InviteUserID = 0;
      $InviteUsername = '';
      $InvitationCode = ArrayValue('InvitationCode', $FormPostValues, '');
      $this->SQL->Select('i.InvitationID, i.InsertUserID, i.Email')
         ->Select('s.Name', '', 'SenderName')
         ->From('Invitation i')
         ->Join('User s', 'i.InsertUserID = s.UserID', 'left')
         ->Where('Code', $InvitationCode)
         ->Where('AcceptedUserID is null'); // Do not let them use the same invitation code twice!
      $InviteExpiration = Gdn::Config('Garden.Registration.InviteExpiration');
      if ($InviteExpiration != 'FALSE' && $InviteExpiration !== FALSE)
         $this->SQL->Where('i.DateInserted >=', Gdn_Format::ToDateTime(strtotime($InviteExpiration)));

      $Invitation = $this->SQL->Get()->FirstRow();
      if ($Invitation !== FALSE) {
         $InviteUserID = $Invitation->InsertUserID;
         $InviteUsername = $Invitation->SenderName;
         $FormPostValues['Email'] = $Invitation->Email;
      }
      if ($InviteUserID <= 0) {
         $this->Validation->AddValidationResult('InvitationCode', 'ErrorBadInvitationCode');
         return FALSE;
      }

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         // Check for spam.
         $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
         if ($Spam) {
            $this->Validation->AddValidationResult('Spam', 'You are not allowed to register at this time.');
            return;
         }

         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         // Make sure the username & email aren't already being used
         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         if ($InviteUserID > 0)
            $Fields['InviteUserID'] = $InviteUserID;


         // And insert the new user.
         if (!isset($Options['NoConfirmEmail']))
            $Options['NoConfirmEmail'] = TRUE;

         $Fields['Roles'] = $RoleIDs;
         $UserID = $this->_Insert($Fields, $Options);

         // Associate the new user id with the invitation (so it cannot be used again)
         $this->SQL
            ->Update('Invitation')
            ->Set('AcceptedUserID', $UserID)
            ->Where('InvitationID', $Invitation->InvitationID)
            ->Put();

         // Report that the user was created
         AddActivity(
            $UserID,
            'JoinInvite',
            T('Welcome Aboard!'),
            $InviteUserID
         );
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for approval registration
    */
   public function InsertForApproval($FormPostValues, $Options = array()) {
      $RoleIDs = C('Garden.Registration.ApplicantRoleID');
      if (!$RoleIDs) {
         throw new Exception(T('The default role has not been configured.'), 400);
      }

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules:
      $this->Validation->ApplyRule('Email', 'Email');

      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE)) {
         // Check for spam.
         $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
         if ($Spam) {
            $this->Validation->AddValidationResult('Spam', 'You are not allowed to register at this time.');
            return;
         }

         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         $Fields['Email'] = $Email;
         $Fields['Roles'] = (array)$RoleIDs;

         // And insert the new user
         $UserID = $this->_Insert($Fields, $Options);
      } else {
         $UserID = FALSE;
      }
      return $UserID;
   }

   /**
    * To be used for basic registration, and captcha registration
    */
   public function InsertForBasic($FormPostValues, $CheckCaptcha = TRUE, $Options = array()) {
      $RoleIDs = Gdn::Config('Garden.Registration.DefaultRoles');
      if (!is_array($RoleIDs) || count($RoleIDs) == 0)
         throw new Exception(T('The default role has not been configured.'), 400);

      $UserID = FALSE;

      // Define the primary key in this model's table.
      $this->DefineSchema();

      // Add & apply any extra validation rules.
      if (GetValue('ValidateEmail', $Options, TRUE))
         $this->Validation->ApplyRule('Email', 'Email');

      // TODO: DO I NEED THIS?!
      // Make sure that the checkbox val for email is saved as the appropriate enum
      if (array_key_exists('ShowEmail', $FormPostValues))
         $FormPostValues['ShowEmail'] = ForceBool($FormPostValues['ShowEmail'], '0', '1', '0');

      $this->AddInsertFields($FormPostValues);

      if ($this->Validate($FormPostValues, TRUE) === TRUE) {
         // Check for spam.
         $Spam = SpamModel::IsSpam('Registration', $FormPostValues);
         if ($Spam) {
            $this->Validation->AddValidationResult('Spam', 'You are not allowed to register at this time.');
            return;
         }

         $Fields = $this->Validation->ValidationFields(); // All fields on the form that need to be validated (including non-schema field rules defined above)
         $Username = ArrayValue('Name', $Fields);
         $Email = ArrayValue('Email', $Fields);
         $Fields = $this->Validation->SchemaValidationFields(); // Only fields that are present in the schema
         $Fields['Roles'] = $RoleIDs;
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);

         // If in Captcha registration mode, check the captcha value
         if ($CheckCaptcha && Gdn::Config('Garden.Registration.Method') == 'Captcha') {
            $CaptchaPublicKey = ArrayValue('Garden.Registration.CaptchaPublicKey', $FormPostValues, '');
            $CaptchaValid = ValidateCaptcha($CaptchaPublicKey);
            if ($CaptchaValid !== TRUE) {
               $this->Validation->AddValidationResult('Garden.Registration.CaptchaPublicKey', 'The reCAPTCHA value was not entered correctly. Please try again.');
               return FALSE;
            }
         }

         if (!$this->ValidateUniqueFields($Username, $Email))
            return FALSE;

         // Define the other required fields:
         $Fields['Email'] = $Email;

         // And insert the new user
         $UserID = $this->_Insert($Fields, $Options);

         AddActivity(
            $UserID,
            'Join',
            T('Welcome Aboard!')
         );
      }
      return $UserID;
   }

   // parent override
   public function AddInsertFields(&$Fields) {
      $this->DefineSchema();

      // Set the hour offset based on the client's clock.
      $ClientHour = ArrayValue('ClientHour', $Fields, '');
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $Fields['HourOffset'] = $HourOffset;
      }

      // Set some required dates.
      $Now = Gdn_Format::ToDateTime();
      $Fields[$this->DateInserted] = $Now;
      $Fields['DateFirstVisit'] = $Now;
      $Fields['DateLastActive'] = $Now;
   }

   /**
    * Update last visit.
    *
    * Regenerates other related user properties.
    *
    * @param int $UserID
    * @param array $Attributes
    * @param string|int|float $ClientHour
    */
   function UpdateLastVisit($UserID, $Attributes, $ClientHour='') {
      $UserID = (int) $UserID;
      if (!$UserID) {
         throw new Exception('A valid UserId is required.');
      }

      $this->SQL->Update('User')
         ->Set('DateLastActive', Gdn_Format::ToDateTime())
         ->Set('LastIPAddress', Gdn::Request()->IpAddress())
         ->Set('CountVisits', 'CountVisits + 1', FALSE);

      if (isset($Attributes) && is_array($Attributes)) {
         // Generate a new transient key for the user (used to authenticate postbacks).
         $Attributes['TransientKey'] = RandomString(12);
         $this->SQL->Set(
         	'Attributes', Gdn_Format::Serialize($Attributes));
      }

      // Set the hour offset based on the client's clock.
      if (is_numeric($ClientHour) && $ClientHour >= 0 && $ClientHour < 24) {
         $HourOffset = $ClientHour - date('G', time());
         $this->SQL->Set('HourOffset', $HourOffset);
      }

      $this->SQL->Where('UserID', $UserID)->Put();
   }

   /**
    * Validate User Credential
    *
    * Fetches a user row by email (or name) and compare the password.
    *
    * If the password was not stored as a blowfish hash,
    * the password will be saved again.
    *
    * Return the user's id, admin status and attributes.
    *
    * @param string $Email
    * @param string $Password
    * @return object
    */
   public function ValidateCredentials($Email = '', $ID = 0, $Password) {
      $this->EventArguments['Credentials'] = array('Email'=>$Email, 'ID'=>$ID, 'Password'=>$Password);
      $this->FireEvent('BeforeValidateCredentials');

      if (!$Email && !$ID)
         throw new Exception('The email or id is required');

		try {
			$this->SQL->Select('UserID, Name, Attributes, Admin, Password, HashMethod, Deleted, Banned')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		} catch(Exception $Ex) {
         $this->SQL->Reset();
         
			// Try getting the user information without the new fields.
			$this->SQL->Select('UserID, Name, Attributes, Admin, Password')
				->From('User');
	
			if ($ID) {
				$this->SQL->Where('UserID', $ID);
			} else {
				if (strpos($Email, '@') > 0) {
					$this->SQL->Where('Email', $Email);
				} else {
					$this->SQL->Where('Name', $Email);
				}
			}
	
			$DataSet = $this->SQL->Get();
		}
		
      if ($DataSet->NumRows() < 1)
         return FALSE;

      $UserData = $DataSet->FirstRow();
		// Check for a deleted user.
		if(GetValue('Deleted', $UserData))
			return FALSE;
		
		
      $PasswordHash = new Gdn_PasswordHash();
		$HashMethod = GetValue('HashMethod', $UserData);
      if(!$PasswordHash->CheckPassword($Password, $UserData->Password, $HashMethod, $UserData->Name))
         return FALSE;

      if ($PasswordHash->Weak || ($HashMethod && strcasecmp($HashMethod, 'Vanilla') != 0)) {
         $PasswordHash = new Gdn_PasswordHash();
         $this->SQL->Update('User')
            ->Set('Password', $PasswordHash->HashPassword($Password))
				->Set('HashMethod', 'Vanilla')
            ->Where('UserID', $UserData->UserID)
            ->Put();
      }

      $UserData->Attributes = Gdn_Format::Unserialize($UserData->Attributes);
      return $UserData;
   }

   /**
    * Checks to see if $Username and $Email are already in use by another member.
    */
   public function ValidateUniqueFields($Username, $Email, $UserID = '') {
      //die(var_dump(array($Username, $Email, $UserID)));


      $Valid = TRUE;
      $Where = array();
      if (is_numeric($UserID))
         $Where['UserID <> '] = $UserID;

      // Make sure the username & email aren't already being used
      if (C('Garden.Registration.NameUnique', TRUE) && $Username) {
         $Where['Name'] = $Username;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $this->Validation->AddValidationResult('Name', 'The name you entered is already in use by another member.');
            $Valid = FALSE;
         }
         unset($Where['Name']);
      }
      
      if (C('Garden.Registration.EmailUnique', TRUE) && $Email) {
         $Where['Email'] = $Email;
         $TestData = $this->GetWhere($Where);
         if ($TestData->NumRows() > 0) {
            $this->Validation->AddValidationResult('Email', 'The email you entered is in use by another member.');
            $Valid = FALSE;
         }
      }
      return $Valid;
   }

   /**
    * Approve a membership applicant.
    */
   public function Approve($UserID, $Email) {
      $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', 0);
      
      // Make sure the $UserID is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $AppRoles = $RoleData->Result(DATASET_TYPE_ARRAY);
         $ApplicantFound = FALSE;
         foreach ($AppRoles as $AppRole)
            if (GetValue('RoleID', $AppRole) == $ApplicantRoleID) $ApplicantFound = TRUE;
      }

      if ($ApplicantFound) {
         // Retrieve the default role(s) for new users
         $RoleIDs = C('Garden.Registration.DefaultRoles', array(8));

         // Wipe out old & insert new roles for this user
         $this->SaveRoles($UserID, $RoleIDs, FALSE);

         // Send out a notification to the user
         $User = $this->GetID($UserID);
         if ($User) {
				$Email->Subject(sprintf(T('[%1$s] Membership Approved'), C('Garden.Title')));
				$Email->Message(sprintf(T('EmailMembershipApproved'), $User->Name, ExternalUrl(SignInUrl())));
				$Email->To($User->Email);
				//$Email->From(C('Garden.SupportEmail'), C('Garden.SupportName'));
				$Email->Send();
         }

         // Report that the user was approved
         $Session = Gdn::Session();
         AddActivity(
            $Session->UserID,
            'JoinApproved',
            T('Welcome Aboard!'),
            $UserID,
            '',
            FALSE
         );
      }
      return TRUE;
   }

   public function Delete($UserID, $Options = array()) {
      // Fire an event so applications can remove their associated user data.
      $this->EventArguments['UserID'] = $UserID;
      $this->EventArguments['Options'] = $Options;
      $this->FireEvent('BeforeDeleteUser');

      // Remove photos
      $PhotoData = $this->SQL->Select()->From('Photo')->Where('InsertUserID', $UserID)->Get();
      foreach ($PhotoData->Result() as $Photo) {
         @unlink(PATH_LOCAL_UPLOADS.DS.$Photo->Name);
      }
      $this->SQL->Delete('Photo', array('InsertUserID' => $UserID));
      
      // Remove invitations
      $this->SQL->Delete('Invitation', array('InsertUserID' => $UserID));
      $this->SQL->Delete('Invitation', array('AcceptedUserID' => $UserID));
      
      // Remove activities
      $this->SQL->Delete('Activity', array('ActivityUserID' => $UserID));
      $this->SQL->Delete('Activity', array('RegardingUserID' => $UserID));
      
      // Remove shared authentications
      $this->SQL->Delete('UserAuthentication', array('UserID' => $UserID));

      // Remove role associations
      $this->SQL->Delete('UserRole', array('UserID' => $UserID));

      // Remove foreign account associations
      $this->SQL->Delete('UserAuthentication', array('UserID' => $UserID));
      
      // Remove the user's information
      $this->SQL->Update('User')
         ->Set(array(
            'Name' => '[Deleted User]',
            'Photo' => null,
            'Password' => RandomString('10'),
            'About' => '',
            'Email' => 'user_'.$UserID.'@deleted.email',
            'ShowEmail' => '0',
            'Gender' => 'm',
            'CountVisits' => 0,
            'CountInvitations' => 0,
            'CountNotifications' => 0,
            'InviteUserID' => null,
            'DiscoveryText' => '',
            'Preferences' => null,
            'Permissions' => null,
            'Attributes' => Gdn_Format::Serialize(array('State' => 'Deleted')),
            'DateSetInvitations' => null,
            'DateOfBirth' => null,
            'DateUpdated' => Gdn_Format::ToDateTime(),
            'HourOffset' => '0',
            'Score' => null,
            'Admin' => 0,
            'Deleted' => 1
            ))
         ->Where('UserID', $UserID)
         ->Put();

      // Remove user's cache rows
      $this->ClearCache($UserID);
      
      return TRUE;
   }

   public function Decline($UserID) {
      $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', 0);
      
      // Make sure the user is an applicant
      $RoleData = $this->GetRoles($UserID);
      if ($RoleData->NumRows() == 0) {
         throw new Exception(T('ErrorRecordNotFound'));
      } else {
         $AppRoles = $RoleData->Result(DATASET_TYPE_ARRAY);
         $ApplicantFound = FALSE;
         foreach ($AppRoles as $AppRole)
            if (GetValue('RoleID', $AppRole) == $ApplicantRoleID) $ApplicantFound = TRUE;
      }

      if ($ApplicantFound) {
         $this->Delete($UserID);
      }
      return TRUE;
   }

   public function GetInvitationCount($UserID) {
      // If this user is master admin, they should have unlimited invites.
      if ($this->SQL
         ->Select('UserID')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Where('Admin', '1')
         ->Get()
         ->NumRows() > 0
      ) return -1;

      // Get the Registration.InviteRoles settings:
      $InviteRoles = Gdn::Config('Garden.Registration.InviteRoles', array());
      if (!is_array($InviteRoles) || count($InviteRoles) == 0)
         return 0;

      // Build an array of roles that can send invitations
      $CanInviteRoles = array();
      foreach ($InviteRoles as $RoleID => $Invites) {
         if ($Invites > 0 || $Invites == -1)
            $CanInviteRoles[] = $RoleID;
      }

      if (count($CanInviteRoles) == 0)
         return 0;

      // See which matching roles the user has
      $UserRoleData = $this->SQL->Select('RoleID')
         ->From('UserRole')
         ->Where('UserID', $UserID)
         ->WhereIn('RoleID', $CanInviteRoles)
         ->Get();

      if ($UserRoleData->NumRows() == 0)
         return 0;

      // Define the maximum number of invites the user is allowed to send
      $InviteCount = 0;
      foreach ($UserRoleData->Result() as $UserRole) {
         $Count = $InviteRoles[$UserRole->RoleID];
         if ($Count == -1) {
            $InviteCount = -1;
         } else if ($InviteCount != -1 && $Count > $InviteCount) {
            $InviteCount = $Count;
         }
      }

      // If the user has unlimited invitations, return that value
      if ($InviteCount == -1)
         return -1;

      // Get the user's current invitation settings from their profile
      $User = $this->SQL->Select('CountInvitations, DateSetInvitations')
         ->From('User')
         ->Where('UserID', $UserID)
         ->Get()
         ->FirstRow();

      // If CountInvitations is null (ie. never been set before) or it is a new month since the DateSetInvitations
      if ($User->CountInvitations == '' || is_null($User->DateSetInvitations) || Gdn_Format::Date($User->DateSetInvitations, 'n Y') != Gdn_Format::Date('', 'n Y')) {
         // Reset CountInvitations and DateSetInvitations
         $this->SQL->Put(
            $this->Name,
            array(
               'CountInvitations' => $InviteCount,
               'DateSetInvitations' => Gdn_Format::Date('', 'Y-m-01') // The first day of this month
            ),
            array('UserID' => $UserID)
         );
         return $InviteCount;
      } else {
         // Otherwise return CountInvitations
         return $User->CountInvitations;
      }
   }

   /**
    * Reduces the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to reduce CountInvitations by.
    */
   public function ReduceInviteCount($UserID, $ReduceBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not reduce if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      // Do not reduce the count below zero.
      if ($ReduceBy > $CurrentCount)
         $ReduceBy = $CurrentCount;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations - '.$ReduceBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /**
    * Increases the user's CountInvitations value by the specified amount.
    *
    * @param int The unique id of the user being affected.
    * @param int The number to increase CountInvitations by.
    */
   public function IncreaseInviteCount($UserID, $IncreaseBy = 1) {
      $CurrentCount = $this->GetInvitationCount($UserID);

      // Do not alter if the user has unlimited invitations
      if ($CurrentCount == -1)
         return TRUE;

      $this->SQL->Update($this->Name)
         ->Set('CountInvitations', 'CountInvitations + '.$IncreaseBy, FALSE)
         ->Where('UserID', $UserID)
         ->Put();
   }

   /**
    * Saves the user's About field.
    *
    * @param int The UserID to save.
    * @param string The about message being saved.
    */
   public function SaveAbout($UserID, $About) {
      $About = substr($About, 0, 1000);
      $this->SQL->Update($this->Name)->Set('About', $About)->Where('UserID', $UserID)->Put();
      if (strlen($About) > 500)
         $About = SliceString($About, 500) . '...';
      
      
   }

   /**
    * Saves a name/value to the user's specified $Column.
    *
    * This method throws exceptions when errors are encountered. Use try ...
    * catch blocks to capture these exceptions.
    *
    * @param string The name of the serialized column to save to. At the time of this writing there are three serialized columns on the user table: Permissions, Preferences, and Attributes.
    * @param int The UserID to save.
    * @param mixed The name of the value being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveToSerializedColumn($Column, $UserID, $Name, $Value = '') {
      // Load the existing values
      $UserData = $this->GetID($UserID, DATASET_TYPE_OBJECT);

      if (!$UserData)
         throw new Exception(T('ErrorRecordNotFound'));

      $Values = GetValue($Column, $UserData);
      
      if (!is_array($Values) && !is_object($Values))
         $Values = @unserialize($UserData->$Column);
      
      // Throw an exception if the field was not empty but is also not an object or array
      if (is_string($Values) && $Values != '')
         throw new Exception(sprintf(T('Serialized column "%s" failed to be unserialized.'),$Column));

      if (!is_array($Values))
         $Values = array();
      
      // Hook for plugins
      $this->EventArguments['CurrentValues'] = &$Values;
      $this->EventArguments['Column'] = &$Column;
      $this->EventArguments['UserID'] = &$UserID;
      $this->EventArguments['Name'] = &$Name;
      $this->EventArguments['Value'] = &$Value;
      $this->FireEvent('BeforeSaveSerialized');

      // Assign the new value(s)
      if (!is_array($Name))
         $Name = array($Name => $Value);

      
      $RawValues = array_merge($Values, $Name);
      $Values = array();
      foreach ($RawValues as $Key => $RawValue)
         if (!is_null($RawValue))
            $Values[$Key] = $RawValue;
      
      $Values = Gdn_Format::Serialize($Values);

      // Save the values back to the db
      $SaveResult = $this->SQL->Put('User', array($Column => $Values), array('UserID' => $UserID));
      $this->ClearCache($UserID, array('user'));
      
      return $SaveResult;
   }

   /**
    * Saves a user preference to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the preference being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SavePreference($UserID, $Preference, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetPreference($Preference, $Value, FALSE);

      return $this->SaveToSerializedColumn('Preferences', $UserID, $Preference, $Value);
   }

   /**
    * Saves a user attribute to the database.
    *
    * This is a convenience method that uses $this->SaveToSerializedColumn().
    *
    * @param int The UserID to save.
    * @param mixed The name of the attribute being saved, or an associative array of name => value pairs to be saved. If this is an associative array, the $Value argument will be ignored.
    * @param mixed The value being saved.
    */
   public function SaveAttribute($UserID, $Attribute, $Value = '') {
      // Make sure that changes to the current user become effective immediately.
      $Session = Gdn::Session();
      if ($UserID == $Session->UserID)
         $Session->SetAttribute($Attribute, $Value);

      return $this->SaveToSerializedColumn('Attributes', $UserID, $Attribute, $Value);
   }

   public function SaveAuthentication($Data) {
      $Cn = $this->Database->Connection();
      $Px = $this->Database->DatabasePrefix;

      $UID = $Cn->quote($Data['UniqueID']);
      $Provider = $Cn->quote($Data['Provider']);
      $UserID = $Cn->quote($Data['UserID']);

      $Sql = "insert {$Px}UserAuthentication (ForeignUserKey, ProviderKey, UserID) values ($UID, $Provider, $UserID) on duplicate key update UserID = $UserID";
      $Result = $this->Database->Query($Sql);
      return $Result;
   }

   public function SetCalculatedFields(&$User) {
      if ($v = GetValue('Attributes', $User))
         SetValue('Attributes', $User, @unserialize($v));
      if ($v = GetValue('Permissions', $User))
         SetValue('Permissions', $User, @unserialize($v));
      if ($v = GetValue('Preferences', $User))
         SetValue('Preferences', $User, @unserialize($v));
      if ($v = GetValue('Photo', $User)) {
         if (!preg_match('`^https?://`i', $v)) {
            $PhotoUrl = Gdn_Upload::Url(ChangeBasename($v, 'n%s'));
         } else {
            $PhotoUrl = $v;
         }
         
         SetValue('PhotoUrl', $User, $PhotoUrl);
      }
   }

   public static function SetMeta($UserID, $Meta, $Prefix = '') {
      $Deletes = array();
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "insert {$Px}UserMeta (UserID, Name, Value) values(:UserID, :Name, :Value) on duplicate key update Value = :Value1";

      foreach ($Meta as $Name => $Value) {
         $Name = $Prefix.$Name;
         if ($Value === NULL)
            $Deletes[] = $Name;
         else
            Gdn::Database()->Query($Sql, array(':UserID' => $UserID, ':Name' => $Name, ':Value' => $Value, ':Value1' => $Value));
      }
      if (count($Deletes))
         Gdn::SQL()->WhereIn('Name', $Deletes)->Where('UserID',$UserID)->Delete('UserMeta');
   }

   public function SetTransientKey($UserID, $ExplicitKey = '') {
      $Key = $ExplicitKey == '' ? RandomString(12) : $ExplicitKey;
      $this->SaveAttribute($UserID, 'TransientKey', $Key);
      return $Key;
   }

   public function GetAttribute($UserID, $Attribute, $DefaultValue = FALSE) {
//
//      $Result = $DefaultValue;
//      if ($Data !== FALSE) {
//         $Attributes = Gdn_Format::Unserialize($Data->Attributes);
//         if (is_array($Attributes))
//            $Result = ArrayValue($Attribute, $Attributes, $DefaultValue);
//
//      }
      
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      $Result = GetValue($Attribute, $User['Attributes'], $DefaultValue);
      
      return $Result;
   }

   public function SendEmailConfirmationEmail($User = NULL) {
      if (!$User)
         $User = Gdn::Session()->User;
      elseif (is_numeric($User))
         $User = $this->GetID($User);
      elseif (is_string($User)) {
         $User = $this->GetByEmail($User);
      }
      
      if (!$User)
         throw NotFoundException('User');
      
      $User = (array)$User;

      if (is_string($User['Attributes']))
         $User['Attributes'] = @unserialize($User['Attributes']);

      // Make sure the user needs email confirmation.
      $Roles = $this->GetRoles($User['UserID']);
      $Roles = ConsolidateArrayValuesByKey($Roles, 'RoleID');
      if (!in_array(C('Garden.Registration.ConfirmEmailRole'), $Roles)) {
         $this->Validation->AddValidationResult('Role', 'Your email doesn\'t need confirmation.');
         
         // Remove the email key.
         if (isset($User['Attributes']['EmailKey'])) {
            unset($User['Attributes']['EmailKey']);
            $this->SaveAttribute($User['UserID'], $User['Attribute']);
         }

         return;
      }

      // Make sure there is a confirmation code.
      $Code = GetValueR('Attributes.EmailKey', $User);
      if (!$Code) {
         $Code = RandomString(8);
         $Attributes = $User['Attributes'];
         if (!is_array($Attributes))
            $Attributes = array('EmailKey' => $Code);
         else
            $Attributes['EmailKey'] = $Code;
         
         $this->SaveAttribute($User['UserID'], $Attributes);
      }
      
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Confirm Your Email Address'), $AppTitle));
      $Email->To($User['Email']);

      $EmailFormat = T('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL);
      $Data = array();
      $Data['EmailKey'] = $Code;
      $Data['User'] = ArrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
      $Data['Title'] = $AppTitle;

      $Message = FormatString($EmailFormat, $Data);
      $Message = $this->_AddEmailHeaderFooter($Message, $Data);
      $Email->Message($Message);

      $Email->Send();
   }

   public function SendWelcomeEmail($UserID, $Password, $RegisterType = 'Add', $AdditionalData = NULL) {
      $Session = Gdn::Session();
      $Sender = $this->GetID($Session->UserID);
      $User = $this->GetID($UserID);

      if (!ValidateEmail($User->Email))
         return;

      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Welcome Aboard!'), $AppTitle));
      $Email->To($User->Email);

      $Data = array();
      $Data['User'] = ArrayTranslate((array)$User, array('UserID', 'Name', 'Email'));
      $Data['Sender'] = ArrayTranslate((array)$Sender, array('Name', 'Email'));
      $Data['Title'] = $AppTitle;
      if (is_array($AdditionalData))
         $Data = array_merge($Data, $AdditionalData);

      $Data['EmailKey'] = GetValueR('Attributes.EmailKey', $User);

      // Check for the new email format.
      if (($EmailFormat = T("EmailWelcome{$RegisterType}", '#')) != '#') {
         $Message = FormatString($EmailFormat, $Data);
      } else {
         $Message = sprintf(
            T('EmailWelcome'),
            $User->Name,
            $Sender->Name,
            $AppTitle,
            ExternalUrl('/'),
            $Password,
            $User->Email
         );
      }

      // Add the email confirmation key.
      if ($Data['EmailKey']) {
         $Message .= "\n\n".FormatString(C('EmailConfirmEmail', self::DEFAULT_CONFIRM_EMAIL), $Data);
      }
      $Message = $this->_AddEmailHeaderFooter($Message, $Data);

      $Email->Message($Message);

      $Email->Send();
   }

   public function SendPasswordEmail($UserID, $Password) {
      $Session = Gdn::Session();
      $Sender = $this->GetID($Session->UserID);
      $User = $this->GetID($UserID);
      $AppTitle = Gdn::Config('Garden.Title');
      $Email = new Gdn_Email();
      $Email->Subject(sprintf(T('[%s] Password Reset'), $AppTitle));
      $Email->To($User->Email);

      $Data = array();
      $Data['User'] = ArrayTranslate((array)$User, array('Name', 'Email'));
      $Data['Sender'] = ArrayTranslate((array)$Sender, array('Name', 'Email'));
      $Data['Title'] = $AppTitle;

      $EmailFormat = T('EmailPassword');
      if (strpos($EmailFormat, '{') !== FALSE) {
         $Message = FormatString($EmailFormat, $Data);
      } else {
         $Message = sprintf(
            $EmailFormat,
            $User->Name,
            $Sender->Name,
            $AppTitle,
            ExternalUrl('/'),
            $Password,
            $User->Email
         );
      }

      $Message = $this->_AddEmailHeaderFooter($Message, $Data);
      $Email->Message($Message);

      $Email->Send();
   }
   
   /**
    * Synchronizes the user based on a given UserKey.
    *
    * @param string $UserKey A string that uniquely identifies this user.
    * @param array $Data Information to put in the user table.
    * @return int The ID of the user.
    */
   public function Synchronize($UserKey, $Data) {
      $UserID = 0;
      
      $Attributes = ArrayValue('Attributes', $Data);
      if (is_string($Attributes))
         $Attributes = @unserialize($Attributes);

      if (!is_array($Attributes))
         $Attributes = array();

      // If the user didnt log in, they won't have a UserID yet. That means they want a new
      // account. So create one for them.
      if (!isset($Data['UserID']) || $Data['UserID'] <= 0) {
      
         // Prepare the user data.
         $UserData['Name'] = $Data['Name'];
         $UserData['Password'] = RandomString(16);
         $UserData['Email'] = ArrayValue('Email', $Data, 'no@email.com');
         $UserData['Gender'] = strtolower(substr(ArrayValue('Gender', $Attributes, 'm'), 0, 1));
         $UserData['HourOffset'] = ArrayValue('HourOffset', $Attributes, 0);
         $UserData['DateOfBirth'] = ArrayValue('DateOfBirth', $Attributes, '');
         $UserData['CountNotifications'] = 0;
         $UserData['Attributes'] = $Attributes;
         if ($UserData['DateOfBirth'] == '')
            $UserData['DateOfBirth'] = '1975-09-16';
            
         // Make sure there isn't another user with this username.
         if ($this->ValidateUniqueFields($UserData['Name'], $UserData['Email'])) {
            // Insert the new user.
            $this->AddInsertFields($UserData);
            $UserID = $this->_Insert($UserData);
         }

         if ($UserID) {
            $NewUserRoleIDs = $this->NewUserRoleIDs();
            
            // Save the roles.
            $Roles = GetValue('Roles', $Data, FALSE);
            if (empty($Roles))
               $Roles = $NewUserRoleIDs;
            
            $this->SaveRoles($UserID, $Roles, FALSE);
         }
      } else {
         $UserID = $Data['UserID'];
      }
      
      // Synchronize the transientkey from the external user data source if it is present (eg. WordPress' wpnonce).
      if (array_key_exists('TransientKey', $Attributes) && $Attributes['TransientKey'] != '' && $UserID > 0)
         $this->SetTransientKey($UserID, $Attributes['TransientKey']);

      return $UserID;
   }
   
   public function NewUserRoleIDs() {
      // Registration method
      $RegistrationMethod = C('Garden.Registration.Method', 'Captcha');
      $DefaultRoleID = C('Garden.Registration.DefaultRoles');
      switch ($RegistrationMethod) {
      
         case 'Approval':
            $RoleID = C('Garden.Registration.ApplicantRoleID', $DefaultRoleID);
         break;
         
         case 'Invitation':
            throw new Gdn_UserException(T('This forum is currently set to invitation only mode.'));
         break;
         
         case 'Basic':
         case 'Captcha':
         default:
            $RoleID = $DefaultRoleID;
         break;
      }
      
      return $RoleID;
   }
   
   public function PasswordRequest($Email) {
      if (!$Email)
         return FALSE;

      $Users = $this->GetWhere(array('Email' => $Email))->ResultObject();
      if (count($Users) == 0 && C('Garden.Registration.NameUnique', 1)) {
         // Check for the username.
         $Users = $this->GetWhere(array('Name' => $Email))->ResultObject();
      }

      $this->EventArguments['Users'] =& $Users;
      $this->EventArguments['Email'] = $Email;
      $this->FireEvent('BeforePasswordRequest');
      
      if (count($Users) == 0)
            return FALSE;

      $Email = new Gdn_Email();
      foreach ($Users as $User) {
         $PasswordResetKey = RandomString(6);
         $this->SaveAttribute($User->UserID, 'PasswordResetKey', $PasswordResetKey);
         $AppTitle = C('Garden.Title');
         $Email->Subject(sprintf(T('[%s] Password Reset Request'), $AppTitle));
         $Email->To($User->Email);
         
         $Email->Message(
            sprintf(
               T('PasswordRequest'),
               $User->Name,
               $AppTitle,
               ExternalUrl('/entry/passwordreset/'.$User->UserID.'/'.$PasswordResetKey)
            )
         );
         $Email->Send();
      }
      return TRUE;
   }

   public function PasswordReset($UserID, $Password) {
      // Encrypt the password before saving
      $PasswordHash = new Gdn_PasswordHash();
      $Password = $PasswordHash->HashPassword($Password);

      $this->SQL->Update('User')->Set('Password', $Password)->Set('HashMethod', 'Vanilla')->Where('UserID', $UserID)->Put();
      $this->SaveAttribute($UserID, 'PasswordResetKey', '');

      $this->EventArguments['UserID'] = $UserID;
      $this->FireEvent('AfterPasswordReset');

      return $this->GetID($UserID);
   }
   
	public function SetField($RowID, $Property, $Value = FALSE) {
		$this->SQL
            ->Update($this->Name)
            ->Set($Property, $Value)
            ->Where('UserID', $RowID)
            ->Put();
      
      if (in_array($Property, array('Permissions')))
         $this->ClearCache ($RowID, array('permissions'));
      else
         $this->UpdateUserCache($RowID, $Property, $Value);
		return $Value;
   }
   
   /**
    * Get a user from the cache by name or ID
    * 
    * @param type $UserToken either a userid or a username
    * @param string $TokenType either 'userid' or 'name'
    * @return type user array or FALSE
    */
   public function GetUserFromCache($UserToken, $TokenType) {
      if ($TokenType == 'name') {
         $UserNameKey = FormatString(self::USERNAME_KEY, array('Name' => md5($UserToken)));
         $UserID = Gdn::Cache()->Get($UserNameKey);
         
         if ($UserID === Gdn_Cache::CACHEOP_FAILURE) return FALSE;
         $UserToken = $UserID; $TokenType = 'userid';
      } else {
         $UserID = $UserToken;
      }
      
      if ($TokenType != 'userid') return FALSE;
      
      // Check local page memory cache first
      if (array_key_exists($UserID, self::$UserCache))
         return self::$UserCache[$UserID];
      
      // Then memcached
      $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserToken));
      $User = Gdn::Cache()->Get($UserKey);
      
      return $User;
   }
   
   public function UpdateUserCache($UserID, $Field, $Value = NULL) {
      $User = $this->GetID($UserID, DATASET_TYPE_ARRAY);
      if (!is_array($Field))
         $Field = array($Field => $Value);
      
      foreach ($Field as $f => $v) {
         $User[$f] = $v;
      }
      $this->UserCache($User);
   }
   
   /**
    * Cache user object
    * 
    * @param type $User
    * @return type 
    */
   public function UserCache($User) {
      $UserID = GetValue('UserID', $User, NULL);
      if (is_null($UserID) || !$UserID) return FALSE;
      
      $Cached = TRUE;
      
      // Local memory page cache
      self::$UserCache[$UserID] = $User;
      
      $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserID));
      $Cached = $Cached & Gdn::Cache()->Store($UserKey, $User, array(
         Gdn_Cache::FEATURE_EXPIRY  => 3600
      ));
      
      $UserNameKey = FormatString(self::USERNAME_KEY, array('Name' => md5(GetValue('Name', $User))));
      $Cached = $Cached & Gdn::Cache()->Store($UserNameKey, $UserID, array(
         Gdn_Cache::FEATURE_EXPIRY  => 3600
      ));
      return $Cached;
   }
   
   /**
    * Cache user's roles
    * 
    * @param type $UserID
    * @param type $RoleIDs
    * @return type 
    */
   public function UserCacheRoles($UserID, $RoleIDs) {
      if (is_null($UserID) || !$UserID) return FALSE;
      
      $Cached = TRUE;
      
      $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
      $Cached = $Cached & Gdn::Cache()->Store($UserRolesKey, $RoleIDs);
      return $Cached;
   }
   
   /**
    * Delete cached data for user
    * 
    * @param type $UserID
    * @return type 
    */
   public function ClearCache($UserID, $CacheTypesToClear = NULL) {
      if (is_null($UserID) || !$UserID) return FALSE;
      
      if (is_null($CacheTypesToClear))
         $CacheTypesToClear = array('user', 'roles', 'permissions');
      
      if (in_array('user', $CacheTypesToClear)) {
         $UserKey = FormatString(self::USERID_KEY, array('UserID' => $UserID));
         Gdn::Cache()->Remove($UserKey);
      }
      
      if (in_array('roles', $CacheTypesToClear)) {
         $UserRolesKey = FormatString(self::USERROLES_KEY, array('UserID' => $UserID));
         Gdn::Cache()->Remove($UserRolesKey);
      }
      
      if (in_array('permissions', $CacheTypesToClear)) {
         Gdn::SQL()->Put('User', array('Permissions' => ''), array('UserID' => $UserID));
         
         $PermissionsIncrement = $this->GetPermissionsIncrement();
         $UserPermissionsKey = FormatString(self::USERPERMISSIONS_KEY, array('UserID' => $UserID, 'PermissionsIncrement' => $PermissionsIncrement));
         Gdn::Cache()->Remove($UserPermissionsKey);
      }
      return TRUE;
   }
   
   public function ClearPermissions() {
      $this->SQL->Put('User', array('Permissions' => ''), array('Permissions <>' => ''));
      
      $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
      $PermissionsIncrement = $this->GetPermissionsIncrement();
      if ($PermissionsIncrement == 0)
         Gdn::Cache()->Store($PermissionsIncrementKey, 1);
      else
         Gdn::Cache()->Increment($PermissionsIncrementKey);
   }
   
   public function GetPermissionsIncrement() {
      $PermissionsIncrementKey = self::INC_PERMISSIONS_KEY;
      $PermissionsKeyValue = Gdn::Cache()->Get($PermissionsIncrementKey);
      
      if (!$PermissionsKeyValue) {
         $Stored = Gdn::Cache()->Store($PermissionsIncrementKey, 1);
         return $Stored ? 1 : FALSE;
      }
      
      return $PermissionsKeyValue;;
   }
}

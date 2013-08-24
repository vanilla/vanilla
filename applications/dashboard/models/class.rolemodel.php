<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class RoleModel extends Gdn_Model {
   public static $Roles = NULL;
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Role');
   }
   
   public function ClearCache() {
      $Key = 'Roles';
      Gdn::Cache()->Remove($Key);
   }
   
   public function Define($Values) {
      if(array_key_exists('RoleID', $Values)) {
         $RoleID = $Values['RoleID'];
         unset($Values['RoleID']);
         
         $this->SQL->Replace('Role', $Values, array('RoleID' => $RoleID), TRUE);
      } else {
         // Check to see if there is a role with the same name.
         $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Values['Name']))->Value('RoleID', NULL);
         
         if(is_null($RoleID)) {
            // Figure out the next role ID.
            $MaxRoleID = $this->SQL->Select('r.RoleID', 'MAX')->From('Role r')->Get()->Value('RoleID', 0);
            $RoleID = $MaxRoleID + 1;
            $Values['RoleID'] = $RoleID;
            
            // Insert the role.
            $this->SQL->Insert('Role', $Values);
         } else {
            // Update the role.
            $this->SQL->Update('Role', $Values, array('RoleID' => $RoleID))->Put();
         }
      }
      $this->ClearCache();
   }
   
   /**
    * Returns a resultset of all roles.
    */
   public function Get() {
      return $this->SQL
         ->Select()
         ->From('Role')
         ->OrderBy('Sort', 'asc')
         ->Get();
   }
   
   /**
    * Returns an array of RoleID => RoleName pairs.
    *
    * @return array
    */
   public function GetArray() {
      // $RoleData = $this->GetEditablePermissions();
      $RoleData = $this->Get();
      $RoleIDs = ConsolidateArrayValuesByKey($RoleData->ResultArray(), 'RoleID');
      $RoleNames = ConsolidateArrayValuesByKey($RoleData->ResultArray(), 'Name');
      return ArrayCombine($RoleIDs, $RoleNames);
   }

   /**
    * Returns a resultset of all roles that have editable permissions.
    *
   public function GetEditablePermissions() {
      return $this->SQL
         ->Select()
         ->From('Role')
         ->Where('EditablePermissions', '1')
         ->OrderBy('Sort', 'asc')
         ->Get();
   }
   */

   /**
    * Returns a resultset of role data related to the specified RoleID.
    *
    * @param int The RoleID to filter to.
    */
   public function GetByRoleID($RoleID) {
      return $this->GetWhere(array('RoleID' => $RoleID))->FirstRow();
   }
   
   /**
    * Returns a resultset of role data related to the specified UserID.
    *
    * @param int The UserID to filter to.
    * @return Gdn_DataSet
    */
   public function GetByUserID($UserID) {
      return $this->SQL->Select()
         ->From('Role')
         ->Join('UserRole', 'Role.RoleID = UserRole.RoleID')
         ->Where('UserRole.UserID', $UserID)
         ->Get();
   }

   /**
    * Returns a resultset of role data NOT related to the specified RoleID.
    *
    * @param int The RoleID to filter out.
    */
   public function GetByNotRoleID($RoleID) {
      return $this->GetWhere(array('RoleID <>' => $RoleID));
   }
   
   public function GetPermissions($RoleID) {
      $PermissionModel = Gdn::PermissionModel();
      $LimitToSuffix = $this->CanSession == '1' ? '' : 'View';
      
      $Result = $PermissionModel->GetPermissions($RoleID, $LimitToSuffix);
      return $Result;      
   }
   
   /**
    * Returns the number of users assigned to the provided RoleID. If
    * $UsersOnlyWithThisRole is TRUE, it will return the number of users who
    * are assigned to this RoleID and NO OTHER.
    *
    * @param int The RoleID to filter to.
    * @param bool Indicating if the count should be any users with this RoleID, or users who are ONLY assigned to this RoleID.
    */
   public function GetUserCount($RoleID, $UsersOnlyWithThisRole = FALSE) {
      if ($UsersOnlyWithThisRole) {
         $Data = $this->SQL->Select('ur.UserID', 'count', 'UserCount')
            ->From('UserRole ur')
            ->Join('UserRole urs', 'ur.UserID = urs.UserID')
            ->GroupBy('urs.UserID')
            ->Having('count(urs.RoleID) =', '1', TRUE, FALSE)
            ->Where('ur.RoleID', $RoleID)
            ->Get()
            ->FirstRow();
            
         return $Data ? $Data->UserCount : 0;
      } else {
         return $this->SQL->GetCount('UserRole', array('RoleID' => $RoleID));
      }
   }
   
   public function GetApplicantCount($Force = FALSE) {
      $CacheKey = 'Moderation.ApplicantCount';
      
      if ($Force)
         Gdn::Cache()->Remove($CacheKey);
      
      $Count = Gdn::Cache()->Get($CacheKey);
      if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
         $Count = Gdn::SQL()
            ->Select('u.UserID', 'count', 'UserCount')
            ->From('User u')
            ->Join('UserRole ur', 'u.UserID = ur.UserID')
            ->Where('ur.RoleID',  C('Garden.Registration.ApplicantRoleID', 0))
            ->Where('u.Deleted', '0')
            ->Get()->Value('UserCount', 0);    
         
         Gdn::Cache()->Store($CacheKey, $Count, array(
            Gdn_Cache::FEATURE_EXPIRY  => 300 // 5 minutes
         ));
      }
      return $Count;
   }
   
   /**
    * Retrieves all roles with the specified permission(s).
    *
    * @param mixed A permission (or array of permissions) to match.
    */
   public function GetByPermission($Permission) {
      if (!is_array($Permission))
         $Permission = array($Permission);
         
      $this->SQL->Select('r.*')
         ->From('Role r')
         ->Join('Permission per', "per.RoleID = r.RoleID")
         ->Where('per.JunctionTable is null');
         
      $this->SQL->BeginWhereGroup();
      $PermissionCount = count($Permission);
      for ($i = 0; $i < $PermissionCount; ++$i) {
         $this->SQL->Where('per.`'.$Permission[$i].'`', 1);
      }
      $this->SQL->EndWhereGroup();
      return $this->SQL->Get();
   }
   
   /**
    *
    * @param array|string $Names 
    */
   public static function GetByName($Names, &$Missing = NULL) {
      if (is_string($Names)) {
         $Names = explode(',', $Names);
         $Names = array_map('trim', $Names);
      }
      
      // Make a lookup array of the names.
      $Names = array_unique($Names);
      $Names = array_combine($Names, $Names);
      $Names = array_change_key_case($Names);
      
      $Roles = RoleModel::Roles();
      $Result = array();
      foreach ($Roles as $RoleID => $Role) {
         $Name = strtolower($Role['Name']);
         
         if (isset($Names[$Name])) {
            $Result[$RoleID] = $Role;
            unset($Names[$Name]);
         }
      }
      
      $Missing = array_values($Names);
      
      return $Result;
   }
   
   public static function Roles($RoleID = NULL, $Force = FALSE) {
      if (self::$Roles == NULL) {
         $Key = 'Roles';
         $Roles = Gdn::Cache()->Get($Key);
         if ($Roles === Gdn_Cache::CACHEOP_FAILURE) {
            $Roles = Gdn::SQL()->Get('Role', 'Sort')->ResultArray();
            $Roles = Gdn_DataSet::Index($Roles, array('RoleID'));
            Gdn::Cache()->Store($Key, $Roles, array(Gdn_Cache::FEATURE_EXPIRY => 24 * 3600));
         }
      } else {
         $Roles = self::$Roles;
      }
      
      if ($RoleID === NULL)
         return $Roles;
      elseif (array_key_exists($RoleID, $Roles))
         return $Roles[$RoleID];
      elseif ($Force)
         return array('RoleID' => $RoleID, 'Name' => '');
      else
         return NULL;
   }
   
   public function Save($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      $RoleID = ArrayValue('RoleID', $FormPostValues);
      $Insert = $RoleID > 0 ? FALSE : TRUE;
      if ($Insert) {
         // Figure out the next role ID.
         $MaxRoleID = $this->SQL->Select('r.RoleID', 'MAX')->From('Role r')->Get()->Value('RoleID', 0);
         $RoleID = $MaxRoleID + 1;
         
         $this->AddInsertFields($FormPostValues);
         $FormPostValues['RoleID'] = strval($RoleID); // string for validation
      } else {
         $this->AddUpdateFields($FormPostValues);
      }
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         $this->Validation->AddValidationField('Permission', $FormPostValues);
         $Fields = $this->Validation->ValidationFields();
         $Permissions = ArrayValue('Permission', $Fields);
         $Fields = $this->Validation->SchemaValidationFields();

         if ($Insert === FALSE) {
            $this->Update($Fields, array('RoleID' => $RoleID));
         } else {
            $this->Insert($Fields);
         }
         // Now update the role permissions
         $Role = $this->GetByRoleID($RoleID);
         
         $PermissionModel = Gdn::PermissionModel();
         $Permissions = $PermissionModel->PivotPermissions($Permissions, array('RoleID' => $RoleID));
         $PermissionModel->SaveAll($Permissions, array('RoleID' => $RoleID));
         
         if (Gdn::Cache()->ActiveEnabled()) {
            // Don't update the user table if we are just using cached permissions.
            $this->ClearCache();
            Gdn::UserModel()->ClearPermissions();
         } else {
            // Remove the cached permissions for all users with this role.
            $this->SQL->Update('User')
               ->Join('UserRole', 'User.UserID = UserRole.UserID')
               ->Set('Permissions', '')
               ->Where(array('UserRole.RoleID' => $RoleID))
               ->Put();
         }
      } else {
         $RoleID = FALSE;
      }
      return $RoleID;
   }

   public static function SetUserRoles(&$Users, $UserIDColumn = 'UserID', $RolesColumn = 'Roles') {
      $UserIDs = array_unique(ConsolidateArrayValuesByKey($Users, $UserIDColumn));
      
      // Try and get all of the mappings from the cache.
      $Keys = array();
      foreach ($UserIDs as $UserID) {
         $Keys[$UserID] = FormatString(UserModel::USERROLES_KEY, array('UserID' => $UserID));
      }
      $UserRoles = Gdn::Cache()->Get($Keys);
      if (!is_array($UserRoles))
         $UserRoles = array();
      
      // Grab all of the data that doesn't exist from the DB.
      $MissingIDs = array();
      foreach($Keys as $UserID => $Key) {
         if (!array_key_exists($Key, $UserRoles)) {
            $MissingIDs[$UserID] = $Key;
         }
      }
      if (count($MissingIDs) > 0) {
         $DbUserRoles = Gdn::SQL()
         ->Select('ur.*')
         ->From('UserRole ur')
         ->WhereIn('ur.UserID', array_keys($MissingIDs))
         ->Get()->ResultArray();
         
         $DbUserRoles = Gdn_DataSet::Index($DbUserRoles, 'UserID', array('Unique' => FALSE));
         
         // Store the user role mappings.
         foreach ($DbUserRoles as $UserID => $Rows) {
            $RoleIDs = ConsolidateArrayValuesByKey($Rows, 'RoleID');
            $Key = $Keys[$UserID];
            Gdn::Cache()->Store($Key, $RoleIDs);
            $UserRoles[$Key] = $RoleIDs;
         }
      }
      
      $AllRoles = self::Roles(); // roles indexed by role id.
      
      // Join the users.
      foreach ($Users as &$User) {
         $UserID = GetValue($UserIDColumn, $User);
         $Key = $Keys[$UserID];
         
         $RoleIDs = GetValue($Key, $UserRoles, array());
         $Roles = array();
         foreach ($RoleIDs as $RoleID) {
            if (!array_key_exists($RoleID, $AllRoles))
               continue;
            $Roles[$RoleID] = $AllRoles[$RoleID]['Name'];
         }
         SetValue($RolesColumn, $User, $Roles);
      }
   }
   
   public function Delete($RoleID, $ReplacementRoleID) {
      // First update users that will be orphaned
      if (is_numeric($ReplacementRoleID) && $ReplacementRoleID > 0) {
         $this->SQL
            ->Options('Ignore', TRUE)
            ->Update('UserRole')
            ->Join('UserRole urs', 'UserRole.UserID = urs.UserID')
            ->GroupBy('urs.UserID')
            ->Having('count(urs.RoleID) =', '1', TRUE, FALSE)
            ->Set('UserRole.RoleID', $ReplacementRoleID)
            ->Where(array('UserRole.RoleID' => $RoleID))
            ->Put();
      }
      
      // Remove permissions for this role.
      $PermissionModel = Gdn::PermissionModel();
      $PermissionModel->Delete($RoleID);
      
      // Remove the role
      $this->SQL->Delete('Role', array('RoleID' => $RoleID));
   }
}
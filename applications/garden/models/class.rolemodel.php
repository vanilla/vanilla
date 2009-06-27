<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class Gdn_RoleModel extends Model {
   /// <summary>
   /// Class constructor. Defines the related database table name.
   /// </summary>
   /// <param name="Name" type="string" required="false" default="get_class($this)">
   /// An optional parameter that allows you to explicitly define the name of
   /// the table that this model represents. You can also explicitly set this
   /// value with $this->Name.
   /// </param>
   public function __construct() {
      parent::__construct('Role');
   }
   
   public function Define($Values) {
      if(array_key_exists('RoleID', $Values)) {
         $RoleID = $Values['RoleID'];
         unset($Values['RoleID']);
         
         $this->SQL->Replace('Role', $Values, array('RoleID' => $RoleID));
      } else {
         // Check to see if there is a role with the same name.
         $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Values['Name']))->Value('RoleID', NULL);
         
         if(is_null($RoleID)) {
            // Figure out the next role ID which is the next biggest power of two.
            $MaxRoleID = $this->SQL->Select('r.RoleID', 'MAX')->From('Role r')->Value('RoleID', 0);
            $RoleID = pow(2, ceil(log($MaxRoleID + 1, 2)));
            $Values['RoleID'] = $RoleID;
            
            // Insert the role.
            $this->SQL->Insert('Role', $Values);
         } else {
            // Update the role.
            $this->SQL->Update('Role', $Values, array('RoleID' => $RoleID));
         }
      }
      
   }
   
   /// <summary>
   /// Returns a resultset of all roles.
   /// </summary>
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
      return array_combine($RoleIDs, $RoleNames);
   }
   /*
   /// <summary>
   /// Returns a resultset of all roles that have editable permissions.
   /// </summary>
   public function GetEditablePermissions() {
      return $this->SQL
         ->Select()
         ->From('Role')
         ->Where('EditablePermissions', '1')
         ->OrderBy('Sort', 'asc')
         ->Get();
   }
   */

   /// <summary>
   /// Returns a resultset of role data related to the specified RoleID.
   /// </summary>
   /// <param name="RoleID" type="int">
   /// The RoleID to filter to.
   /// </param>
   public function GetByRoleID($RoleID) {
      return $this->GetWhere(array('RoleID' => $RoleID))->FirstRow();
   }
   
   /// <summary>
   /// Returns a resultset of role data related to the specified UserID.
   /// </summary>
   /// <param name="UserID" type="int">
   /// The UserID to filter to.
   /// </param>
   public function GetByUserID($UserID) {
      return $this->SQL->Select()
         ->From('Role')
         ->Join('UserRole', 'Role.RoleID = UserRole.RoleID')
         ->Where('UserRole.UserID', $UserID)
         ->Get();
   }

   /// <summary>
   /// Returns a resultset of role data NOT related to the specified RoleID.
   /// </summary>
   /// <param name="RoleID" type="int">
   /// The RoleID to filter out.
   /// </param>
   public function GetByNotRoleID($RoleID) {
      return $this->GetWhere(array('RoleID <>' => $RoleID));
   }
   
   public function GetPermissions($RoleID) {
      $PermissionModel = Gdn::PermissionModel();
      $LimitToSuffix = $this->CanSession == '1' ? '' : 'View';
      
      $Result = $PermissionModel->GetPermissions($RoleID, $LimitToSuffix);
      return $Result;      
   }
   
   //public function GetJunctionPermissionsForRole($RoleID) {
   //   $Data = $this->SQL->Select('JunctionID, PermissionID')
   //      ->From('RolePermission')
   //      ->Where('RoleID', $RoleID)
   //      ->Where('JunctionID >', 0)
   //      ->Get();
   //      
   //   $JunctionPermissions = array();         
   //   foreach ($Data->Result() as $JP) {
   //      $JunctionPermissions[] = $JP->JunctionID . '-' . $JP->PermissionID;
   //   }
   //   return $JunctionPermissions;
   //}
   
   /// <summary>
   /// Returns the number of users assigned to the provided RoleID. If
   /// $UsersOnlyWithThisRole is TRUE, it will return the number of users who
   /// are assigned to this RoleID and NO OTHER.
   /// </summary>
   /// <param name="RoleID" type="int">
   /// The RoleID to filter to.
   /// </param>
   /// <param name="UsersOnlyWithThisRole" type="bool" required="false" default="FALSE">
   /// A boolean value indicating if the count should be any users with this
   /// RoleID, or users who are ONLY assigned to this RoleID.
   /// </param>
   public function GetUserCount($RoleID, $UsersOnlyWithThisRole = FALSE) {
      if ($UsersOnlyWithThisRole) {
         return $this->SQL->Select('UserRole.UserID', 'count', 'UserCount')
            ->From('UserRole')
            ->Join('vw_SingleRoleUser', 'UserRole.UserID = vw_SingleRoleUser.UserID')
            ->Where('UserRole.RoleID', $RoleID)
            ->Get()
            ->FirstRow()
            ->UserCount;
      } else {
         return $this->SQL->GetCount('UserRole', array('RoleID' => $RoleID));
      }
   }
   
   /// <summary>
   /// Retrieves all roles with the specified permission(s).
   /// </summary>
   /// <param name="Permission" type="mixed">
   /// A permission (or array of permissions) to match.
   /// </param>
   public function GetByPermission($Permission) {
      if (!is_array($Permission))
         $Permission = array($Permission);
         
      $this->SQL->Select('Role.*')
         ->From('Role')
         ->Join('RolePermission', "Role.RoleID = RolePermission.RoleID")
         ->Join('Permission per', "RolePermission.PermissionID = per.PermissionID");
      $PermissionCount = count($Permission);
      for ($i = 0; $i < $PermissionCount; ++$i) {
         $this->SQL->OrWhere('per.Name', $Permission[$i]);
      }
      return $this->SQL->Get();
   }
   
   public function Save($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();

      $RoleID = ArrayValue('RoleID', $FormPostValues);
      $Insert = $RoleID > 0 ? FALSE : TRUE;
      if ($Insert) {
         // Figure out the next role ID which is the next biggest power of two.
         $MaxRoleID = $this->SQL->Select('r.RoleID', 'MAX')->From('Role r')->Value('RoleID', 0);
         $RoleID = pow(2, ceil(log($MaxRoleID + 1, 2)));
         
         $this->AddInsertFields($FormPostValues);
         $FormPostValues['RoleID'] = $RoleID;
      } else {
         $this->AddUpdateFields($FormPostValues);
      }
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         $this->Validation->AddValidationField('Permission', $FormPostValues);
         $Fields = $this->Validation->ValidationFields();
         $Permissions = ArrayValue('Permission', $Fields);
         $Fields = $this->Validation->SchemaValidationFields();
         $Fields = RemoveKeyFromArray($Fields, 'RoleID');

         if ($Insert === FALSE) {
            // Don't update the primary key
            $this->Update($Fields, array('RoleID' => $RoleID));
         } else {
            $RoleID = $this->Insert($Fields);
         }
         // Now update the role permissions
         $Role = $this->GetByRoleID($RoleID);
         
         $PermissionModel = Gdn::PermissionModel();
         $Permissions = $PermissionModel->UnpivotPermissions($Permissions, $RoleID);
         $PermissionModel->SaveAll($Permissions);
         
         $this->SavePermissions($RoleID, $PermissionIDs, $JunctionPermissionIDs);
      } else {
         $RoleID = FALSE;
      }
      return $RoleID;
   }   
   
   public function SavePermissions($RoleID, $PermissionIDs, $JunctionPermissionIDs = '') {
      if (!is_array($PermissionIDs))
         $PermissionIDs = array();

      // 1. Remove old role associations for this role
      $this->SQL->Delete('RolePermission', array('RoleID' => $RoleID));
      
      // 2. Insert the new permissions for this role.
      $Count = count($PermissionIDs);
      for ($i = 0; $i < $Count; $i++) {
         $this->SQL->Insert('RolePermission', array('RoleID' => $RoleID, 'PermissionID' => $PermissionIDs[$i]));
      }
      
      // 3. Insert junction permissions if they were present
      if (is_array($JunctionPermissionIDs)) {
         $Count = count($JunctionPermissionIDs);
         for ($i = 0; $i < $Count; $i++) {
            $Parts = explode('-', $JunctionPermissionIDs[$i]);
            if (count($Parts) == 2) {
               $PermissionID = array_pop($Parts);
               $JunctionID = $Parts[0];
               $this->SQL->Insert('RolePermission', array('RoleID' => $RoleID, 'PermissionID' => $PermissionID, 'JunctionID' => $JunctionID));
            }
         }
      }

      // 4. Remove the cached permissions for all users with this role.
      $this->SQL->Update('User')
         ->Join('UserRole', 'User.UserID = UserRole.UserID')
         ->Set('User.Permissions', '')
         ->Where(array('UserRole.RoleID' => $RoleID))
         ->Put();      
   }
   
   public function Delete($RoleID, $ReplacementRoleID) {
      // First update users that will be orphaned
      if (is_numeric($ReplacementRoleID) && $ReplacementRoleID > 0) {
         $this->SQL->Update('UserRole')
            ->Join('vw_SingleRoleUser', 'UserRole.UserID = vw_SingleRoleUser.UserID')
            ->Set('UserRole.RoleID', $ReplacementRoleID)
            ->Where(array('UserRole.RoleID' => $RoleID))
            ->Put();
      }
      
      // Remove old role associations for this role
      $this->SQL->Delete('RolePermission', array('RoleID' => $RoleID));
      
      // Remove the cached permissions for all users with this role.
      $this->SQL->Update('User')
         ->Join('UserRole', 'User.UserID = UserRole.UserID')
         ->Set('User.Permissions', '')
         ->Where(array('UserRole.RoleID' => $RoleID))
         ->Put();
      
      // Remove the role
      $this->SQL->Delete('Role', array('RoleID' => $RoleID));
   }
}
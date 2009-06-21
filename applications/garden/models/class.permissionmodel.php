<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class PermissionModel extends Model {
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Permission');
   }

   /**
    * Return the permissions of a user.
    *
    * @param int $UserID
    * @return array
    */
   public function GetUserPermissions($UserID, $LimitToSuffix = '') {
      $this->SQL->Select('p.PermissionID')
         ->From('Permission p')
         ->Join('RolePermission rp', "p.PermissionID = rp.PermissionID")
         ->Join('UserRole ur', 'rp.RoleID = ur.RoleID')
         ->Where('ur.UserID', $UserID)
         ->Where('p.Name', 'Garden.SignIn.Allow');
         
      if ($LimitToSuffix != '')
         $this->SQL->Like('p.Name', $LimitToSuffix, 'right');
         
      $DataSet = $this->SQL->Get();
      return $DataSet->Result();
   }
   
   /**
    * Returns a complete list of all enabled applications & plugins. This list
    * can act as a namespace list for permissions.
    * @return array
    */
   public function GetAllowedPermissionNamespaces() {
      $ApplicationManager = new ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();
      $PluginManager = Gdn::Factory('PluginManager');
      return array_merge(array_keys($EnabledApplications), array_keys($PluginManager->EnabledPlugins));      
   }
   
   /**
    * Returns all defined permissions relating to the specified $JunctionTable.
    * Excludes permissions related to applications & plugins that are disabled.
    *
    * @param string $JunctionTable The name of the junction table for which to
    * retrieve permissions.
    * @param string $LimitToSuffix An optional suffix to limit the permission names to.
    * @return DataSet
    */
   public function GetJunctionPermissions($JunctionTable, $LimitToSuffix = '') {
      $Namespaces = $this->GetAllowedPermissionNamespaces();
      $NamespaceCount = count($Namespaces);

      // Load all junction permissions
      $this->SQL
         ->Select('PermissionID, Name')
         ->From('Permission')
         ->Where('JunctionTable', $JunctionTable);
      
      if ($LimitToSuffix != '')
         $this->SQL->Like('Name', $LimitToSuffix, 'left');
         
      $this->SQL->BeginWhereGroup();
      for ($i = 0; $i < $NamespaceCount; ++$i) {
         if ($i == 0)
            $this->SQL->Like('Name', $Namespaces[$i], 'right');
         else
            $this->SQL->OrLike('Name', $Namespaces[$i], 'right');
      }
      
      return $this->SQL
         ->OrderBy('JunctionTable', 'asc')
         ->OrderBy('Name', 'asc')
         ->Get();
   }
   
   /**
    * Returns all defined permissions not related to junction tables. Excludes
    * permissions related to applications & plugins that are disabled.
    *
    * @param string $LimitToSuffix An optional suffix to limit the permission names to.
    * @return DataSet
    */
   public function GetPermissions($LimitToSuffix = '') {
      $Namespaces = $this->GetAllowedPermissionNamespaces();
      $NamespaceCount = count($Namespaces);
      
      // Load all non-junction permissions
      $this->SQL
         ->Select('PermissionID,Name')
         ->From('Permission')
         ->Where('JunctionTable is null');
         
      if ($LimitToSuffix != '')
         $this->SQL->Like('Name', $LimitToSuffix, 'left');
         
      $this->SQL->BeginWhereGroup();
      for ($i = 0; $i < $NamespaceCount; ++$i) {
         if ($i == 0)
            $this->SQL->Like('Name', $Namespaces[$i], 'right');
         else
            $this->SQL->OrLike('Name', $Namespaces[$i], 'right');
      }
      
      return $this->SQL
         ->OrderBy('Name', 'asc')
         ->Get();
   }
   
   /**
    * Returns an associative array of RoleID => JunctionPermissionData
    *
    * @param string $JunctionTable The name of the table for which permissions
    * should be returned.
    * @return array
    */
   public function GetAvailableRolePermissionsForJunction($JunctionTable) {
      $RoleModel = new RoleModel();
      $RoleData = $RoleModel->Get(); // $RoleModel->GetEditablePermissions();
      $RoleIDs = ConsolidateArrayValuesByKey($RoleData->ResultArray(), 'RoleID');
      $RoleNames = ConsolidateArrayValuesByKey($RoleData->ResultArray(), 'Name');
      $this->RoleArray = array_combine($RoleIDs, $RoleNames);

      // Define all of the roles/permissions
      $RolePermissions = array();
      foreach ($RoleData->Result() as $Role) {
         // Load all available junction permissions
         $RolePermissions[$Role->RoleID] = $this->GetJunctionPermissions($JunctionTable);
      }
      return $RolePermissions;
   }
   
   /**
    * Returns an array of RoleID-PermissionID values representing the currently
    * selected permissions for the specified junction.
    *
    * @param int $JunctionID The primary key of the junction to select.
    * @return array
    */
   public function GetSelectedRolePermissionsForJunction($JunctionID) {
      $Data = $this->SQL->Select('RoleID, PermissionID')
         ->From('RolePermission')
         ->Where('JunctionID', $JunctionID)
         ->Get();
         
      $RolePermissions = array();         
      foreach ($Data->Result() as $RP) {
         $RolePermissions[] = $RP->RoleID . '-' . $RP->PermissionID;
      }
      return $RolePermissions;
   }
   

   
   /**
    * Returns all rows from the specified JunctionTable/Column combination. This
    * method assumes that $JuntionTable has a "Name" column.
    *
    * @param string $JunctionTable The name of the table from which to retrieve data.
    * @param string $JunctionColumn The name of the column that represents the JunctionID in $JunctionTable.
    * @return DataSet
    */
   public function GetJunctionData($JunctionTable, $JunctionColumn) {
      return $this->SQL
         ->Select($JunctionColumn, '', 'JunctionID')
         ->Select('Name')
         ->From($JunctionTable)
         ->OrderBy('Name', 'asc')
         ->Get();      
   }
   
   /**
    * Return a dataset of all available junction tables (as defined in
    * Permission.JunctionTable).
    *
    * @return DataSet
    */
   public function GetJunctionTables() {
      return $this->SQL
         ->Select('JunctionTable, JunctionColumn')
         ->From('Permission')
         ->Where('JunctionTable is not null')
         ->GroupBy('JunctionTable, JunctionColumn')
         ->Get();
   }

   /**
    * Allows the insertion of new permissions. If the permission(s) already
    * exist in the database, or is not formatted properly, it will be skipped.
    *
    * @param mixed $Permission The permission (or array of permissions) to be added.
    * @param string $JunctionTable The junction table to relate the permission(s) to.
    * @param string $JunctionColumn The junction column to relate the permission(s) to.
    */
   public function InsertNew($Permission, $JunctionTable = '', $JunctionColumn = '') {
      if (!is_array($Permission))
         $Permission = array($Permission);

      $PermissionCount = count($Permission);
      // Validate the permissions first
      if (ValidatePermissionFormat($Permission)) {
         // Now save them
         $this->DefineSchema();
         for ($i = 0; $i < $PermissionCount; ++$i) {
            // Check to see if the permission already exists
            $ResultSet = $this->GetWhere(array('Name' => $Permission[$i]));
            // If not, insert it now
            if ($ResultSet->NumRows() == 0) {
               $Values = array();
               $Values['Name'] = $Permission[$i];
               if ($JunctionTable != '') {
                  $Values['JunctionTable'] = $JunctionTable;
                  $Values['JunctionColumn'] = $JunctionColumn;
               }
               $this->Insert($Values);
            }
         }
      }
   }
   
   /**
    * Saves Permissions for the specified JunctionID.
    *
    * @param array $FormPostValues The values posted in a form. This should contain an array 'RolePermissionID' checkbox values.
    * @param int $JunctionID The JunctionID to associate the form-posted permissions with.
    */
   public function SaveJunctionPermissions($FormPostValues, $JunctionID) {
      // Remove old permissions for this JunctionID
      $this->SQL->Delete('RolePermission', array('JunctionID' => $JunctionID));

      $RolePermissionIDs = ArrayValue('RolePermissionID', $FormPostValues);
      if (is_array($RolePermissionIDs)) {
         $Count = count($RolePermissionIDs);
         for ($i = 0; $i < $Count; $i++) {
            $Parts = explode('-', $RolePermissionIDs[$i]);
            if (count($Parts) == 2) {
               $PermissionID = array_pop($Parts);
               $RoleID = $Parts[0];
               // Insert new ones
               $this->SQL->Insert('RolePermission', array('RoleID' => $RoleID, 'PermissionID' => $PermissionID, 'JunctionID' => $JunctionID));
            }
         }
      }
      
      // Remove the cached permissions for all users.
      $this->SQL->Update('User')
         ->Set('Permissions', '')
         ->Put();        
   }
}
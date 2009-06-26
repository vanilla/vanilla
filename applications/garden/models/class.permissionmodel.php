<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class Gdn_PermissionModel extends Model {
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Permission');
   }
   
   public function Define($PermissionNames, $Type = 'tinyint', $JunctionTable = NULL, $JunctionColumn = NULL) {
      if(!is_array($PermissionNames))
         $PermissionNames = array($PermissionNames);
      
      
      $St = $this->Database->Structure();
      $St->Table('Permission');
      
      foreach($PermissionNames as $PermissionName) {
         // Define the column.
         $St->Column($PermissionName, $Type, '', FALSE, 0);
      }
      $St->Set(FALSE, FALSE);
      
      // Set the default permissions so we know how to administer them.
      $DefaultPermissions = array_fill_keys($PermissionNames, 2);
      $this->SQL->Replace('Permission', $DefaultPermissions, array('RoleID' => 0, 'JunctionTable' => $JunctionTable, 'JunctionColumn' => $JunctionColumn));
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
      $ApplicationManager = new Gdn_ApplicationManager();
      $EnabledApplications = $ApplicationManager->EnabledApplications();
      $PluginManager = Gdn::Factory('PluginManager');
      return array_merge(array_keys($EnabledApplications), array_keys($PluginManager->EnabledPlugins));      
   }
   
   /**
    */
   public function GetJunctionPermissions($RoleID, $JunctionTable = NULL, $LimitToSuffix = '') {
      $Namespaces = $this->GetAllowedPermissionNamespaces();
      $SQL = $this->SQL;
      
      // Load all of the default junction permissions.
      $SQL->Select('*')
         ->From('Permission p')
         ->Where('p.RoleID', 0);
         
      if(is_null($JunctionTable)) {
         $SQL->Where('p.JunctionTable is not null');
      } else {
         $SQL->Where('p.JunctionTable', $JunctionTable);
      }
      
      $Data = $SQL->Get()->ResultArray();
      $Result = array();
      foreach($Data as $Row) {
         $JunctionTable = $Row['JunctionTable'];
         $JunctionColumn = $Row['JunctionColumn'];
         unset($Row['PermissionID'], $Row['RoleID'], $Row['JunctionTable'], $Row['JunctionColumn'], $Row['JunctionID']);
         
         $SQL->Select('junc.Name');
         $SQL->Select('junc.'.$JunctionColumn, '', 'JunctionID');
         // Figure out which columns to select.
         foreach($Row as $PermissionName => $Value) {
            if($Value != 2)
               continue; // permission not applicable to this junction table
            if(!empty($LimitToSuffix) && substr($PermissionName, -strlen($LimitToSuffix)) != $LimitToSuffix)
               continue; // permission not in $LimitToSuffix
            if($index = strpos($PermissionName, '.')) {
               if(!in_array(substr($PermissionName, 0, $index), $Namespaces))
                  continue; // permission not in allowed namespaces
            }
            
            $SQL->Select('p.`'.$PermissionName.'`, 0', 'coalesce', $PermissionName);
         }
         // Get the permissions for the junction table.
         $JuncData = $SQL->From($JunctionTable.' junc')
            ->Join('Permission p', 'p.JunctionID = junc.'.$JunctionColumn.' and p.RoleID = '.$RoleID, 'left')
            ->OrderBy('junc.Name')->Get()->ResultArray();
            
         // Add all of the necessary information back to the result.
         foreach($JuncData as $JuncRow) {
            $JuncRow['JunctionTable'] = $JunctionTable;
            $JuncRow['JunctionColumn'] = $JunctionColumn;
            
            $Result[] = $JuncRow;
         }
      }
      return $Result;
   }
   
   /**
    * Returns all defined permissions not related to junction tables. Excludes
    * permissions related to applications & plugins that are disabled.
    *
    * @param string $LimitToSuffix An optional suffix to limit the permission names to.
    * @return DataSet
    */
   public function GetPermissions($RoleID, $LimitToSuffix = '') {
      $Namespaces = $this->GetAllowedPermissionNamespaces();
      $NamespaceCount = count($Namespaces);
      
      $Result = array();
      
      $GlobalPermissions = $this->GetGlobalPermissions($RoleID, $LimitToSuffix);
      $Result[] = $GlobalPermissions;
      
      $JunctionPermissions = $this->GetJunctionPermissions($RoleID, NULL, $LimitToSuffix);
      $Result = array_merge($Result, $JunctionPermissions);
      
      return $Result;
   }
   
   public function GetPermissionsEdit($RoleID, $LimitToSuffix = '') {
      $Permissions = $this->GetPermissions($RoleID, $LimitToSuffix);
      return $this->UnpivotPermissions($Permissions);
   }
   
   public function GetGlobalPermissions($RoleID, $LimitToSuffix = '') {
      $Namespaces = $this->GetAllowedPermissionNamespaces();
      
      $Data = $this->SQL
         ->Select('*')
         ->From('Permission p')
         ->WhereIn('p.RoleID', array($RoleID, 0))
         ->Where('p.JunctionTable is null')
         ->OrderBy('p.RoleID')
         ->Get()->ResultArray();
         
      $DefaultRow = $Data[0];
      unset($DefaultRow['RoleID'], $DefaultRow['JunctionTable'], $DefaultRow['JunctionColumn'], $DefaultRow['JunctionID']);
      if(count($Data) >= 2) {
         $GlobalPermissions = $Data[1];   
         unset($GlobalPermissions['RoleID'], $GlobalPermissions['JunctionTable'], $GlobalPermissions['JunctionColumn'], $GlobalPermissions['JunctionID']);
      } else {
         $GlobalPermissions = $DefaultRow;
         // Set all of the default permissions to false.
         foreach($DefaultRow as $PermissionName => $Value) {
            $GlobalPermissions[$PermissionName] = 0;
         }
      }
      
      // Remove all of the permissions that don't apply globally.
      foreach($DefaultRow as $PermissionName => $Value) {
         if($Value != 2)
            unset($GlobalPermissions[$PermissionName]); // permission not applicable
         if(!empty($LimitToSuffix) && substr($PermissionName, -strlen($LimitToSuffix)) != $LimitToSuffix)
            unset($GlobalPermissions[$PermissionName]); // permission not in $LimitToSuffix
         if($index = strpos($PermissionName, '.')) {
            if(!in_array(substr($PermissionName, 0, $index), $Namespaces))
               unset($GlobalPermissions[$PermissionName]);; // permission not in allowed namespaces
         }
      }
      
      return $GlobalPermissions;
   }
   
   /**
    * Returns an associative array of RoleID => JunctionPermissionData
    *
    * @param string $JunctionTable The name of the table for which permissions
    * should be returned.
    * @return array
    */
   /*public function GetAvailableRolePermissionsForJunction($JunctionTable) {
      $RoleModel = new Gdn_RoleModel();
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
   }*/
   
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
   
   public static function PermissionNamespace($PermissionName) {
      if($Index = strpos($PermissionName))
         return substr($PermissionName, 0, $Index);
      return '';
   }
   
   /**
    * Returns all rows from the specified JunctionTable/Column combination. This
    * method assumes that $JuntionTable has a "Name" column.
    *
    * @param string $JunctionTable The name of the table from which to retrieve data.
    * @param string $JunctionColumn The name of the column that represents the JunctionID in $JunctionTable.
    * @return DataSet
    */
   /*public function GetJunctionData($JunctionTable, $JunctionColumn) {
      return $this->SQL
         ->Select($JunctionColumn, '', 'JunctionID')
         ->Select('Name')
         ->From($JunctionTable)
         ->OrderBy('Name', 'asc')
         ->Get();      
   }*/
   
   /**
    * Return a dataset of all available junction tables (as defined in
    * Permission.JunctionTable).
    *
    * @return DataSet
    */
  /* public function GetJunctionTables() {
      return $this->SQL
         ->Select('JunctionTable, JunctionColumn')
         ->From('Permission')
         ->Where('JunctionTable is not null')
         ->GroupBy('JunctionTable, JunctionColumn')
         ->Get();
   }*/

   /**
    * Allows the insertion of new permissions. If the permission(s) already
    * exist in the database, or is not formatted properly, it will be skipped.
    *
    * @param mixed $Permission The permission (or array of permissions) to be added.
    * @param string $JunctionTable The junction table to relate the permission(s) to.
    * @param string $JunctionColumn The junction column to relate the permission(s) to.
    */
  /* public function InsertNew($Permission, $JunctionTable = '', $JunctionColumn = '') {
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
   }*/
   
   /**
    * Saves a permission row.
    *
    * 
    *
    */
   public function Save($Values) {      
      // Figure out how to find the existing permission.
      if(array_key_exists('PermissionID', $Values)) {
         $Where = array('PermissionID', $Values['PermissionID']);
         unset($Values['PermissionID']);
         
         $this->SQL->Update('Permission', $Values, $Where);
      } else {
         $Where = array();
         
         if(array_key_exists('RoleID', $Values)) {
            $Where['RoleID'] = $Values['RoleID'];
            unset($Values['RoleID']);
         } else {
            $Where['RoleID'] = 0; // default role.
         }
         
         if(array_key_exists('JunctionTable', $Values)) {
            $Where['JunctionTable'] = $Values['JunctionTable'];

            // If the junction table was given then so must the other values.
            $Where['JunctionColumn'] = $Values['JunctionColumn'];
            $Where['JunctionID'] = $Values['JunctionID'];
            
            unset($Values['JunctionTable'], $Values['JunctionColumn'], $Values['JunctionID']);
         } else {
            $Where['JunctionTable'] = NULL; // no junction table.
            $Where['JunctionColumn'] = NULL;
            $Where['JunctionID'] = NULL;
         }
         
         $this->SQL->Replace('Permission', $Values, $Where);
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
   
   /**
    * Split a permission name into its constituant parts.
    *
    * @param string $PermissionName The name of the permission.
    * @return array The split permission in the form array(Namespace, Permission,Suffix).
    */
   public static function SplitPermission($PermissionName) {
      $i = strpos($PermissionName, '.');
      $j = strrpos($PermissionName, '.');
      
      if($i !== FALSE) { // $j must also not be false
         return array(substr($PermissionName, 0, $i), substr($PermissionName, $i + 1, $j - $i - 1), substr($PermissionName, $j + 1));
      } else {
         return array($PermissionName, '', '');
      }
   }
   
   public function UnpivotPermissions($Permissions) {
      $Result = array();
      foreach($Permissions as $Row) {
         $this->_UnpivotPermissionsRow($Row, $Result);
      }
      return $Result;
   }
   
   protected function _UnpivotPermissionsRow($Row, &$Result, $InclueRoleID = FALSE) {
      $GlobalName = ArrayValue('Name', $Row);
      
      // Loop through each permission in the row and place them in the correct place in the grid.
      foreach($Row as $PermissionName => $Value) {
         list($Namespace, $Name, $Suffix) = self::SplitPermission($PermissionName);
         if(empty($Name))
            continue; // was some other column
         
         if($GlobalName) $Namespace = $GlobalName;
            
         // Check to see if the namespace is in the result.
         if(!array_key_exists($Namespace, $Result))
            $Result[$Namespace] = array('_Columns' => array(), '_Rows' => array());
         $NamespaceArray = &$Result[$Namespace];
         
         // Add the names to the columns and rows.
         $NamespaceArray['_Columns'][$Suffix] = TRUE;
         $NamespaceArray['_Rows'][$Name] = TRUE;
         
         // Augment the value depending on the junction ID.
         if(array_key_exists('JunctionTable', $Row) && ($JunctionTable = $Row['JunctionTable'])) {
            $PostName = $JunctionTable.'-'.$Row['JunctionID'].($InclueRoleID ? '-'.$Row['RoleID'] : '');
         } else {
            $PostName = 'Permission'.($InclueRoleID ? '-'.$Row['RoleID'] : '');
         }
         
         $NamespaceArray[$Name.'.'.$Suffix] = array('Value' => $Value, 'PostName' => $PostName, 'PostValue' => $PermissionName);
      }
   }
}
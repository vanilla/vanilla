<?php
/**
 * Permission model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles permission data.
 */
class PermissionModel extends Gdn_Model {

    /** @var array Default role permissions. */
    protected $DefaultPermissions = array();

    /** @var array Default row permission values. */
    protected $RowDefaults = array();

    /** @var array Permission columns. */
    protected $_PermissionColumns = array();

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Permission');
    }

    /**
     *
     *
     * @param $Values
     * @return array
     */
    protected function _Backtick($Values) {
        $NewValues = array();
        foreach ($Values as $Key => $Value) {
            $NewValues['`'.$Key.'`'] = $Value;
        }
        return $NewValues;
    }

    /**
     * Add an entry into the list of default permissions.
     *
     * @param string $Type Type of role the permissions should be added for.
     * @param array $Permissions The list of permissions to include.
     * @param null|string $Junction Type of junction to base the permission on.
     * @param null|int $JunctionId Identifier for the specific junction record to base the permission on.
     */
    public function AddDefault($Type, $Permissions, $Junction = null, $JunctionId = null) {
        if (!array_key_exists($Type, $this->DefaultPermissions)) {
            $this->DefaultPermissions[$Type] = array('global' => array());
        }

        if ($Junction && $JunctionId) {
            $JunctionKey = "$Junction:$JunctionId";
            if (!array_key_exists($JunctionKey, $this->DefaultPermissions[$Type])) {
                $this->DefaultPermissions[$Type][$JunctionKey] = array();
            }
            $Defaults =& $this->DefaultPermissions[$Type][$JunctionKey];
        } else {
            $Defaults =& $this->DefaultPermissions[$Type]['global'];
        }

        $Defaults = array_merge($Defaults, $Permissions);
    }

    /**
     * Populate a list of default permissions, per type.
     *
     * @param bool $ResetDefaults If we already have defaults, should they be discarded?
     */
    public function AssignDefaults($ResetDefaults = FALSE) {
        if (count($this->DefaultPermissions)) {
            if ($ResetDefaults) {
                $this->DefaultPermissions = array();
            } else {
                return;
            }
        }

        $this->AddDefault(
            RoleModel::TYPE_GUEST,
            array(
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
            )
        );
        $this->AddDefault(
            RoleModel::TYPE_UNCONFIRMED,
            $Permissions = array(
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Email.View' => 1
            )
        );
        $this->AddDefault(
            RoleModel::TYPE_APPLICANT,
            $Permissions = array(
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Email.View' => 1
            )
        );
        $this->AddDefault(
            RoleModel::TYPE_MODERATOR,
            $Permissions = array(
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Curation.Manage' => 1,
                'Garden.Moderation.Manage' => 1,
                'Garden.PersonalInfo.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Profiles.Edit' => 1,
                'Garden.Email.View' => 1
            )
        );
        $this->AddDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            array(
                'Garden.SignIn.Allow' => 1,
                'Garden.Settings.View' => 1,
                'Garden.Settings.Manage' => 1,
                'Garden.Community.Manage' => 1,
                'Garden.Users.Add' => 1,
                'Garden.Users.Edit' => 1,
                'Garden.Users.Delete' => 1,
                'Garden.Users.Approve' => 1,
                'Garden.Activity.Delete' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Messages.Manage' => 1,
                'Garden.PersonalInfo.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Profiles.Edit' => 1,
                'Garden.AdvancedNotifications.Allow' => 1,
                'Garden.Email.View' => 1,
                'Garden.Curation.Manage' => 1,
                'Garden.Moderation.Manage' => 1
            )
        );
        $this->AddDefault(
            RoleModel::TYPE_MEMBER,
            array(
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Profiles.Edit' => 1,
                'Garden.Email.View' => 1
            )
        );

        // Allow the ability for other applications and plug-ins to speakup with their own default permissions.
        $this->FireEvent('DefaultPermissions');
    }

    /**
     *
     */
    public function ClearPermissions() {
        static $PermissionsCleared = FALSE;

        if (!$PermissionsCleared) {
            // Remove the cached permissions for all users.
            Gdn::UserModel()->ClearPermissions();
            $PermissionsCleared = TRUE;
        }
    }

    /**
     *
     *
     * @param $PermissionNames
     * @param string $Type
     * @param null $JunctionTable
     * @param null $JunctionColumn
     * @throws Exception
     */
    public function Define($PermissionNames, $Type = 'tinyint', $JunctionTable = NULL, $JunctionColumn = NULL) {
        $PermissionNames = (array)$PermissionNames;

        $Structure = $this->Database->Structure();
        $Structure->Table('Permission');
        $DefaultPermissions = array();

        $NewColumns = array();

        foreach ($PermissionNames as $Key => $Value) {
            if (is_numeric($Key)) {
                $PermissionName = $Value;
                $DefaultPermissions[$PermissionName] = 2;
            } else {
                $PermissionName = $Key;

                if ($Value === 0)
                    $DefaultPermissions[$PermissionName] = 2;
                elseif ($Value === 1)
                    $DefaultPermissions[$PermissionName] = 3;
                elseif (!$Structure->ColumnExists($Value) && array_key_exists($Value, $PermissionNames))
                    $DefaultPermissions[$PermissionName] = $PermissionNames[$Value] ? 3 : 2;
                else
                    $DefaultPermissions[$PermissionName] = "`{$Value}`"; // default to another field
            }
            if (!$Structure->ColumnExists($PermissionName)) {
                $NewColumns[$PermissionName] = is_numeric($DefaultPermissions[$PermissionName]) ? $DefaultPermissions[$PermissionName] - 2 : $DefaultPermissions[$PermissionName];
            }

            // Define the column.
            $Structure->Column($PermissionName, $Type, 0);

        }
        $Structure->Set(FALSE, FALSE);

        // Set the default permissions on the placeholder.
        $this->SQL
            ->Set($this->_Backtick($DefaultPermissions), '', FALSE)
            ->Replace('Permission', array(), array('RoleID' => 0, 'JunctionTable' => $JunctionTable, 'JunctionColumn' => $JunctionColumn), TRUE);

        // Set the default permissions for new columns on all roles.
        if (count($NewColumns) > 0) {
            $Where = array('RoleID <>' => 0);
            if (!$JunctionTable)
                $Where['JunctionTable'] = NULL;
            else
                $Where['JunctionTable'] = $JunctionTable;

            $this->SQL
                ->Set($this->_Backtick($NewColumns), '', FALSE)
                ->Put('Permission', array(), $Where);
        }
        $this->ClearPermissions();
        if ($this->Schema) {
            // Redefine the schema if it has been defined to reflect the permissions that were just added.
            $this->Schema = NULL;
            $this->DefineSchema();
        }
    }

    /**
     *
     *
     * @param null $RoleID
     * @param null $JunctionTable
     * @param null $JunctionColumn
     * @param null $JunctionID
     */
    public function Delete($RoleID = NULL, $JunctionTable = NULL, $JunctionColumn = NULL, $JunctionID = NULL) {
        // Build the where clause.
        $Where = array();
        if (!is_null($RoleID)) {
            $Where['RoleID'] = $RoleID;
        }
        if (!is_null($JunctionTable)) {
            $Where['JunctionTable'] = $JunctionTable;
            $Where['JunctionColumn'] = $JunctionColumn;
            $Where['JunctionID'] = $JunctionID;
        }

        $this->SQL->Delete('Permission', $Where);

        if (!is_null($RoleID)) {
            // Rebuild the permission cache.
        }
    }

    /**
     * Grab the list of default permissions by role type
     *
     * @return array List of permissions, grouped by role type
     */
    public function GetDefaults() {
        if (empty($this->DefaultPermissions)) {
            $this->AssignDefaults();
        }

        return $this->DefaultPermissions;
    }

    /**
     * Grab default permission column values.
     *
     * @throws Exception Throws when no default permission row can be found in the database.
     * @return array A list of default permission values.
     */
    public function GetRowDefaults() {
        if (empty($this->RowDefaults)) {
            $DefaultRow = $this->SQL
                ->Select('*')
                ->From('Permission')
                ->Where('RoleID', 0)
                ->Where('JunctionTable is null')
                ->OrderBy('RoleID')
                ->Limit(1)
                ->Get()->FirstRow(DATASET_TYPE_ARRAY);

            if (!$DefaultRow) {
                throw new Exception(T('No default permission row.'));
            }

            $this->_MergeDisabledPermissions($DefaultRow);

            unset(
                $DefaultRow['PermissionID'],
                $DefaultRow['RoleID'],
                $DefaultRow['JunctionTable'],
                $DefaultRow['JunctionColumn'],
                $DefaultRow['JunctionID']
            );

            $this->RowDefaults = $this->StripPermissions($DefaultRow, $DefaultRow);
        }

        return $this->RowDefaults;
    }

    /**
     * Get the permissions of a user.
     *
     * If no junction table is specified, will return ONLY non-junction permissions.
     * If you need every permission regardless of junction & suffix, see CachePermissions.
     *
     * @param int $UserID Unique identifier for user.
     * @param string $LimitToSuffix String permission name must match, starting on right (ex: 'View' would match *.*.View)
     * @param string $JunctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string $JunctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $JunctionTable.
     * @param string $ForeignKey Foreign table column to join on.
     * @param int $ForeignID Foreign ID to limit join to.
     * @return array Permission records.
     */
    public function GetUserPermissions($UserID, $LimitToSuffix = '', $JunctionTable = FALSE, $JunctionColumn = FALSE, $ForeignKey = FALSE, $ForeignID = FALSE) {
        // Get all permissions
        $PermissionColumns = $this->PermissionColumns($JunctionTable, $JunctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($PermissionColumns as $ColumnName => $Value) {
            if (!empty($LimitToSuffix) && substr($ColumnName, -strlen($LimitToSuffix)) != $LimitToSuffix)
                continue; // permission not in $LimitToSuffix
            $this->SQL->Select('p.`'.$ColumnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->From('Permission p')
            ->Join('UserRole ur', 'p.RoleID = ur.RoleID')
            ->Where('ur.UserID', $UserID);

        // Either limit to 1 junction or exclude junctions
        if ($JunctionTable && $JunctionColumn) {
            $this->SQL
                ->Select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
                ->GroupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));
            if ($ForeignKey && $ForeignID) {
                $this->SQL
                    ->Join("$JunctionTable j", "j.$JunctionColumn = p.JunctionID")
                    ->Where("j.$ForeignKey", $ForeignID);
            }
        } else {
            $this->SQL->Where('p.JunctionTable is null');
        }

        return $this->SQL->Get()->ResultArray();
    }

    /**
     * Get the permissions of a role.
     *
     * If no junction table is specified, will return ONLY non-junction permissions.
     * If you need every permission regardless of junction & suffix, see CachePermissions.
     *
     * @param int $RoleID Unique identifier for role.
     * @param string $LimitToSuffix String permission name must match, starting on right (ex: 'View' would match *.*.View)
     * @param string $JunctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string $JunctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $JunctionTable.
     * @param string $ForeignKey Foreign table column to join on.
     * @param int $ForeignID Foreign ID to limit join to.
     * @return array Permission records.
     */
    public function GetRolePermissions($RoleID, $LimitToSuffix = '', $JunctionTable = FALSE, $JunctionColumn = FALSE, $ForeignKey = FALSE, $ForeignID = FALSE) {
        // Get all permissions
        $PermissionColumns = $this->PermissionColumns($JunctionTable, $JunctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($PermissionColumns as $ColumnName => $Value) {
            if (!empty($LimitToSuffix) && substr($ColumnName, -strlen($LimitToSuffix)) != $LimitToSuffix)
                continue; // permission not in $LimitToSuffix
            $this->SQL->Select('p.`'.$ColumnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->From('Permission p')
            ->Where('p.RoleID', $RoleID);

        // Either limit to 1 junction or exclude junctions
        if ($JunctionTable && $JunctionColumn) {
            $this->SQL
                ->Select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
                ->GroupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));
            if ($ForeignKey && $ForeignID) {
                $this->SQL
                    ->Join("$JunctionTable j", "j.$JunctionColumn = p.JunctionID")
                    ->Where("j.$ForeignKey", $ForeignID);
            }
        } else {
            $this->SQL->Where('p.JunctionTable is null');
        }

        return $this->SQL->Get()->ResultArray();
    }

    /**
     * Returns a complete list of all enabled applications & plugins. This list
     * can act as a namespace list for permissions.
     *
     * @return array
     */
    public function GetAllowedPermissionNamespaces() {
        $ApplicationManager = new Gdn_ApplicationManager();
        $EnabledApplications = $ApplicationManager->EnabledApplications();

        $PluginNamespaces = array();
        foreach (Gdn::PluginManager()->EnabledPlugins() as $Plugin) {
            if (!array_key_exists('RegisterPermissions', $Plugin) || !is_array($Plugin['RegisterPermissions']))
                continue;
            foreach ($Plugin['RegisterPermissions'] as $Index => $PermissionName) {
                if (is_string($Index))
                    $PermissionName = $Index;

                $Namespace = substr($PermissionName, 0, strrpos($PermissionName, '.'));
                $PluginNamespaces[$Namespace] = TRUE;
            }
        }

        $Result = array_merge(array_keys($EnabledApplications), array_keys($PluginNamespaces));
        if (in_array('Dashboard', $Result))
            $Result[] = 'Garden';
        return $Result;
    }

    /**
     *
     *
     * @param null $UserID
     * @param null $RoleID
     * @return array|null
     */
    public function CachePermissions($UserID = NULL, $RoleID = NULL) {
        if (!$UserID) {
            $RoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_GUEST);
        }

        // Select all of the permission columns.
        $PermissionColumns = $this->PermissionColumns();
        foreach ($PermissionColumns as $ColumnName => $Value) {
            $this->SQL->Select('p.`'.$ColumnName.'`', 'MAX');
        }

        $this->SQL->From('Permission p');

        if (!is_null($RoleID))
            $this->SQL->Where('p.RoleID', $RoleID);
        elseif (!is_null($UserID))
            $this->SQL->Join('UserRole ur', 'p.RoleID = ur.RoleID')->Where('ur.UserID', $UserID);

        $this->SQL
            ->Select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
            ->GroupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));

        $Result = $this->SQL->Get()->ResultArray();
        return $Result;
    }

    /**
     *
     *
     * @param $Where
     * @param null $JunctionTable
     * @param string $LimitToSuffix
     * @param array $Options
     * @return array
     */
    public function GetJunctionPermissions($Where, $JunctionTable = NULL, $LimitToSuffix = '', $Options = array()) {
        $Namespaces = $this->GetAllowedPermissionNamespaces();
        $RoleID = ArrayValue('RoleID', $Where, NULL);
        $JunctionID = ArrayValue('JunctionID', $Where, NULL);
        $SQL = $this->SQL;

        // Load all of the default junction permissions.
        $SQL->Select('*')
            ->From('Permission p')
            ->Where('p.RoleID', 0);

        if (is_null($JunctionTable)) {
            $SQL->Where('p.JunctionTable is not null');
        } else {
            $SQL->Where('p.JunctionTable', $JunctionTable);
        }

        // Get the disabled permissions.
        $DisabledPermissions = C('Garden.Permissions.Disabled');
        if (is_array($DisabledPermissions)) {
            $DisabledWhere = array();
            foreach ($DisabledPermissions as $TableName => $Disabled) {
                if ($Disabled)
                    $DisabledWhere[] = $TableName;
            }
            if (count($DisabledWhere) > 0)
                $SQL->WhereNotIn('JunctionTable', $DisabledWhere);
        }

        $Data = $SQL->Get()->ResultArray();
        $Result = array();
        foreach ($Data as $Row) {
            $JunctionTable = $Row['JunctionTable'];
            $JunctionColumn = $Row['JunctionColumn'];
            unset($Row['PermissionID'], $Row['RoleID'], $Row['JunctionTable'], $Row['JunctionColumn'], $Row['JunctionID']);

            // If the junction column is not the primary key then we must figure out and limit the permissions.
            if ($JunctionColumn != $JunctionTable.'ID') {
                $JuncIDs = $SQL
                    ->Distinct(TRUE)
                    ->Select("p.{$JunctionTable}ID")
                    ->Select("c.$JunctionColumn")
                    ->Select('p.Name')
                    ->From("$JunctionTable c")
                    ->Join("$JunctionTable p", "c.$JunctionColumn = p.{$JunctionTable}ID", 'left')
                    ->Get()->ResultArray();

                foreach ($JuncIDs as &$JuncRow) {
                    if (!$JuncRow[$JunctionTable.'ID'])
                        $JuncRow[$JunctionTable.'ID'] = -1;
                }
            }

            // Figure out which columns to select.
            foreach ($Row as $PermissionName => $Value) {
                if (!($Value & 2))
                    continue; // permission not applicable to this junction table
                if (!empty($LimitToSuffix) && substr($PermissionName, -strlen($LimitToSuffix)) != $LimitToSuffix)
                    continue; // permission not in $LimitToSuffix
                if ($index = strpos($PermissionName, '.')) {
                    if (!in_array(substr($PermissionName, 0, $index), $Namespaces) &&
                        !in_array(substr($PermissionName, 0, strrpos($PermissionName, '.')), $Namespaces)
                    )
                        continue; // permission not in allowed namespaces
                }

                // If we are viewing the permissions by junction table (ex. Category) then set the default value when a permission row doesn't exist.
                if (!$RoleID && $JunctionColumn != $JunctionTable.'ID' && GetValue('AddDefaults', $Options))
                    $DefaultValue = $Value & 1 ? 1 : 0;
                else
                    $DefaultValue = 0;

                $SQL->Select("p.`$PermissionName`, $DefaultValue", 'coalesce', $PermissionName);
            }

            if (!empty($RoleID)) {
                $roleIDs = (array)$RoleID;
                if (count($roleIDs) === 1) {
                    $roleOn = 'p.RoleID = '.$this->SQL->Database->Connection()->quote(reset($roleIDs));
                } else {
                    $roleIDs = array_map(array($this->SQL->Database->Connection(), 'quote'), $roleIDs);
                    $roleOn = 'p.RoleID in ('.implode(',', $roleIDs).')';
                }

                // Get the permissions for the junction table.
                $SQL->Select('junc.Name')
                    ->Select('junc.'.$JunctionColumn, '', 'JunctionID')
                    ->From($JunctionTable.' junc')
                    ->Join('Permission p', "p.JunctionID = junc.$JunctionColumn and $roleOn", 'left')
                    ->OrderBy('junc.Sort')
                    ->OrderBy('junc.Name');

                if (isset($JuncIDs)) {
                    $SQL->WhereIn("junc.{$JunctionTable}ID", array_column($JuncIDs, "{$JunctionTable}ID"));
                }
            } else {
                // Here we are getting permissions for all roles.
                $SQL->Select('r.RoleID, r.Name, r.CanSession')
                    ->From('Role r')
                    ->Join('Permission p', "p.RoleID = r.RoleID and p.JunctionTable = '$JunctionTable' and p.JunctionColumn = '$JunctionColumn' and p.JunctionID = $JunctionID", 'left')
                    ->OrderBy('r.Sort, r.Name');
            }
            $JuncData = $SQL->Get()->ResultArray();

            // Add all of the necessary information back to the result.
            foreach ($JuncData as $JuncRow) {
                $JuncRow['JunctionTable'] = $JunctionTable;
                $JuncRow['JunctionColumn'] = $JunctionColumn;
                if (!is_null($JunctionID)) {
                    $JuncRow['JunctionID'] = $JunctionID;
                }
                if ($JuncRow['JunctionID'] < 0) {
                    $JuncRow['Name'] = sprintf(T('Default %s Permissions'), T('Permission.'.$JunctionTable, $JunctionTable));
                }

                if (array_key_exists('CanSession', $JuncRow)) {
                    if (!$JuncRow['CanSession']) {
                        // Remove view permissions.
                        foreach ($JuncRow as $PermissionName => $Value) {
                            if (strpos($PermissionName, '.') !== FALSE && strpos($PermissionName, '.View') === FALSE)
                                unset($JuncRow[$PermissionName]);
                        }
                    }

                    unset($JuncRow['CanSession']);
                }

                if (!$RoleID && !$JunctionID && array_key_exists(0, $Data)) {
                    // Set all of the default permissions for a new role.
                    foreach ($JuncRow as $PermissionName => $Value) {
                        if (GetValue($PermissionName, $Data[0], 0) & 1) {
                            $JuncRow[$PermissionName] = 1;
                        }
                    }
                }

                $Result[] = $JuncRow;
            }
        }
        return $Result;
    }

    /**
     * Returns all defined permissions not related to junction tables. Excludes
     * permissions related to applications & plugins that are disabled.
     *
     * @param int|array $RoleID The role(s) to get the permissions for.
     * @param string $LimitToSuffix An optional suffix to limit the permission names to.
     * @return array
     */
    public function GetPermissions($RoleID, $LimitToSuffix = '') {
        $RoleID = (array)$RoleID;
        $Result = array();

        $GlobalPermissions = $this->GetGlobalPermissions($RoleID, $LimitToSuffix);
        $Result[] = $GlobalPermissions;

        $JunctionPermissions = $this->GetJunctionPermissions(array('RoleID' => $RoleID), NULL, $LimitToSuffix);
        $Result = array_merge($Result, $JunctionPermissions);

        return $Result;
    }

    /**
     *
     *
     * @param $RoleID
     * @param string $LimitToSuffix
     * @return array
     */
    public function GetPermissionsEdit($RoleID, $LimitToSuffix = '') {
        $Permissions = $this->GetPermissions($RoleID, $LimitToSuffix);
        return $this->UnpivotPermissions($Permissions);
    }

    /**
     * Get all of the global permissions for one or more roles.
     *
     * @param int|array $RoleID The role(s) to get the permissions for.
     * @param string $LimitToSuffix Whether or not to limit the permissions to a suffix.
     * @return Returns an
     */
    public function GetGlobalPermissions($RoleID, $LimitToSuffix = '') {
        $RoleIDs = (array)$RoleID;

        // Get the global permissions.
        $Data = $this->SQL
            ->Select('*')
            ->From('Permission p')
            ->WhereIn('p.RoleID', array_merge($RoleIDs, array(0)))
            ->Where('p.JunctionTable is null')
            ->OrderBy('p.RoleID')
            ->Get()->ResultArray();

        $this->_MergeDisabledPermissions($Data);
        $Data = Gdn_DataSet::Index($Data, 'RoleID');

        $DefaultRow = $Data[0];
        unset($Data[0], $DefaultRow['RoleID'], $DefaultRow['JunctionTable'], $DefaultRow['JunctionColumn'], $DefaultRow['JunctionID']);
        $DefaultRow = $this->StripPermissions($DefaultRow, $DefaultRow, $LimitToSuffix);
        if ($RoleID) {
            // When editing a role make sure the default permissions are false so as not to be misleading.
            $DefaultRow = array_fill_keys(array_keys($DefaultRow), 0);
        }

        foreach ($RoleIDs as $ID) {
            if (isset($Data[$ID])) {
                $Data[$ID] = array_intersect_key($Data[$ID], $DefaultRow);
            } else {
                $Data[$ID] = $DefaultRow;
                $Data[$ID]['PermissionID'] = NULL;
            }
        }

        if (count($RoleIDs) === 1) {
            return array_pop($Data);
        } else {
            return $Data;
        }
    }

    /**
     * Take a permission row and strip the global/local permissions from it.
     *
     * @param $Row
     * @param $DefaultRow
     * @param string $LimitToSuffix
     * @return mixed
     */
    public function StripPermissions($Row, $DefaultRow, $LimitToSuffix = '') {
        static $Namespaces;
        if (!isset($Namespaces)) {
            $Namespaces = $this->GetAllowedPermissionNamespaces();
        }

        foreach ($DefaultRow as $PermissionName => $Value) {
            if (in_array($PermissionName, array('PermissionID', 'RoleID', 'JunctionTable', 'JunctionColumn', 'JunctionID'))) {
                continue;
            }

            if (!$this->IsGlobalPermission($Value, $PermissionName, $LimitToSuffix, $Namespaces)) {
                unset($Row[$PermissionName]);
                continue;
            }

            switch ($DefaultRow[$PermissionName]) {
                case 3:
                    $Row[$PermissionName] = 1;
                    break;
                case 2:
                    $Row[$PermissionName] = 0;
                    break;
            }
        }
        return $Row;
    }

    /**
     * Returns whether or not a permission is a global permission.
     *
     * @param $Value
     * @param $PermissionName
     * @param $LimitToSuffix
     * @param $Namespaces
     * @return bool
     */
    protected function IsGlobalPermission($Value, $PermissionName, $LimitToSuffix, $Namespaces) {
        if (!($Value & 2)) {
            return false;
        }
        if (!empty($LimitToSuffix) && substr($PermissionName, -strlen($LimitToSuffix)) != $LimitToSuffix) {
            return false;
        }
        if ($index = strpos($PermissionName, '.')) {
            if (!in_array(substr($PermissionName, 0, $index), $Namespaces) && !in_array(substr($PermissionName, 0, strrpos($PermissionName, '.')), $Namespaces)) {
                return false;
            }
        }
        return true;
    }

    /** Merge junction permissions with global permissions if they are disabled.
     *
     * @param array $GlobalPermissions
     * @return void
     */
    protected function _MergeDisabledPermissions(&$GlobalPermissions) {
        // Get the default permissions for junctions that are disabled.
        $DisabledPermissions = C('Garden.Permissions.Disabled');
        if (!$DisabledPermissions)
            return;

        $DisabledIn = array();
        foreach ($DisabledPermissions as $JunctionTable => $Disabled) {
            if ($Disabled)
                $DisabledIn[] = $JunctionTable;
        }

        if (!$DisabledIn)
            return;

        $DisabledData = $this->SQL
            ->Select('*')
            ->From('Permission p')
            ->Where('p.RoleID', 0)
            ->WhereIn('p.JunctionTable', $DisabledIn)
            ->Get()->ResultArray();

        $DefaultRow =& $GlobalPermissions[0];

        // Loop through each row and add it's default definition to the global permissions.
        foreach ($DisabledData as $PermissionRow) {
            foreach ($PermissionRow as $ColumnName => $Value) {
                if (in_array($ColumnName, array('PermissionID', 'RoleID', 'JunctionTable', 'JunctionColumn', 'JunctionID')))
                    continue;

                if ($Value & 2) {
                    $Setting = $Value | GetValue($ColumnName, $DefaultRow, 0);
                    SetValue($ColumnName, $DefaultRow, $Setting);
                }
            }
        }
    }

    /**
     * Get all of the permission columns in the system.
     *
     * @param bool $JunctionTable
     * @param bool $JunctionColumn
     * @return mixed
     * @throws Exception
     */
    public function PermissionColumns($JunctionTable = FALSE, $JunctionColumn = FALSE) {
        $Key = "{$JunctionTable}__{$JunctionColumn}";

        if (!isset($this->_PermissionColumns[$Key])) {
            $SQL = clone $this->SQL;
            $SQL->Reset();

            $SQL
                ->Select('*')
                ->From('Permission')
                ->Limit(1);

            if ($JunctionTable !== FALSE && $JunctionColumn !== FALSE) {
                $SQL
                    ->Where('JunctionTable', $JunctionTable)
                    ->Where('JunctionColumn', $JunctionColumn)
                    ->Where('RoleID', 0);
            }

            $Cols = $SQL->Get()->FirstRow(DATASET_TYPE_ARRAY);

            unset($Cols['RoleID'], $Cols['JunctionTable'], $Cols['JunctionColumn'], $Cols['JunctionID']);

            $this->_PermissionColumns[$Key] = $Cols;
        }
        return $this->_PermissionColumns[$Key];
    }

    /**
     *
     *
     * @param $PermissionName
     * @return string
     */
    public static function PermissionNamespace($PermissionName) {
        if ($Index = strpos($PermissionName))
            return substr($PermissionName, 0, $Index);
        return '';
    }

    /**
     *
     *
     * @param $Data
     * @param null $Overrides
     * @return array
     */
    public function PivotPermissions($Data, $Overrides = NULL) {
        // Get all of the columns in the permissions table.
        $Schema = $this->SQL->Get('Permission', '', '', 1)->FirstRow(DATASET_TYPE_ARRAY);
        foreach ($Schema as $Key => $Value) {
            if (strpos($Key, '.') !== FALSE)
                $Schema[$Key] = 0;
        }
        unset($Schema['PermissionID']);
        $Schema['RoleID'] = 0;
        $Schema['JunctionTable'] = NULL;
        $Schema['JunctionColumn'] = NULL;
        $Schema['JunctionID'] = NULL;

        $Result = array();
        if (is_array($Data)) {
            foreach ($Data as $SetPermission) {
                // Get the parts out of the permission.
                $Parts = explode('//', $SetPermission);
                if (count($Parts) > 1) {
                    // This is a junction permission.
                    $PermissionName = $Parts[1];
                    $Key = $Parts[0];
                    $Parts = explode('/', $Key);
                    $JunctionTable = $Parts[0];
                    $JunctionColumn = $Parts[1];
                    $JunctionID = ArrayValue('JunctionID', $Overrides, $Parts[2]);
                    if (count($Parts) >= 4)
                        $RoleID = $Parts[3];
                    else
                        $RoleID = ArrayValue('RoleID', $Overrides, NULL);
                } else {
                    // This is a global permission.
                    $PermissionName = $Parts[0];
                    $Key = 'Global';
                    $JunctionTable = NULL;
                    $JunctionColumn = NULL;
                    $JunctionID = NULL;
                    $RoleID = ArrayValue('RoleID', $Overrides, NULL);
                }

                // Check for a row in the result for these permissions.
                if (!array_key_exists($Key, $Result)) {
                    $NewRow = $Schema;
                    $NewRow['RoleID'] = $RoleID;
                    $NewRow['JunctionTable'] = $JunctionTable;
                    $NewRow['JunctionColumn'] = $JunctionColumn;
                    $NewRow['JunctionID'] = $JunctionID;
                    $Result[$Key] = $NewRow;
                }
                $Result[$Key][$PermissionName] = 1;
            }
        }

        return $Result;
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
     * Save a permission row.
     *
     * @param array $Values The values you want to save. See the Permission table for possible columns.
     * @param bool $SaveGlobal Also save a junction permission to the global permissions.
     */
    public function Save($Values, $SaveGlobal = FALSE) {
        // Get the list of columns that are available for permissions.
        $PermissionColumns = Gdn::PermissionModel()->DefineSchema()->Fields();
        if (isset($Values['Role'])) {
            $PermissionColumns['Role'] = TRUE;
        }
        $Values = array_intersect_key($Values, $PermissionColumns);

        // Figure out how to find the existing permission.
        if (array_key_exists('PermissionID', $Values)) {
            $Where = array('PermissionID' => $Values['PermissionID']);
            unset($Values['PermissionID']);

            $this->SQL->Update('Permission', $this->_Backtick($Values), $Where)->Put();
        } else {
            $Where = array();

            if (array_key_exists('RoleID', $Values)) {
                $Where['RoleID'] = $Values['RoleID'];
                unset($Values['RoleID']);
            } elseif (array_key_exists('Role', $Values)) {
                // Get the RoleID.
                $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Values['Role']))->Value('RoleID');
                if (!$RoleID)
                    return;
                $Where['RoleID'] = $RoleID;
                unset($Values['Role']);
            } else {
                $Where['RoleID'] = 0; // default role.
            }

            if (array_key_exists('JunctionTable', $Values)) {
                $Where['JunctionTable'] = $Values['JunctionTable'];

                // If the junction table was given then so must the other values.
                if (array_key_exists('JunctionColumn', $Values))
                    $Where['JunctionColumn'] = $Values['JunctionColumn'];
                $Where['JunctionID'] = $Values['JunctionID'];

                unset($Values['JunctionTable'], $Values['JunctionColumn'], $Values['JunctionID']);
            } else {
                $Where['JunctionTable'] = NULL; // no junction table.
                $Where['JunctionColumn'] = NULL;
                $Where['JunctionID'] = NULL;
            }

            $this->SQL->Replace('Permission', $this->_Backtick($Values), $Where, TRUE);

            if ($SaveGlobal && !is_null($Where['JunctionTable'])) {
                // Save these permissions with the global permissions.
                $Where['JunctionTable'] = NULL; // no junction table.
                $Where['JunctionColumn'] = NULL;
                $Where['JunctionID'] = NULL;

                $this->SQL->Replace('Permission', $this->_Backtick($Values), $Where, TRUE);
            }
        }

        $this->ClearPermissions();
    }

    /**
     *
     *
     * @param $Permissions
     * @param null $AllWhere
     */
    public function SaveAll($Permissions, $AllWhere = NULL) {
        // Load the permission data corresponding to the where so unset permissions get ovewritten.
        if (is_array($AllWhere)) {
            $AllPermissions = $this->SQL->GetWhere('Permission', $AllWhere)->ResultArray();
            // Find the permissions that were loaded, but not saved.
            foreach ($AllPermissions as $i => $AllRow) {
                foreach ($Permissions as $SaveRow) {
                    if ($AllRow['RoleID'] == $SaveRow['RoleID']
                        && $AllRow['JunctionTable'] == $SaveRow['JunctionTable']
                        && $AllRow['JunctionID'] == $SaveRow['JunctionID']
                    ) {

                        unset($AllPermissions[$i]); // saving handled already.
                        break;
                    }
                }
            }
            // Make all permission false that need to be saved here.
            foreach ($AllPermissions as &$AllRow) {
                foreach ($AllRow as $Name => $Value) {
                    if (strpos($Name, '.') !== FALSE)
                        $AllRow[$Name] = 0;
                }
            }
            if (count($AllPermissions) > 0)
                $Permissions = array_merge($Permissions, $AllPermissions);
        }

        foreach ($Permissions as $Row) {
            $this->Save($Row);
        }

        // TODO: Clear the permissions for rows that aren't here.
    }

    /**
     * Reset permissions for all roles, based on the value in their Type column.
     *
     * @param string $Type Role type to limit the updates to.
     */
    public static function ResetAllRoles($Type = null) {
        // Retrieve an array containing all available roles.
        $RoleModel = new RoleModel();
        if ($Type) {
            $Result = $RoleModel->getByType($Type)->ResultArray();
            $Roles = array_column($Result, 'Name', 'RoleID');
        } else {
            $Roles = $RoleModel->GetArray();
        }

        // Iterate through our roles and reset their permissions.
        $Permissions = Gdn::PermissionModel();
        foreach ($Roles as $RoleID => $Role) {
            $Permissions->ResetRole($RoleID);
        }
    }

    /**
     * Reset permissions for a role, based on the value in its Type column.
     *
     * @param int $RoleId ID of the role to reset permissions for.
     * @throws Exception
     */
    public function ResetRole($RoleId) {
        // Grab the value of Type for this role.
        $RoleType = $this->SQL->GetWhere('Role', array('RoleID' => $RoleId))->Value('Type');

        if ($RoleType == '') {
            $RoleType = RoleModel::TYPE_MEMBER;
        }

        $Defaults = $this->GetDefaults();
        $RowDefaults = $this->GetRowDefaults();

        $ResetValues = array_fill_keys(array_keys($RowDefaults), 0);

        if (array_key_exists($RoleType, $Defaults)) {
            foreach ($Defaults[$RoleType] as $Specificity => $Permissions) {
                $Permissions['RoleID'] = $RoleId;
                $Permissions = array_merge($ResetValues, $Permissions);

                if (strpos($Specificity, ':')) {
                    list($Junction, $JunctionId) = explode(':', $Specificity);
                    if ($Junction && $JunctionId) {
                        switch ($Junction) {
                            case 'Category':
                            default:
                                $Permissions['JunctionTable'] = $Junction;
                                $Permissions['JunctionColumn'] = 'PermissionCategoryID';
                                $Permissions['JunctionID'] = $JunctionId;
                        }
                    }
                }

                $this->Save($Permissions);
            }
        }
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

        if ($i !== FALSE) { // $j must also not be false
            return array(substr($PermissionName, 0, $i), substr($PermissionName, $i + 1, $j - $i - 1), substr($PermissionName, $j + 1));
        } else {
            return array($PermissionName, '', '');
        }
    }

    /**
     * Joins the query to a permission junction table and limits the results accordingly.
     *
     * @param Gdn_SQLDriver $SQL The SQL driver to add the permission to.
     * @param mixed $Permissions The permission name (or array of names) to use when limiting the query.
     * @param string $ForeignAlias The alias of the table to join to (ie. Category).
     * @param string $ForeignColumn The primary key column name of $JunctionTable (ie. CategoryID).
     * @param string $JunctionTable
     * @param string $JunctionColumn
     */
    public function SQLPermission($SQL, $Permissions, $ForeignAlias, $ForeignColumn, $JunctionTable = '', $JunctionColumn = '') {
        $Session = Gdn::Session();

        // Figure out the junction table if necessary.
        if (!$JunctionTable && StringEndsWith($ForeignColumn, 'ID'))
            $JunctionTable = substr($ForeignColumn, 0, -2);

        // Check to see if the permission is disabled.
        if (C('Garden.Permission.Disabled.'.$JunctionTable)) {
            if (!$Session->CheckPermission($Permissions))
                $SQL->Where('1', '0', FALSE, FALSE);
        } elseif ($Session->UserID <= 0 || (is_object($Session->User) && $Session->User->Admin != '1')) {
            $SQL->Distinct()
                ->Join('Permission _p', '_p.JunctionID = '.$ForeignAlias.'.'.$ForeignColumn, 'inner')
                ->Join('UserRole _ur', '_p.RoleID = _ur.RoleID', 'inner')
                ->BeginWhereGroup()
                ->Where('_ur.UserID', $Session->UserID);

            if (!is_array($Permissions))
                $Permissions = array($Permissions);

            $SQL->BeginWhereGroup();
            foreach ($Permissions as $Permission) {
                $SQL->Where('_p.`'.$Permission.'`', 1);
            }
            $SQL->EndWhereGroup();
        } else {
            // Force this method to play nice in case it is used in an or clause
            // (ie. it returns true in a sql sense by doing 1 = 1)
            $SQL->Where('1', '1', FALSE, FALSE);
        }

        return $SQL;
    }

    /**
     *
     *
     * @param $Permissions
     * @param bool $IncludeRole
     * @return array
     */
    public function UnpivotPermissions($Permissions, $IncludeRole = FALSE) {
        $Result = array();
        foreach ($Permissions as $Row) {
            $this->_UnpivotPermissionsRow($Row, $Result, $IncludeRole);
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Names
     */
    public function Undefine($Names) {
        $Names = (array)$Names;
        $St = $this->Database->Structure();
        $St->Table('Permission');

        foreach ($Names as $Name) {
            if ($St->ColumnExists($Name))
                $St->DropColumn($Name);
        }
        $St->Reset();
    }

    /**
     *
     *
     * @param $Row
     * @param $Result
     * @param bool $IncludeRole
     */
    protected function _UnpivotPermissionsRow($Row, &$Result, $IncludeRole = FALSE) {
        $GlobalName = ArrayValue('Name', $Row);

        // Loop through each permission in the row and place them in the correct place in the grid.
        foreach ($Row as $PermissionName => $Value) {
            list($Namespace, $Name, $Suffix) = self::SplitPermission($PermissionName);
            if (empty($Name))
                continue; // was some other column

            if ($GlobalName) $Namespace = $GlobalName;

            if (array_key_exists('JunctionTable', $Row) && ($JunctionTable = $Row['JunctionTable'])) {
                $Key = "$JunctionTable/{$Row['JunctionColumn']}/{$Row['JunctionID']}".($IncludeRole ? '/'.$Row['RoleID'] : '');
            } else {
                $Key = '_'.$Namespace;
            }


            // Check to see if the namespace is in the result.
            if (!array_key_exists($Key, $Result))
                $Result[$Key] = array('_Columns' => array(), '_Rows' => array(), '_Info' => array('Name' => $Namespace));
            $NamespaceArray = &$Result[$Key];

            // Add the names to the columns and rows.
            $NamespaceArray['_Columns'][$Suffix] = TRUE;
            $NamespaceArray['_Rows'][$Name] = TRUE;

            // Augment the value depending on the junction ID.
            if (substr($Key, 0, 1) === '_') {
                $PostValue = $PermissionName;
            } else {
                $PostValue = $Key.'//'.$PermissionName;
            }

            $NamespaceArray[$Name.'.'.$Suffix] = array('Value' => $Value, 'PostValue' => $PostValue);
        }
    }
}

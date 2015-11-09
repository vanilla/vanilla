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
    public function addDefault($Type, $Permissions, $Junction = null, $JunctionId = null) {
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
     * Add the permissions from one permission array to another.
     *
     * @param array $perms1 The permissions to be added to.
     * @param array $perms2 The permissions to add.
     * @return array Returns an array with all of the permissions in both permissions arrays.
     */
    public static function addPermissions($perms1, $perms2) {
        // Union the global permissions.
        $result = array_unique(array_merge(array_filter($perms1, 'is_string'), array_filter($perms2, 'is_string')));

        // Union the junction permissions.
        $junctions1 = array_filter($perms1, 'is_array');
        $junctions2 = array_filter($perms2, 'is_array');
        foreach ($junctions2 as $key => $ids) {
            if (empty($junctions1[$key])) {
                $junctions1[$key] = $ids;
            } else {
                $junctions1[$key] = array_unique(array_merge($junctions1[$key], $ids));
            }
        }

        $result = array_merge($result, $junctions1);
        return $result;
    }

    /**
     * Populate a list of default permissions, per type.
     *
     * @param bool $ResetDefaults If we already have defaults, should they be discarded?
     */
    public function assignDefaults($ResetDefaults = false) {
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
        $this->fireEvent('DefaultPermissions');
    }

    /**
     *
     */
    public function clearPermissions() {
        static $PermissionsCleared = false;

        if (!$PermissionsCleared) {
            // Remove the cached permissions for all users.
            Gdn::userModel()->ClearPermissions();
            $PermissionsCleared = true;
        }
    }

    /**
     * Define one or more permissions with default values.
     *
     * @param array $PermissionNames
     * @param string $Type
     * @param string? $JunctionTable
     * @param string? $JunctionColumn
     * @throws Exception
     */
    public function define($PermissionNames, $Type = 'tinyint', $JunctionTable = null, $JunctionColumn = null) {
        $PermissionNames = (array)$PermissionNames;

        $Structure = $this->Database->Structure();
        $Structure->table('Permission');
        $DefaultPermissions = array();
        $NewColumns = array();

        foreach ($PermissionNames as $Key => $Value) {
            if (is_numeric($Key)) {
                // Only got a permissions name with no default.
                $PermissionName = $Value;
                $DefaultPermissions[$PermissionName] = 2;
            } else {
                $PermissionName = $Key;

                if ($Value === 0) {
                    // "Off for all"
                    $DefaultPermissions[$PermissionName] = 2;
                } elseif ($Value === 1)
                    // "On for all"
                    $DefaultPermissions[$PermissionName] = 3;
                elseif (!$Structure->columnExists($Value) && array_key_exists($Value, $PermissionNames))
                    // Mapped to an explicitly-defined permission.
                    $DefaultPermissions[$PermissionName] = $PermissionNames[$Value] ? 3 : 2;
                else {
                    // Mapped to another permission for which we don't have the value.
                    $DefaultPermissions[$PermissionName] = "`{$Value}`";
                }
            }
            if (!$Structure->columnExists($PermissionName)) {
                $Default = $DefaultPermissions[$PermissionName];
                $NewColumns[$PermissionName] = is_numeric($Default) ? $Default - 2 : $Default;
            }

            // Define the column.
            $Structure->column($PermissionName, $Type, 0);

        }
        $Structure->set(false, false);

        // Detect an initial permission setup by seeing if our placeholder row exists yet.
        $DefaultRow = $this->SQL
            ->select('*')
            ->from('Permission')
            ->where('RoleID', 0)
            ->where('JunctionTable is null')
            ->orderBy('RoleID')
            ->limit(1)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        // If this is our initial setup, map missing permissions to off.
        // Otherwise we'd be left with placeholders in our final query, which would cause a strict mode failure.
        if (!$DefaultRow) {
            $DefaultPermissions = array_map(
                function ($Value) {
                    // All non-numeric values are converted to "off" flag.
                    return (is_numeric($Value)) ? $Value : 2;
                },
                $DefaultPermissions
            );
        }

        // Set the default permissions on the placeholder.
        $this->SQL
            ->set($this->_backtick($DefaultPermissions), '', false)
            ->replace('Permission', array(), array('RoleID' => 0, 'JunctionTable' => $JunctionTable, 'JunctionColumn' => $JunctionColumn), true);

        // Set the default permissions for new columns on all roles.
        if (count($NewColumns) > 0) {
            $Where = array('RoleID <>' => 0);
            if (!$JunctionTable) {
                $Where['JunctionTable'] = null;
            } else {
                $Where['JunctionTable'] = $JunctionTable;
            }

            $this->SQL
                ->set($this->_backtick($NewColumns), '', false)
                ->put('Permission', array(), $Where);
        }

        // Flush permissions cache & loaded schema.
        $this->clearPermissions();
        if ($this->Schema) {
            // Redefine the schema if it has been defined to reflect the permissions that were just added.
            $this->Schema = null;
            $this->defineSchema();
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
    public function delete($RoleID = null, $JunctionTable = null, $JunctionColumn = null, $JunctionID = null) {
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

        $this->SQL->delete('Permission', $Where);

        if (!is_null($RoleID)) {
            // Rebuild the permission cache.
        }
    }

    /**
     * Grab the list of default permissions by role type
     *
     * @return array List of permissions, grouped by role type
     */
    public function getDefaults() {
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
    public function getRowDefaults() {
        if (empty($this->RowDefaults)) {
            $DefaultRow = $this->SQL
                ->select('*')
                ->from('Permission')
                ->where('RoleID', 0)
                ->where('JunctionTable is null')
                ->orderBy('RoleID')
                ->limit(1)
                ->get()->firstRow(DATASET_TYPE_ARRAY);

            if (!$DefaultRow) {
                throw new Exception(t('No default permission row.'));
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
    public function getUserPermissions($UserID, $LimitToSuffix = '', $JunctionTable = false, $JunctionColumn = false, $ForeignKey = false, $ForeignID = false) {
        // Get all permissions
        $PermissionColumns = $this->PermissionColumns($JunctionTable, $JunctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($PermissionColumns as $ColumnName => $Value) {
            if (!empty($LimitToSuffix) && substr($ColumnName, -strlen($LimitToSuffix)) != $LimitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }            $this->SQL->select('p.`'.$ColumnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->from('Permission p')
            ->join('UserRole ur', 'p.RoleID = ur.RoleID')
            ->where('ur.UserID', $UserID);

        // Either limit to 1 junction or exclude junctions
        if ($JunctionTable && $JunctionColumn) {
            $this->SQL
                ->select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
                ->groupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));
            if ($ForeignKey && $ForeignID) {
                $this->SQL
                    ->join("$JunctionTable j", "j.$JunctionColumn = p.JunctionID")
                    ->where("j.$ForeignKey", $ForeignID);
            }
        } else {
            $this->SQL->where('p.JunctionTable is null');
        }

        return $this->SQL->get()->resultArray();
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
    public function getRolePermissions($RoleID, $LimitToSuffix = '', $JunctionTable = false, $JunctionColumn = false, $ForeignKey = false, $ForeignID = false) {
        // Get all permissions
        $PermissionColumns = $this->PermissionColumns($JunctionTable, $JunctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($PermissionColumns as $ColumnName => $Value) {
            if (!empty($LimitToSuffix) && substr($ColumnName, -strlen($LimitToSuffix)) != $LimitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }            $this->SQL->select('p.`'.$ColumnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->from('Permission p')
            ->where('p.RoleID', $RoleID);

        // Either limit to 1 junction or exclude junctions
        if ($JunctionTable && $JunctionColumn) {
            $this->SQL
                ->select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
                ->groupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));
            if ($ForeignKey && $ForeignID) {
                $this->SQL
                    ->join("$JunctionTable j", "j.$JunctionColumn = p.JunctionID")
                    ->where("j.$ForeignKey", $ForeignID);
            }
        } else {
            $this->SQL->where('p.JunctionTable is null');
        }

        return $this->SQL->get()->resultArray();
    }

    /**
     * Returns a complete list of all enabled applications & plugins. This list
     * can act as a namespace list for permissions.
     *
     * @return array
     */
    public function getAllowedPermissionNamespaces() {
        $ApplicationManager = new Gdn_ApplicationManager();
        $EnabledApplications = $ApplicationManager->EnabledApplications();

        $PluginNamespaces = array();
        foreach (Gdn::pluginManager()->EnabledPlugins() as $Plugin) {
            if (!array_key_exists('RegisterPermissions', $Plugin) || !is_array($Plugin['RegisterPermissions'])) {
                continue;
            }
            foreach ($Plugin['RegisterPermissions'] as $Index => $PermissionName) {
                if (is_string($Index)) {
                    $PermissionName = $Index;
                }

                $Namespace = substr($PermissionName, 0, strrpos($PermissionName, '.'));
                $PluginNamespaces[$Namespace] = true;
            }
        }

        $Result = array_merge(array_keys($EnabledApplications), array_keys($PluginNamespaces));
        if (in_array('Dashboard', $Result)) {
            $Result[] = 'Garden';
        }
        return $Result;
    }

    /**
     *
     *
     * @param null $UserID
     * @param null $RoleID
     * @return array|null
     */
    public function cachePermissions($UserID = null, $RoleID = null) {
        if (!$UserID) {
            $RoleID = RoleModel::getDefaultRoles(RoleModel::TYPE_GUEST);
        }

        // Select all of the permission columns.
        $PermissionColumns = $this->PermissionColumns();
        foreach ($PermissionColumns as $ColumnName => $Value) {
            $this->SQL->select('p.`'.$ColumnName.'`', 'MAX');
        }

        $this->SQL->from('Permission p');

        if (!is_null($RoleID)) {
            $this->SQL->where('p.RoleID', $RoleID);
        } elseif (!is_null($UserID))
            $this->SQL->join('UserRole ur', 'p.RoleID = ur.RoleID')->where('ur.UserID', $UserID);

        $this->SQL
            ->select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
            ->groupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));

        $Result = $this->SQL->get()->resultArray();
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
    public function getJunctionPermissions($Where, $JunctionTable = null, $LimitToSuffix = '', $Options = array()) {
        $Namespaces = $this->GetAllowedPermissionNamespaces();
        $RoleID = arrayValue('RoleID', $Where, null);
        $JunctionID = arrayValue('JunctionID', $Where, null);
        $SQL = $this->SQL;

        // Load all of the default junction permissions.
        $SQL->select('*')
            ->from('Permission p')
            ->where('p.RoleID', 0);

        if (is_null($JunctionTable)) {
            $SQL->where('p.JunctionTable is not null');
        } else {
            $SQL->where('p.JunctionTable', $JunctionTable);
        }

        // Get the disabled permissions.
        $DisabledPermissions = c('Garden.Permissions.Disabled');
        if (is_array($DisabledPermissions)) {
            $DisabledWhere = array();
            foreach ($DisabledPermissions as $TableName => $Disabled) {
                if ($Disabled) {
                    $DisabledWhere[] = $TableName;
                }
            }
            if (count($DisabledWhere) > 0) {
                $SQL->whereNotIn('JunctionTable', $DisabledWhere);
            }
        }

        $Data = $SQL->get()->resultArray();
        $Result = array();
        foreach ($Data as $Row) {
            $JunctionTable = $Row['JunctionTable'];
            $JunctionColumn = $Row['JunctionColumn'];
            unset($Row['PermissionID'], $Row['RoleID'], $Row['JunctionTable'], $Row['JunctionColumn'], $Row['JunctionID']);

            // If the junction column is not the primary key then we must figure out and limit the permissions.
            if ($JunctionColumn != $JunctionTable.'ID') {
                $JuncIDs = $SQL
                    ->Distinct(true)
                    ->select("p.{$JunctionTable}ID")
                    ->select("c.$JunctionColumn")
                    ->select('p.Name')
                    ->from("$JunctionTable c")
                    ->join("$JunctionTable p", "c.$JunctionColumn = p.{$JunctionTable}ID", 'left')
                    ->get()->resultArray();

                foreach ($JuncIDs as &$JuncRow) {
                    if (!$JuncRow[$JunctionTable.'ID']) {
                        $JuncRow[$JunctionTable.'ID'] = -1;
                    }
                }
            }

            // Figure out which columns to select.
            foreach ($Row as $PermissionName => $Value) {
                if (!($Value & 2)) {
                    continue; // permission not applicable to this junction table
                }                if (!empty($LimitToSuffix) && substr($PermissionName, -strlen($LimitToSuffix)) != $LimitToSuffix) {
                    continue; // permission not in $LimitToSuffix
                }                if ($index = strpos($PermissionName, '.')) {
                    if (!in_array(substr($PermissionName, 0, $index), $Namespaces) &&
                        !in_array(substr($PermissionName, 0, strrpos($PermissionName, '.')), $Namespaces)
                    ) {
                        continue; // permission not in allowed namespaces
                    }
                }

                // If we are viewing the permissions by junction table (ex. Category) then set the default value when a permission row doesn't exist.
                if (!$RoleID && $JunctionColumn != $JunctionTable.'ID' && val('AddDefaults', $Options)) {
                    $DefaultValue = $Value & 1 ? 1 : 0;
                } else {
                    $DefaultValue = 0;
                }

                $SQL->select("p.`$PermissionName`, $DefaultValue", 'coalesce', $PermissionName);
            }

            if (!empty($RoleID)) {
                $roleIDs = (array)$RoleID;
                if (count($roleIDs) === 1) {
                    $roleOn = 'p.RoleID = '.$this->SQL->Database->connection()->quote(reset($roleIDs));
                } else {
                    $roleIDs = array_map(array($this->SQL->Database->connection(), 'quote'), $roleIDs);
                    $roleOn = 'p.RoleID in ('.implode(',', $roleIDs).')';
                }

                // Get the permissions for the junction table.
                $SQL->select('junc.Name')
                    ->select('junc.'.$JunctionColumn, '', 'JunctionID')
                    ->from($JunctionTable.' junc')
                    ->join('Permission p', "p.JunctionID = junc.$JunctionColumn and $roleOn", 'left')
                    ->orderBy('junc.Sort')
                    ->orderBy('junc.Name');

                if (isset($JuncIDs)) {
                    $SQL->whereIn("junc.{$JunctionTable}ID", array_column($JuncIDs, "{$JunctionTable}ID"));
                }
            } else {
                // Here we are getting permissions for all roles.
                $SQL->select('r.RoleID, r.Name, r.CanSession')
                    ->from('Role r')
                    ->join('Permission p', "p.RoleID = r.RoleID and p.JunctionTable = '$JunctionTable' and p.JunctionColumn = '$JunctionColumn' and p.JunctionID = $JunctionID", 'left')
                    ->orderBy('r.Sort, r.Name');
            }
            $JuncData = $SQL->get()->resultArray();

            // Add all of the necessary information back to the result.
            foreach ($JuncData as $JuncRow) {
                $JuncRow['JunctionTable'] = $JunctionTable;
                $JuncRow['JunctionColumn'] = $JunctionColumn;
                if (!is_null($JunctionID)) {
                    $JuncRow['JunctionID'] = $JunctionID;
                }
                if ($JuncRow['JunctionID'] < 0) {
                    $JuncRow['Name'] = sprintf(t('Default %s Permissions'), t('Permission.'.$JunctionTable, $JunctionTable));
                }

                if (array_key_exists('CanSession', $JuncRow)) {
                    if (!$JuncRow['CanSession']) {
                        // Remove view permissions.
                        foreach ($JuncRow as $PermissionName => $Value) {
                            if (strpos($PermissionName, '.') !== false && strpos($PermissionName, '.View') === false) {
                                unset($JuncRow[$PermissionName]);
                            }
                        }
                    }

                    unset($JuncRow['CanSession']);
                }

                if (!$RoleID && !$JunctionID && array_key_exists(0, $Data)) {
                    // Set all of the default permissions for a new role.
                    foreach ($JuncRow as $PermissionName => $Value) {
                        if (val($PermissionName, $Data[0], 0) & 1) {
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
    public function getPermissions($RoleID, $LimitToSuffix = '') {
        $RoleID = (array)$RoleID;
        $Result = array();

        $GlobalPermissions = $this->GetGlobalPermissions($RoleID, $LimitToSuffix);
        $Result[] = $GlobalPermissions;

        $JunctionPermissions = $this->GetJunctionPermissions(array('RoleID' => $RoleID), null, $LimitToSuffix);
        $Result = array_merge($Result, $JunctionPermissions);

        return $Result;
    }

    /**
     * Get the permissions for one or more roles.
     *
     * @param int $roleID The role to get the permissions for.
     * @return array Returns a permission array suitable for use in a session.
     */
    public function getPermissionsByRole($roleID) {
        $inc = Gdn::userModel()->getPermissionsIncrement();
        $key = "perms:$inc:role:$roleID";

        $permissions = Gdn::cache()->get($key);
        if ($permissions === Gdn_Cache::CACHEOP_FAILURE) {
            $sql = clone $this->SQL;
            $sql->reset();

            // Select all of the permission columns.
            $permissionColumns = $this->permissionColumns();
            foreach ($permissionColumns as $columnName => $value) {
                $sql->select('p.`'.$columnName.'`', 'MAX');
            }

            $sql->from('Permission p')
                ->where('p.RoleID', $roleID)
                ->select(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'))
                ->groupBy(array('p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'));

            $permissions = $sql->get()->resultArray();
            $permissions = UserModel::compilePermissions($permissions);
            Gdn::cache()->store($key, $permissions);
        }

        return $permissions;
    }

    /**
     *
     *
     * @param $RoleID
     * @param string $LimitToSuffix
     * @return array
     */
    public function getPermissionsEdit($RoleID, $LimitToSuffix = '') {
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
    public function getGlobalPermissions($RoleID, $LimitToSuffix = '') {
        $RoleIDs = (array)$RoleID;

        // Get the global permissions.
        $Data = $this->SQL
            ->select('*')
            ->from('Permission p')
            ->whereIn('p.RoleID', array_merge($RoleIDs, array(0)))
            ->where('p.JunctionTable is null')
            ->orderBy('p.RoleID')
            ->get()->resultArray();

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
                $Data[$ID]['PermissionID'] = null;
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
    public function stripPermissions($Row, $DefaultRow, $LimitToSuffix = '') {
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
    protected function isGlobalPermission($Value, $PermissionName, $LimitToSuffix, $Namespaces) {
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
        $DisabledPermissions = c('Garden.Permissions.Disabled');
        if (!$DisabledPermissions) {
            return;
        }

        $DisabledIn = array();
        foreach ($DisabledPermissions as $JunctionTable => $Disabled) {
            if ($Disabled) {
                $DisabledIn[] = $JunctionTable;
            }
        }

        if (!$DisabledIn) {
            return;
        }

        $DisabledData = $this->SQL
            ->select('*')
            ->from('Permission p')
            ->where('p.RoleID', 0)
            ->whereIn('p.JunctionTable', $DisabledIn)
            ->get()->resultArray();

        $DefaultRow =& $GlobalPermissions[0];

        // Loop through each row and add it's default definition to the global permissions.
        foreach ($DisabledData as $PermissionRow) {
            foreach ($PermissionRow as $ColumnName => $Value) {
                if (in_array($ColumnName, array('PermissionID', 'RoleID', 'JunctionTable', 'JunctionColumn', 'JunctionID'))) {
                    continue;
                }

                if ($Value & 2) {
                    $Setting = $Value | val($ColumnName, $DefaultRow, 0);
                    setValue($ColumnName, $DefaultRow, $Setting);
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
    public function permissionColumns($JunctionTable = false, $JunctionColumn = false) {
        $Key = "{$JunctionTable}__{$JunctionColumn}";

        if (!isset($this->_PermissionColumns[$Key])) {
            $SQL = clone $this->SQL;
            $SQL->reset();

            $SQL
                ->select('*')
                ->from('Permission')
                ->limit(1);

            if ($JunctionTable !== false && $JunctionColumn !== false) {
                $SQL
                    ->where('JunctionTable', $JunctionTable)
                    ->where('JunctionColumn', $JunctionColumn)
                    ->where('RoleID', 0);
            }

            $Cols = $SQL->get()->firstRow(DATASET_TYPE_ARRAY);

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
    public static function permissionNamespace($PermissionName) {
        if ($Index = strpos($PermissionName)) {
            return substr($PermissionName, 0, $Index);
        }
        return '';
    }

    /**
     *
     *
     * @param $Data
     * @param null $Overrides
     * @return array
     */
    public function pivotPermissions($Data, $Overrides = null) {
        // Get all of the columns in the permissions table.
        $Schema = $this->SQL->get('Permission', '', '', 1)->firstRow(DATASET_TYPE_ARRAY);
        foreach ($Schema as $Key => $Value) {
            if (strpos($Key, '.') !== false) {
                $Schema[$Key] = 0;
            }
        }
        unset($Schema['PermissionID']);
        $Schema['RoleID'] = 0;
        $Schema['JunctionTable'] = null;
        $Schema['JunctionColumn'] = null;
        $Schema['JunctionID'] = null;

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
                    $JunctionID = arrayValue('JunctionID', $Overrides, $Parts[2]);
                    if (count($Parts) >= 4) {
                        $RoleID = $Parts[3];
                    } else {
                        $RoleID = arrayValue('RoleID', $Overrides, null);
                    }
                } else {
                    // This is a global permission.
                    $PermissionName = $Parts[0];
                    $Key = 'Global';
                    $JunctionTable = null;
                    $JunctionColumn = null;
                    $JunctionID = null;
                    $RoleID = arrayValue('RoleID', $Overrides, null);
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
    /*public function getJunctionData($JunctionTable, $JunctionColumn) {
       return $this->SQL
          ->select($JunctionColumn, '', 'JunctionID')
          ->select('Name')
          ->from($JunctionTable)
          ->orderBy('Name', 'asc')
          ->get();
    }*/

    /**
     * Return a dataset of all available junction tables (as defined in
     * Permission.JunctionTable).
     *
     * @return DataSet
     */
    /* public function getJunctionTables() {
        return $this->SQL
           ->select('JunctionTable, JunctionColumn')
           ->from('Permission')
           ->where('JunctionTable is not null')
           ->groupBy('JunctionTable, JunctionColumn')
           ->get();
     }*/

    /**
     * Allows the insertion of new permissions. If the permission(s) already
     * exist in the database, or is not formatted properly, it will be skipped.
     *
     * @param mixed $Permission The permission (or array of permissions) to be added.
     * @param string $JunctionTable The junction table to relate the permission(s) to.
     * @param string $JunctionColumn The junction column to relate the permission(s) to.
     */
    /* public function insertNew($Permission, $JunctionTable = '', $JunctionColumn = '') {
        if (!is_array($Permission))
           $Permission = array($Permission);

        $PermissionCount = count($Permission);
        // Validate the permissions first
        if (ValidatePermissionFormat($Permission)) {
           // Now save them
           $this->defineSchema();
           for ($i = 0; $i < $PermissionCount; ++$i) {
              // Check to see if the permission already exists
              $ResultSet = $this->getWhere(array('Name' => $Permission[$i]));
              // If not, insert it now
              if ($ResultSet->numRows() == 0) {
                 $Values = array();
                 $Values['Name'] = $Permission[$i];
                 if ($JunctionTable != '') {
                    $Values['JunctionTable'] = $JunctionTable;
                    $Values['JunctionColumn'] = $JunctionColumn;
                 }
                 $this->insert($Values);
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
    public function save($Values, $SaveGlobal = false) {
        // Get the list of columns that are available for permissions.
        $PermissionColumns = Gdn::permissionModel()->defineSchema()->Fields();
        if (isset($Values['Role'])) {
            $PermissionColumns['Role'] = true;
        }
        $Values = array_intersect_key($Values, $PermissionColumns);

        // Figure out how to find the existing permission.
        if (array_key_exists('PermissionID', $Values)) {
            $Where = array('PermissionID' => $Values['PermissionID']);
            unset($Values['PermissionID']);

            $this->SQL->update('Permission', $this->_Backtick($Values), $Where)->put();
        } else {
            $Where = array();

            if (array_key_exists('RoleID', $Values)) {
                $Where['RoleID'] = $Values['RoleID'];
                unset($Values['RoleID']);
            } elseif (array_key_exists('Role', $Values)) {
                // Get the RoleID.
                $RoleID = $this->SQL->getWhere('Role', array('Name' => $Values['Role']))->value('RoleID');
                if (!$RoleID) {
                    return;
                }
                $Where['RoleID'] = $RoleID;
                unset($Values['Role']);
            } else {
                $Where['RoleID'] = 0; // default role.
            }

            if (array_key_exists('JunctionTable', $Values)) {
                $Where['JunctionTable'] = $Values['JunctionTable'];

                // If the junction table was given then so must the other values.
                if (array_key_exists('JunctionColumn', $Values)) {
                    $Where['JunctionColumn'] = $Values['JunctionColumn'];
                }
                $Where['JunctionID'] = $Values['JunctionID'];
            } else {
                $Where['JunctionTable'] = null; // no junction table.
                $Where['JunctionColumn'] = null;
                $Where['JunctionID'] = null;
            }
            
            unset($Values['JunctionTable'], $Values['JunctionColumn'], $Values['JunctionID']);

            $this->SQL->replace('Permission', $this->_Backtick($Values), $Where, true);

            if ($SaveGlobal && !is_null($Where['JunctionTable'])) {
                // Save these permissions with the global permissions.
                $Where['JunctionTable'] = null; // no junction table.
                $Where['JunctionColumn'] = null;
                $Where['JunctionID'] = null;

                $this->SQL->replace('Permission', $this->_Backtick($Values), $Where, true);
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
    public function saveAll($Permissions, $AllWhere = null) {
        // Load the permission data corresponding to the where so unset permissions get ovewritten.
        if (is_array($AllWhere)) {
            $AllPermissions = $this->SQL->getWhere('Permission', $AllWhere)->resultArray();
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
                    if (strpos($Name, '.') !== false) {
                        $AllRow[$Name] = 0;
                    }
                }
            }
            if (count($AllPermissions) > 0) {
                $Permissions = array_merge($Permissions, $AllPermissions);
            }
        }

        foreach ($Permissions as $Row) {
            $this->save($Row);
        }

        // TODO: Clear the permissions for rows that aren't here.
    }

    /**
     * Reset permissions for all roles, based on the value in their Type column.
     *
     * @param string $Type Role type to limit the updates to.
     */
    public static function resetAllRoles($Type = null) {
        // Retrieve an array containing all available roles.
        $RoleModel = new RoleModel();
        if ($Type) {
            $Result = $RoleModel->getByType($Type)->resultArray();
            $Roles = array_column($Result, 'Name', 'RoleID');
        } else {
            $Roles = $RoleModel->getArray();
        }

        // Iterate through our roles and reset their permissions.
        $Permissions = Gdn::permissionModel();
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
    public function resetRole($RoleId) {
        // Grab the value of Type for this role.
        $RoleType = $this->SQL->getWhere('Role', array('RoleID' => $RoleId))->value('Type');

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

                $this->save($Permissions);
            }
        }
    }

    /**
     * Split a permission name into its constituant parts.
     *
     * @param string $PermissionName The name of the permission.
     * @return array The split permission in the form array(Namespace, Permission,Suffix).
     */
    public static function splitPermission($PermissionName) {
        $i = strpos($PermissionName, '.');
        $j = strrpos($PermissionName, '.');

        if ($i !== false) { // $j must also not be false
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
    public function sQLPermission($SQL, $Permissions, $ForeignAlias, $ForeignColumn, $JunctionTable = '', $JunctionColumn = '') {
        $Session = Gdn::session();

        // Figure out the junction table if necessary.
        if (!$JunctionTable && StringEndsWith($ForeignColumn, 'ID')) {
            $JunctionTable = substr($ForeignColumn, 0, -2);
        }

        // Check to see if the permission is disabled.
        if (c('Garden.Permission.Disabled.'.$JunctionTable)) {
            if (!$Session->checkPermission($Permissions)) {
                $SQL->where('1', '0', false, false);
            }
        } elseif ($Session->UserID <= 0 || (is_object($Session->User) && $Session->User->Admin != '1')) {
            $SQL->Distinct()
                ->join('Permission _p', '_p.JunctionID = '.$ForeignAlias.'.'.$ForeignColumn, 'inner')
                ->join('UserRole _ur', '_p.RoleID = _ur.RoleID', 'inner')
                ->beginWhereGroup()
                ->where('_ur.UserID', $Session->UserID);

            if (!is_array($Permissions)) {
                $Permissions = array($Permissions);
            }

            $SQL->beginWhereGroup();
            foreach ($Permissions as $Permission) {
                $SQL->where('_p.`'.$Permission.'`', 1);
            }
            $SQL->endWhereGroup();
        } else {
            // Force this method to play nice in case it is used in an or clause
            // (ie. it returns true in a sql sense by doing 1 = 1)
            $SQL->where('1', '1', false, false);
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
    public function unpivotPermissions($Permissions, $IncludeRole = false) {
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
    public function undefine($Names) {
        $Names = (array)$Names;
        $St = $this->Database->Structure();
        $St->table('Permission');

        foreach ($Names as $Name) {
            if ($St->columnExists($Name)) {
                $St->DropColumn($Name);
            }
        }
        $St->reset();
    }

    /**
     *
     *
     * @param $Row
     * @param $Result
     * @param bool $IncludeRole
     */
    protected function _UnpivotPermissionsRow($Row, &$Result, $IncludeRole = false) {
        $GlobalName = arrayValue('Name', $Row);

        // Loop through each permission in the row and place them in the correct place in the grid.
        foreach ($Row as $PermissionName => $Value) {
            list($Namespace, $Name, $Suffix) = self::SplitPermission($PermissionName);
            if (empty($Name)) {
                continue; // was some other column
            }
            if ($GlobalName) {
                $Namespace = $GlobalName;
            }

            if (array_key_exists('JunctionTable', $Row) && ($JunctionTable = $Row['JunctionTable'])) {
                $Key = "$JunctionTable/{$Row['JunctionColumn']}/{$Row['JunctionID']}".($IncludeRole ? '/'.$Row['RoleID'] : '');
            } else {
                $Key = '_'.$Namespace;
            }


            // Check to see if the namespace is in the result.
            if (!array_key_exists($Key, $Result)) {
                $Result[$Key] = array('_Columns' => array(), '_Rows' => array(), '_Info' => array('Name' => $Namespace));
            }
            $NamespaceArray = &$Result[$Key];

            // Add the names to the columns and rows.
            $NamespaceArray['_Columns'][$Suffix] = true;
            $NamespaceArray['_Rows'][$Name] = true;

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

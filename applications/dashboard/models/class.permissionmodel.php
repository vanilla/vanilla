<?php
/**
 * Permission model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles permission data.
 */
class PermissionModel extends Gdn_Model {

    /** @var array Default role permissions. */
    protected $DefaultPermissions = [];

    /** @var array Default row permission values. */
    protected $RowDefaults = [];

    /** @var array Permission columns. */
    protected $_PermissionColumns = [];

    /** @var array Permission namespaces from enabled addons. */
    private $namespaces;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Permission');
    }

    /**
     *
     *
     * @param $values
     * @return array
     */
    protected function _Backtick($values) {
        $newValues = [];
        foreach ($values as $key => $value) {
            $newValues['`'.$key.'`'] = $value;
        }
        return $newValues;
    }

    /**
     * Add an entry into the list of default permissions.
     *
     * @param string $type Type of role the permissions should be added for.
     * @param array $permissions The list of permissions to include.
     * @param null|string $junction Type of junction to base the permission on.
     * @param null|int $junctionId Identifier for the specific junction record to base the permission on.
     */
    public function addDefault($type, $permissions, $junction = null, $junctionId = null) {
        if (!array_key_exists($type, $this->DefaultPermissions)) {
            $this->DefaultPermissions[$type] = ['global' => []];
        }

        if ($junction && $junctionId) {
            $junctionKey = "$junction:$junctionId";
            if (!array_key_exists($junctionKey, $this->DefaultPermissions[$type])) {
                $this->DefaultPermissions[$type][$junctionKey] = [];
            }
            $defaults =& $this->DefaultPermissions[$type][$junctionKey];
        } else {
            $defaults =& $this->DefaultPermissions[$type]['global'];
        }

        $defaults = array_merge($defaults, $permissions);
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
     * @param bool $resetDefaults If we already have defaults, should they be discarded?
     */
    public function assignDefaults($resetDefaults = false) {
        if (count($this->DefaultPermissions)) {
            if ($resetDefaults) {
                $this->DefaultPermissions = [];
            } else {
                return;
            }
        }

        $this->addDefault(
            RoleModel::TYPE_GUEST,
            [
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
            ]
        );
        $this->addDefault(
            RoleModel::TYPE_UNCONFIRMED,
            $permissions = [
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Email.View' => 1
            ]
        );
        $this->addDefault(
            RoleModel::TYPE_APPLICANT,
            $permissions = [
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Email.View' => 1
            ]
        );
        $this->addDefault(
            RoleModel::TYPE_MODERATOR,
            $permissions = [
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Curation.Manage' => 1,
                'Garden.Moderation.Manage' => 1,
                'Garden.PersonalInfo.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Profiles.Edit' => 1,
                'Garden.Email.View' => 1
            ]
        );
        $this->addDefault(
            RoleModel::TYPE_ADMINISTRATOR,
            [
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
            ]
        );
        $this->addDefault(
            RoleModel::TYPE_MEMBER,
            [
                'Garden.SignIn.Allow' => 1,
                'Garden.Activity.View' => 1,
                'Garden.Profiles.View' => 1,
                'Garden.Profiles.Edit' => 1,
                'Garden.Email.View' => 1
            ]
        );

        // Allow the ability for other applications and plug-ins to speak up with their own default permissions.
        $this->fireEvent('DefaultPermissions');
    }

    /**
     * Remove the cached permissions for all users.
     */
    public function clearPermissions() {
        static $permissionsCleared = false;

        if (!$permissionsCleared) {
            Gdn::userModel()->clearPermissions();
            $permissionsCleared = true;
        }
    }

    /**
     * Define one or more permissions with default values.
     *
     * @param array $permissionNames
     * @param string $type
     * @param string? $junctionTable
     * @param string? $junctionColumn
     * @throws Exception
     */
    public function define($permissionNames, $type = 'tinyint', $junctionTable = null, $junctionColumn = null) {
        if (!is_array($permissionNames)) {
            trigger_error(__CLASS__.'->'.__METHOD__.' was called with an invalid $PermissionNames parameter.', E_USER_ERROR);
            return;
        }

        $structure = $this->Database->structure();
        $structure->table('Permission');
        $defaultPermissions = [];
        $newColumns = [];

        foreach ($permissionNames as $key => $value) {
            if (is_numeric($key)) {
                // Only got a permissions name with no default.
                $permissionName = $value;
                $defaultPermissions[$permissionName] = 2;
            } else {
                $permissionName = $key;

                if ($value === 0) {
                    // "Off for all"
                    $defaultPermissions[$permissionName] = 2;
                } elseif ($value === 1)
                    // "On for all"
                    $defaultPermissions[$permissionName] = 3;
                elseif (!$structure->columnExists($value) && array_key_exists($value, $permissionNames))
                    // Mapped to an explicitly-defined permission.
                    $defaultPermissions[$permissionName] = $permissionNames[$value] ? 3 : 2;
                else {
                    // Mapped to another permission for which we don't have the value.
                    $defaultPermissions[$permissionName] = "`{$value}`";
                }
            }
            if (!$structure->columnExists($permissionName)) {
                $default = $defaultPermissions[$permissionName];
                $newColumns[$permissionName] = is_numeric($default) ? $default - 2 : $default;
            }

            // Define the column.
            $structure->column($permissionName, $type, 0);

        }
        $structure->set(false, false);

        // Detect an initial permission setup by seeing if our placeholder row exists yet.
        $defaultRow = $this->SQL
            ->select('*')
            ->from('Permission')
            ->where('RoleID', 0)
            ->where('JunctionTable is null')
            ->orderBy('RoleID')
            ->limit(1)
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        // If this is our initial setup, map missing permissions to off.
        // Otherwise we'd be left with placeholders in our final query, which would cause a strict mode failure.
        if (!$defaultRow) {
            $defaultPermissions = array_map(
                function ($value) {
                    // All non-numeric values are converted to "off" flag.
                    return (is_numeric($value)) ? $value : 2;
                },
                $defaultPermissions
            );
        }

        // Set the default permissions on the placeholder.
        $this->SQL
            ->set($this->_backtick($defaultPermissions), '', false)
            ->replace('Permission', [], ['RoleID' => 0, 'JunctionTable' => $junctionTable, 'JunctionColumn' => $junctionColumn], true);

        // Set the default permissions for new columns on all roles.
        if (count($newColumns) > 0) {
            $where = ['RoleID <>' => 0];
            if (!$junctionTable) {
                $where['JunctionTable'] = null;
            } else {
                $where['JunctionTable'] = $junctionTable;
            }

            $this->SQL
                ->set($this->_backtick($newColumns), '', false)
                ->put('Permission', [], $where);
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
     * @param null $roleID
     * @param null $junctionTable
     * @param null $junctionColumn
     * @param null $junctionID
     */
    public function delete($roleID = null, $junctionTable = null, $junctionColumn = null, $junctionID = null) {
        // Build the where clause.
        $where = [];
        if (!is_null($roleID)) {
            $where['RoleID'] = $roleID;
        }
        if (!is_null($junctionTable)) {
            $where['JunctionTable'] = $junctionTable;
            $where['JunctionColumn'] = $junctionColumn;
            $where['JunctionID'] = $junctionID;
        }

        $this->SQL->delete('Permission', $where);

        if (!is_null($roleID)) {
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
            $this->assignDefaults();
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
            $defaultRow = $this->SQL
                ->select('*')
                ->from('Permission')
                ->where('RoleID', 0)
                ->where('JunctionTable is null')
                ->orderBy('RoleID')
                ->limit(1)
                ->get()->firstRow(DATASET_TYPE_ARRAY);

            if (!$defaultRow) {
                throw new Exception(t('No default permission row.'));
            }

            $this->_MergeDisabledPermissions($defaultRow);

            unset(
                $defaultRow['PermissionID'],
                $defaultRow['RoleID'],
                $defaultRow['JunctionTable'],
                $defaultRow['JunctionColumn'],
                $defaultRow['JunctionID']
            );

            $this->RowDefaults = $this->stripPermissions($defaultRow, $defaultRow);
        }

        return $this->RowDefaults;
    }

    /**
     * Get the permissions of a user.
     *
     * If no junction table is specified, will return ONLY non-junction permissions.
     * If you need every permission regardless of junction & suffix, see CachePermissions.
     *
     * @param int $userID Unique identifier for user.
     * @param string $limitToSuffix String permission name must match, starting on right (ex: 'View' would match *.*.View)
     * @param string $junctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string $junctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $junctionTable.
     * @param string $foreignKey Foreign table column to join on.
     * @param int $foreignID Foreign ID to limit join to.
     * @return array Permission records.
     */
    public function getUserPermissions($userID, $limitToSuffix = '', $junctionTable = false, $junctionColumn = false, $foreignKey = false, $foreignID = false) {
        // Get all permissions
        $permissionColumns = $this->permissionColumns($junctionTable, $junctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($permissionColumns as $columnName => $value) {
            if (!empty($limitToSuffix) && substr($columnName, -strlen($limitToSuffix)) != $limitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }            $this->SQL->select('p.`'.$columnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->from('Permission p')
            ->join('UserRole ur', 'p.RoleID = ur.RoleID')
            ->where('ur.UserID', $userID);

        // Either limit to 1 junction or exclude junctions
        if ($junctionTable && $junctionColumn) {
            $this->SQL
                ->select(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'])
                ->groupBy(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID']);
            if ($foreignKey && $foreignID) {
                $this->SQL
                    ->join("$junctionTable j", "j.$junctionColumn = p.JunctionID")
                    ->where("j.$foreignKey", $foreignID);
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
     * @param int $roleID Unique identifier for role.
     * @param string $limitToSuffix String permission name must match, starting on right (ex: 'View' would match *.*.View)
     * @param string $junctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string $junctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $junctionTable.
     * @param string $foreignKey Foreign table column to join on.
     * @param int $foreignID Foreign ID to limit join to.
     * @return array Permission records.
     */
    public function getRolePermissions($roleID, $limitToSuffix = '', $junctionTable = false, $junctionColumn = false, $foreignKey = false, $foreignID = false) {
        // Get all permissions
        $permissionColumns = $this->permissionColumns($junctionTable, $junctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($permissionColumns as $columnName => $value) {
            if (!empty($limitToSuffix) && substr($columnName, -strlen($limitToSuffix)) != $limitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }            $this->SQL->select('p.`'.$columnName.'`', 'MAX');
        }

        // Generic part of query
        $this->SQL->from('Permission p')
            ->where('p.RoleID', $roleID);

        // Either limit to 1 junction or exclude junctions
        if ($junctionTable && $junctionColumn) {
            $this->SQL
                ->select(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'])
                ->groupBy(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID']);
            if ($foreignKey && $foreignID) {
                $this->SQL
                    ->join("$junctionTable j", "j.$junctionColumn = p.JunctionID")
                    ->where("j.$foreignKey", $foreignID);
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
        $applicationManager = Gdn::applicationManager();
        $enabledApplications = $applicationManager->enabledApplications();

        $pluginNamespaces = [];
        foreach (Gdn::pluginManager()->enabledPlugins() as $plugin) {
            if (!array_key_exists('RegisterPermissions', $plugin) || !is_array($plugin['RegisterPermissions'])) {
                continue;
            }
            foreach ($plugin['RegisterPermissions'] as $index => $permissionName) {
                if (is_string($index)) {
                    $permissionName = $index;
                }

                $namespace = substr($permissionName, 0, strrpos($permissionName, '.'));
                $pluginNamespaces[$namespace] = true;
            }
        }

        $result = array_merge(array_keys($enabledApplications), array_keys($pluginNamespaces));
        if (in_array('Dashboard', $result)) {
            $result[] = 'Garden';
        }
        return $result;
    }

    /**
     *
     *
     * @param null $userID
     * @param null $roleID
     * @return array|null
     */
    public function cachePermissions($userID = null, $roleID = null) {
        if (!$userID) {
            $roleID = RoleModel::getDefaultRoles(RoleModel::TYPE_GUEST);
        }

        // Select all of the permission columns.
        $permissionColumns = $this->permissionColumns();
        foreach ($permissionColumns as $columnName => $value) {
            $this->SQL->select('p.`'.$columnName.'`', 'MAX');
        }

        $this->SQL->from('Permission p');

        if (!is_null($roleID)) {
            $this->SQL->where('p.RoleID', $roleID);
        } elseif (!is_null($userID))
            $this->SQL->join('UserRole ur', 'p.RoleID = ur.RoleID')->where('ur.UserID', $userID);

        $this->SQL
            ->select(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'])
            ->groupBy(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID']);

        $result = $this->SQL->get()->resultArray();
        return $result;
    }

    /**
     *
     *
     * @param $where
     * @param null $junctionTable
     * @param string $limitToSuffix
     * @param array $options
     * @return array
     */
    public function getJunctionPermissions($where, $junctionTable = null, $limitToSuffix = '', $options = []) {
        $namespaces = $this->getNamespaces();
        $roleID = val('RoleID', $where, null);
        $junctionID = val('JunctionID', $where, null);
        $limitToDefault = val('LimitToDefault', $options);
        $sQL = $this->SQL;

        // Load all of the default junction permissions.
        $sQL->select('*')
            ->from('Permission p')
            ->where('p.RoleID', 0);

        if (is_null($junctionTable)) {
            $sQL->where('p.JunctionTable is not null');
        } else {
            $sQL->where('p.JunctionTable', $junctionTable);
        }

        // Get the disabled permissions.
        $disabledPermissions = c('Garden.Permissions.Disabled');
        if (is_array($disabledPermissions)) {
            $disabledWhere = [];
            foreach ($disabledPermissions as $tableName => $disabled) {
                if ($disabled) {
                    $disabledWhere[] = $tableName;
                }
            }
            if (count($disabledWhere) > 0) {
                $sQL->whereNotIn('JunctionTable', $disabledWhere);
            }
        }

        $data = $sQL->get()->resultArray();
        $result = [];
        foreach ($data as $row) {
            $junctionTable = $row['JunctionTable'];
            $junctionColumn = $row['JunctionColumn'];
            unset($row['PermissionID'], $row['RoleID'], $row['JunctionTable'], $row['JunctionColumn'], $row['JunctionID']);

            // If the junction column is not the primary key then we must figure out and limit the permissions.
            if ($limitToDefault === false && $junctionColumn != $junctionTable.'ID') {
                $juncIDs = $sQL
                    ->distinct(true)
                    ->select("p.{$junctionTable}ID")
                    ->select("c.$junctionColumn")
                    ->select('p.Name')
                    ->from("$junctionTable c")
                    ->join("$junctionTable p", "c.$junctionColumn = p.{$junctionTable}ID", 'left')
                    ->get()->resultArray();

                foreach ($juncIDs as &$juncRow) {
                    if (!$juncRow[$junctionTable.'ID']) {
                        $juncRow[$junctionTable.'ID'] = -1;
                    }
                }
            }

            if (!empty($roleID) || !empty($junctionID)) {
                // Figure out which columns to select.
                foreach ($row as $permissionName => $value) {
                    if (!($value & 2)) {
                        continue; // permission not applicable to this junction table
                    }
                    if (!empty($limitToSuffix) && substr($permissionName, -strlen($limitToSuffix)) != $limitToSuffix) {
                        continue; // permission not in $LimitToSuffix
                    }
                    if ($index = strpos($permissionName, '.')) {
                        if (!in_array(substr($permissionName, 0, $index), $namespaces) &&
                            !in_array(substr($permissionName, 0, strrpos($permissionName, '.')), $namespaces)
                        ) {
                            continue; // permission not in allowed namespaces
                        }
                    }

                    // If we are viewing the permissions by junction table (ex. Category) then set the default value when a permission row doesn't exist.
                    if (!$roleID && $junctionColumn != $junctionTable.'ID' && val('AddDefaults', $options)) {
                        $defaultValue = $value & 1 ? 1 : 0;
                    } else {
                        $defaultValue = 0;
                    }

                    $sQL->select("p.`$permissionName`, $defaultValue", 'coalesce', $permissionName);
                }

                if (!empty($roleID)) {
                    $roleIDs = (array)$roleID;
                    if (count($roleIDs) === 1) {
                        $roleOn = 'p.RoleID = '.$this->SQL->Database->connection()->quote(reset($roleIDs));
                    } else {
                        $roleIDs = array_map([$this->SQL->Database->connection(), 'quote'], $roleIDs);
                        $roleOn = 'p.RoleID in ('.implode(',', $roleIDs).')';
                    }

                    // Get the permissions for the junction table.
                    $sQL->select('junc.Name')
                        ->select('junc.'.$junctionColumn, '', 'JunctionID')
                        ->from($junctionTable.' junc')
                        ->join('Permission p', "p.JunctionID = junc.$junctionColumn and $roleOn", 'left')
                        ->orderBy('junc.Sort')
                        ->orderBy('junc.Name');

                    if ($limitToDefault) {
                        $sQL->where("junc.{$junctionTable}ID", -1);
                    } elseif (isset($juncIDs)) {
                        $sQL->whereIn("junc.{$junctionTable}ID", array_column($juncIDs, "{$junctionTable}ID"));
                    }

                    $juncData = $sQL->get()->resultArray();
                } elseif (!empty($junctionID)) {
                    // Here we are getting permissions for all roles.
                    $juncData = $sQL->select('r.RoleID, r.Name, r.CanSession')
                        ->from('Role r')
                        ->join('Permission p', "p.RoleID = r.RoleID and p.JunctionTable = '$junctionTable' and p.JunctionColumn = '$junctionColumn' and p.JunctionID = $junctionID", 'left')
                        ->orderBy('r.Sort, r.Name')
                        ->get()->resultArray();
                }
            } else {
                $juncData = [];
            }

            // Add all of the necessary information back to the result.
            foreach ($juncData as $juncRow) {
                $juncRow['JunctionTable'] = $junctionTable;
                $juncRow['JunctionColumn'] = $junctionColumn;
                if (!is_null($junctionID)) {
                    $juncRow['JunctionID'] = $junctionID;
                }
                if ($juncRow['JunctionID'] < 0) {
                    $juncRow['Name'] = sprintf(t('Default %s Permissions'), t('Permission.'.$junctionTable, $junctionTable));
                }

                if (array_key_exists('CanSession', $juncRow)) {
                    if (!$juncRow['CanSession']) {
                        // Remove view permissions.
                        foreach ($juncRow as $permissionName => $value) {
                            if (strpos($permissionName, '.') !== false && strpos($permissionName, '.View') === false) {
                                unset($juncRow[$permissionName]);
                            }
                        }
                    }

                    unset($juncRow['CanSession']);
                }

                if (!$roleID && !$junctionID && array_key_exists(0, $data)) {
                    // Set all of the default permissions for a new role.
                    foreach ($juncRow as $permissionName => $value) {
                        if (val($permissionName, $data[0], 0) & 1) {
                            $juncRow[$permissionName] = 1;
                        }
                    }
                }

                $result[] = $juncRow;
            }
        }
        return $result;
    }

    /**
     * Returns all defined permissions not related to junction tables. Excludes
     * permissions related to applications & plugins that are disabled.
     *
     * @param int|array $roleID The role(s) to get the permissions for.
     * @param string $limitToSuffix An optional suffix to limit the permission names to.
     * @param bool $includeJunction
     * @return array
     */
    public function getPermissions($roleID, $limitToSuffix = '', $includeJunction = true) {
        $roleID = (array)$roleID;
        $result = [];

        $globalPermissions = $this->getGlobalPermissions($roleID, $limitToSuffix);
        $result[] = $globalPermissions;

        $junctionOptions = [];
        if ($includeJunction === false) {
            // If we're skipping junction permissions, just grab the defaults.
            $junctionOptions['LimitToDefault'] = true;
        }
        $junctionPermissions = $this->getJunctionPermissions(
            ['RoleID' => $roleID],
            null,
            $limitToSuffix,
            $junctionOptions
        );
        $result = array_merge($result, $junctionPermissions);

        return $result;
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
                ->select(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID'])
                ->groupBy(['p.JunctionTable', 'p.JunctionColumn', 'p.JunctionID']);

            $permissions = $sql->get()->resultArray();
            $permissions = UserModel::compilePermissions($permissions);
            Gdn::cache()->store($key, $permissions);
        }

        return $permissions;
    }

    /**
     *
     * @param int $roleID
     * @param string $limitToSuffix
     * @param bool $includeJunction
     * @param array|bool $overrides Form values used override current permission flags.
     * @return array
     */
    public function getPermissionsEdit($roleID, $limitToSuffix = '', $includeJunction = true, $overrides = false) {
        $permissions = $this->getPermissions($roleID, $limitToSuffix, $includeJunction);
        $permissions = $this->unpivotPermissions($permissions);

        if (is_array($overrides)) {
            foreach ($permissions as $namespace) {
                foreach ($namespace as $name => $currentPermission) {
                    if (stringBeginsWith('_', $name)) {
                        continue;
                    }
                    $postValue = val('PostValue', $currentPermission);
                    $currentPermission['Value'] = (int)in_array($postValue, $overrides);
                }
            }
        }

        return $permissions;
    }

    /**
     * Get all of the global permissions for one or more roles.
     *
     * @param int|array $roleID The role(s) to get the permissions for.
     * @param string $limitToSuffix Whether or not to limit the permissions to a suffix.
     * @return array
     */
    public function getGlobalPermissions($roleID, $limitToSuffix = '') {
        $roleIDs = (array)$roleID;

        // Get the global permissions.
        $data = $this->SQL
            ->select('*')
            ->from('Permission p')
            ->whereIn('p.RoleID', array_merge($roleIDs, [0]))
            ->where('p.JunctionTable is null')
            ->orderBy('p.RoleID')
            ->get()->resultArray();

        $this->_MergeDisabledPermissions($data);
        $data = Gdn_DataSet::index($data, 'RoleID');

        $defaultRow = $data[0];
        unset($data[0], $defaultRow['RoleID'], $defaultRow['JunctionTable'], $defaultRow['JunctionColumn'], $defaultRow['JunctionID']);
        $defaultRow = $this->stripPermissions($defaultRow, $defaultRow, $limitToSuffix);
        if ($roleID) {
            // When editing a role make sure the default permissions are false so as not to be misleading.
            $defaultRow = array_fill_keys(array_keys($defaultRow), 0);
        }

        foreach ($roleIDs as $iD) {
            if (isset($data[$iD])) {
                $data[$iD] = array_intersect_key($data[$iD], $defaultRow);
            } else {
                $data[$iD] = $defaultRow;
                $data[$iD]['PermissionID'] = null;
            }
        }

        if (count($roleIDs) === 1) {
            return array_pop($data);
        } else {
            return $data;
        }
    }

    /**
     * Take a permission row and strip the global/local permissions from it.
     *
     * @param $row
     * @param $defaultRow
     * @param string $limitToSuffix
     * @return mixed
     */
    public function stripPermissions($row, $defaultRow, $limitToSuffix = '') {
        $namespaces = $this->getNamespaces();

        foreach ($defaultRow as $permissionName => $value) {
            if (in_array($permissionName, ['PermissionID', 'RoleID', 'JunctionTable', 'JunctionColumn', 'JunctionID'])) {
                continue;
            }

            if (!$this->isGlobalPermission($value, $permissionName, $limitToSuffix, $namespaces)) {
                unset($row[$permissionName]);
                continue;
            }

            switch ($defaultRow[$permissionName]) {
                case 3:
                    $row[$permissionName] = 1;
                    break;
                case 2:
                    $row[$permissionName] = 0;
                    break;
            }
        }
        return $row;
    }

    /**
     * Returns whether or not a permission is a global permission.
     *
     * @param $value
     * @param $permissionName
     * @param $limitToSuffix
     * @param $namespaces
     * @return bool
     */
    protected function isGlobalPermission($value, $permissionName, $limitToSuffix, $namespaces) {
        if (!($value & 2)) {
            return false;
        }
        if (!empty($limitToSuffix) && substr($permissionName, -strlen($limitToSuffix)) != $limitToSuffix) {
            return false;
        }
        if ($index = strpos($permissionName, '.')) {
            if (!in_array(substr($permissionName, 0, $index), $namespaces) && !in_array(substr($permissionName, 0, strrpos($permissionName, '.')), $namespaces)) {
                return false;
            }
        }
        return true;
    }

    /** Merge junction permissions with global permissions if they are disabled.
     *
     * @param array $globalPermissions
     * @return void
     */
    protected function _MergeDisabledPermissions(&$globalPermissions) {
        // Get the default permissions for junctions that are disabled.
        $disabledPermissions = c('Garden.Permissions.Disabled');
        if (!$disabledPermissions) {
            return;
        }

        $disabledIn = [];
        foreach ($disabledPermissions as $junctionTable => $disabled) {
            if ($disabled) {
                $disabledIn[] = $junctionTable;
            }
        }

        if (!$disabledIn) {
            return;
        }

        $disabledData = $this->SQL
            ->select('*')
            ->from('Permission p')
            ->where('p.RoleID', 0)
            ->whereIn('p.JunctionTable', $disabledIn)
            ->get()->resultArray();

        $defaultRow =& $globalPermissions[0];

        // Loop through each row and add it's default definition to the global permissions.
        foreach ($disabledData as $permissionRow) {
            foreach ($permissionRow as $columnName => $value) {
                if (in_array($columnName, ['PermissionID', 'RoleID', 'JunctionTable', 'JunctionColumn', 'JunctionID'])) {
                    continue;
                }

                if ($value & 2) {
                    $setting = $value | val($columnName, $defaultRow, 0);
                    setValue($columnName, $defaultRow, $setting);
                }
            }
        }
    }

    /**
     * Get all of the permission columns in the system.
     *
     * @param bool $junctionTable
     * @param bool $junctionColumn
     * @return mixed
     * @throws Exception
     */
    public function permissionColumns($junctionTable = false, $junctionColumn = false) {
        $key = "{$junctionTable}__{$junctionColumn}";

        if (!isset($this->_PermissionColumns[$key])) {
            $sQL = clone $this->SQL;
            $sQL->reset();

            $sQL
                ->select('*')
                ->from('Permission')
                ->limit(1);

            if ($junctionTable !== false && $junctionColumn !== false) {
                $sQL
                    ->where('JunctionTable', $junctionTable)
                    ->where('JunctionColumn', $junctionColumn)
                    ->where('RoleID', 0);
            }

            $cols = $sQL->get()->firstRow(DATASET_TYPE_ARRAY);

            unset($cols['RoleID'], $cols['JunctionTable'], $cols['JunctionColumn'], $cols['JunctionID']);

            $this->_PermissionColumns[$key] = $cols;
        }
        return $this->_PermissionColumns[$key];
    }

    /**
     *
     *
     * @param $permissionName
     * @return string
     */
    public static function permissionNamespace($permissionName) {
        if ($index = strpos($permissionName)) {
            return substr($permissionName, 0, $index);
        }
        return '';
    }

    /**
     *
     *
     * @param $data
     * @param null $overrides
     * @return array
     */
    public function pivotPermissions($data, $overrides = null) {
        // Get all of the columns in the permissions table.
        $schema = $this->SQL->get('Permission', '', '', 1)->firstRow(DATASET_TYPE_ARRAY);
        foreach ($schema as $key => $value) {
            if (strpos($key, '.') !== false) {
                $schema[$key] = 0;
            }
        }
        unset($schema['PermissionID']);
        $schema['RoleID'] = 0;
        $schema['JunctionTable'] = null;
        $schema['JunctionColumn'] = null;
        $schema['JunctionID'] = null;

        $result = [];
        if (is_array($data)) {
            foreach ($data as $setPermission) {
                // Get the parts out of the permission.
                $parts = explode('//', $setPermission);
                if (count($parts) > 1) {
                    // This is a junction permission.
                    $permissionName = $parts[1];
                    $key = $parts[0];
                    $parts = explode('/', $key);
                    $junctionTable = $parts[0];
                    $junctionColumn = $parts[1];
                    $junctionID = val('JunctionID', $overrides, $parts[2]);
                    if (count($parts) >= 4) {
                        $roleID = $parts[3];
                    } else {
                        $roleID = val('RoleID', $overrides, null);
                    }
                } else {
                    // This is a global permission.
                    $permissionName = $parts[0];
                    $key = 'Global';
                    $junctionTable = null;
                    $junctionColumn = null;
                    $junctionID = null;
                    $roleID = val('RoleID', $overrides, null);
                }

                // Check for a row in the result for these permissions.
                if (!array_key_exists($key, $result)) {
                    $newRow = $schema;
                    $newRow['RoleID'] = $roleID;
                    $newRow['JunctionTable'] = $junctionTable;
                    $newRow['JunctionColumn'] = $junctionColumn;
                    $newRow['JunctionID'] = $junctionID;
                    $result[$key] = $newRow;
                }
                $result[$key][$permissionName] = 1;
            }
        }

        return $result;
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
        if (validatePermissionFormat($Permission)) {
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
     * @param array $values The values you want to save. See the Permission table for possible columns.
     * @param bool $saveGlobal Also save a junction permission to the global permissions.
     */
    public function save($values, $saveGlobal = false) {
        // Get the list of columns that are available for permissions.
        $permissionColumns = Gdn::permissionModel()->defineSchema()->fields();
        if (isset($values['Role'])) {
            $permissionColumns['Role'] = true;
        }
        $values = array_intersect_key($values, $permissionColumns);

        // Figure out how to find the existing permission.
        if (array_key_exists('PermissionID', $values)) {
            $where = ['PermissionID' => $values['PermissionID']];
            unset($values['PermissionID']);

            $this->SQL->update('Permission', $this->_Backtick($values), $where)->put();
        } else {
            $where = [];

            if (array_key_exists('RoleID', $values)) {
                $where['RoleID'] = $values['RoleID'];
                unset($values['RoleID']);
            } elseif (array_key_exists('Role', $values)) {
                // Get the RoleID.
                $roleID = $this->SQL->getWhere('Role', ['Name' => $values['Role']])->value('RoleID');
                if (!$roleID) {
                    return;
                }
                $where['RoleID'] = $roleID;
                unset($values['Role']);
            } else {
                $where['RoleID'] = 0; // default role.
            }

            if (array_key_exists('JunctionTable', $values)) {
                $where['JunctionTable'] = $values['JunctionTable'];

                // If the junction table was given then so must the other values.
                if (array_key_exists('JunctionColumn', $values)) {
                    $where['JunctionColumn'] = $values['JunctionColumn'];
                }
                $where['JunctionID'] = $values['JunctionID'];
            } else {
                $where['JunctionTable'] = null; // no junction table.
                $where['JunctionColumn'] = null;
                $where['JunctionID'] = null;
            }

            unset($values['JunctionTable'], $values['JunctionColumn'], $values['JunctionID']);

            $this->SQL->replace('Permission', $this->_Backtick($values), $where, true);

            if ($saveGlobal && !is_null($where['JunctionTable'])) {
                // Save these permissions with the global permissions.
                $where['JunctionTable'] = null; // no junction table.
                $where['JunctionColumn'] = null;
                $where['JunctionID'] = null;

                $this->SQL->replace('Permission', $this->_Backtick($values), $where, true);
            }
        }

        $this->clearPermissions();
    }

    /**
     *
     *
     * @param $permissions
     * @param null $allWhere
     */
    public function saveAll($permissions, $allWhere = null) {
        // Load the permission data corresponding to the where so unset permissions get ovewritten.
        if (is_array($allWhere)) {
            $allPermissions = $this->SQL->getWhere('Permission', $allWhere)->resultArray();
            // Find the permissions that were loaded, but not saved.
            foreach ($allPermissions as $i => $allRow) {
                foreach ($permissions as $saveRow) {
                    if ($allRow['RoleID'] == $saveRow['RoleID']
                        && $allRow['JunctionTable'] == $saveRow['JunctionTable']
                        && $allRow['JunctionID'] == $saveRow['JunctionID']
                    ) {
                        unset($allPermissions[$i]); // saving handled already.
                        break;
                    }
                }
            }
            // Make all permission false that need to be saved here.
            foreach ($allPermissions as &$allRow) {
                foreach ($allRow as $name => $value) {
                    if (strpos($name, '.') !== false) {
                        $allRow[$name] = 0;
                    }
                }
            }
            if (count($allPermissions) > 0) {
                $permissions = array_merge($permissions, $allPermissions);
            }
        }

        foreach ($permissions as $row) {
            $this->save($row);
        }

        // TODO: Clear the permissions for rows that aren't here.
    }

    /**
     * Reset permissions for all roles, based on the value in their Type column.
     *
     * @param string $type Role type to limit the updates to.
     */
    public static function resetAllRoles($type = null) {
        // Retrieve an array containing all available roles.
        $roleModel = new RoleModel();
        if ($type) {
            $result = $roleModel->getByType($type)->resultArray();
            $roles = array_column($result, 'Name', 'RoleID');
        } else {
            $roles = $roleModel->getArray();
        }

        // Iterate through our roles and reset their permissions.
        $permissions = Gdn::permissionModel();
        foreach ($roles as $roleID => $role) {
            $permissions->resetRole($roleID);
        }
    }

    /**
     * Reset permissions for a role, based on the value in its Type column.
     *
     * @param int $roleId ID of the role to reset permissions for.
     * @throws Exception
     */
    public function resetRole($roleId) {
        // Grab the value of Type for this role.
        $roleType = $this->SQL->getWhere('Role', ['RoleID' => $roleId])->value('Type');

        if ($roleType == '') {
            $roleType = RoleModel::TYPE_MEMBER;
        }

        $defaults = $this->getDefaults();
        $rowDefaults = $this->getRowDefaults();

        $resetValues = array_fill_keys(array_keys($rowDefaults), 0);

        if (array_key_exists($roleType, $defaults)) {
            foreach ($defaults[$roleType] as $specificity => $permissions) {
                $permissions['RoleID'] = $roleId;
                $permissions = array_merge($resetValues, $permissions);

                if (strpos($specificity, ':')) {
                    list($junction, $junctionId) = explode(':', $specificity);
                    if ($junction && $junctionId) {
                        switch ($junction) {
                            case 'Category':
                            default:
                                $permissions['JunctionTable'] = $junction;
                                $permissions['JunctionColumn'] = 'PermissionCategoryID';
                                $permissions['JunctionID'] = $junctionId;
                        }
                    }
                }

                $this->save($permissions);
            }
        }
    }

    /**
     * Split a permission name into its constituant parts.
     *
     * @param string $permissionName The name of the permission.
     * @return array The split permission in the form array(Namespace, Permission,Suffix).
     */
    public static function splitPermission($permissionName) {
        $i = strpos($permissionName, '.');
        $j = strrpos($permissionName, '.');

        if ($i !== false) { // $j must also not be false
            return [substr($permissionName, 0, $i), substr($permissionName, $i + 1, $j - $i - 1), substr($permissionName, $j + 1)];
        } else {
            return [$permissionName, '', ''];
        }
    }

    /**
     * Joins the query to a permission junction table and limits the results accordingly.
     *
     * @param Gdn_SQLDriver $sQL The SQL driver to add the permission to.
     * @param mixed $permissions The permission name (or array of names) to use when limiting the query.
     * @param string $foreignAlias The alias of the table to join to (ie. Category).
     * @param string $foreignColumn The primary key column name of $junctionTable (ie. CategoryID).
     * @param string $junctionTable
     * @param string $junctionColumn
     */
    public function sQLPermission($sQL, $permissions, $foreignAlias, $foreignColumn, $junctionTable = '', $junctionColumn = '') {
        $session = Gdn::session();

        // Figure out the junction table if necessary.
        if (!$junctionTable && stringEndsWith($foreignColumn, 'ID')) {
            $junctionTable = substr($foreignColumn, 0, -2);
        }

        // Check to see if the permission is disabled.
        if (c('Garden.Permission.Disabled.'.$junctionTable)) {
            if (!$session->checkPermission($permissions)) {
                $sQL->where('1', '0', false, false);
            }
        } elseif ($session->UserID <= 0 || (is_object($session->User) && $session->User->Admin != '1')) {
            $sQL->distinct()
                ->join('Permission _p', '_p.JunctionID = '.$foreignAlias.'.'.$foreignColumn, 'inner')
                ->join('UserRole _ur', '_p.RoleID = _ur.RoleID', 'inner')
                ->beginWhereGroup()
                ->where('_ur.UserID', $session->UserID);

            if (!is_array($permissions)) {
                $permissions = [$permissions];
            }

            $sQL->beginWhereGroup();
            foreach ($permissions as $permission) {
                $sQL->where('_p.`'.$permission.'`', 1);
            }
            $sQL->endWhereGroup();
        } else {
            // Force this method to play nice in case it is used in an or clause
            // (ie. it returns true in a sql sense by doing 1 = 1)
            $sQL->where('1', '1', false, false);
        }

        return $sQL;
    }

    /**
     *
     *
     * @param $permissions
     * @param bool $includeRole
     * @return array
     */
    public function unpivotPermissions($permissions, $includeRole = false) {
        $result = [];
        foreach ($permissions as $row) {
            $this->_UnpivotPermissionsRow($row, $result, $includeRole);
        }
        return $result;
    }

    /**
     *
     *
     * @param $names
     */
    public function undefine($names) {
        $names = (array)$names;
        $st = $this->Database->structure();
        $st->table('Permission');

        foreach ($names as $name) {
            if ($st->columnExists($name)) {
                $st->dropColumn($name);
            }
        }
        $st->reset();
    }

    /**
     *
     *
     * @param $row
     * @param $result
     * @param bool $includeRole
     */
    protected function _UnpivotPermissionsRow($row, &$result, $includeRole = false) {
        $globalName = val('Name', $row);

        // Loop through each permission in the row and place them in the correct place in the grid.
        foreach ($row as $permissionName => $value) {
            list($namespace, $name, $suffix) = self::splitPermission($permissionName);
            if (empty($name)) {
                continue; // was some other column
            }
            if ($globalName) {
                $namespace = $globalName;
            }

            if (array_key_exists('JunctionTable', $row) && ($junctionTable = $row['JunctionTable'])) {
                $key = "$junctionTable/{$row['JunctionColumn']}/{$row['JunctionID']}".($includeRole ? '/'.$row['RoleID'] : '');
            } else {
                $key = '_'.$namespace;
            }


            // Check to see if the namespace is in the result.
            if (!array_key_exists($key, $result)) {
                $result[$key] = ['_Columns' => [], '_Rows' => [], '_Info' => ['Name' => $namespace]];
            }
            $namespaceArray = &$result[$key];

            // Add the names to the columns and rows.
            $namespaceArray['_Columns'][$suffix] = true;
            $namespaceArray['_Rows'][$name] = true;

            // Augment the value depending on the junction ID.
            if (substr($key, 0, 1) === '_') {
                $postValue = $permissionName;
            } else {
                $postValue = $key.'//'.$permissionName;
            }

            $namespaceArray[$name.'.'.$suffix] = ['Value' => $value, 'PostValue' => $postValue];
        }
    }

    /**
     * Get the namespaces from enabled permissions.
     *
     * @return array Returns an array of permission prefixes.
     */
    public function getNamespaces() {
        if (!isset($this->namespaces)) {
            $this->namespaces = $this->getAllowedPermissionNamespaces();
        }
        $namespaces = $this->namespaces;
        return $namespaces;
    }
}

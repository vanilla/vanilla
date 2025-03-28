<?php
/**
 * Permission model.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Web\Exception\NotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Dashboard\Models\PermissionJunctionModelInterface;
use Vanilla\Logger;
use Vanilla\Models\ModelCache;
use Vanilla\Permissions;
use Vanilla\Utility\DebugUtils;

/**
 * Handles permission data.
 */
class PermissionModel extends Gdn_Model implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array Default role permissions. */
    protected $DefaultPermissions = [];

    /** @var array Default row permission values. */
    protected $RowDefaults = [];

    /** @var array Permission columns. */
    protected $_PermissionColumns = [];

    /** @var array Permission namespaces from enabled addons. */
    private $namespaces;

    /** @var PermissionJunctionModelInterface[] $junctionModels */
    protected $junctionModels = [];

    /** @var ModelCache */
    private $modelCache;

    /**
     * Class constructor. Defines the related database table name.
     *
     */
    public function __construct()
    {
        parent::__construct("Permission");
        $this->modelCache = new ModelCache("permissions", \Gdn::cache());
    }

    /**
     * Invalid the all record cache.
     */
    protected function onUpdate()
    {
        parent::onUpdate();
        $this->modelCache->invalidateAll();
    }

    /**
     * Create the permissions.
     *
     * @return Permissions
     */
    public function createPermissionInstance(): Permissions
    {
        $permissions = new Permissions();

        foreach ($this->junctionModels as $junctionModel) {
            $aliases = $junctionModel->getJunctionAliases();
            if ($aliases !== null) {
                $permissions->addJunctionAliases($aliases);
            }

            $junctions = $junctionModel->getJunctions();
            if ($junctions !== null) {
                $permissions->addJunctions($junctions);
            }
        }

        $permissions->setValidPermissionNames($this->getAllPermissions());

        return $permissions;
    }

    /**
     * Returns a list of all permissions.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $this->defineSchema();
        $schema = $this->Schema;
        $fields = $schema->fields();
        $fieldName = [];
        foreach ($fields as $field => $name) {
            if (str_contains($field, ".")) {
                $fieldName[] = $field;
            }
        }
        return $fieldName;
    }

    /**
     * Get a mapping of all junction tables + JunctionIDs.
     *
     * @return array
     *
     * @example
     * [
     *      'Category' => [1, 49, 100],
     *      'knowledgeBase' => [53, 60, 100],
     * ]
     */
    public function getAllJunctionTablesAndIDs(): array
    {
        $result = $this->modelCache->getCachedOrHydrate(["junctionTablesAndIDs" => true], function () {
            $rows = $this->createSql()
                ->select(["p.JunctionTable", "p.JunctionID"])
                ->where("p.JunctionTable IS NOT NULL")
                ->where("p.JunctionID IS NOT NULL")
                ->whereNotIn("p.JunctionID", [Permissions::GLOBAL_JUNCTION_ID])
                ->get("Permission p")
                ->resultArray();

            $junctions = [];
            foreach ($rows as $row) {
                $junctionTable = $row["JunctionTable"];
                $junctionID = $row["JunctionID"];
                if (!array_key_exists($junctionTable, $junctions)) {
                    $junctions[$junctionTable] = [];
                }

                if (!in_array($junctionID, $junctions[$junctionTable], true)) {
                    $junctions[$junctionTable][] = $junctionID;
                }
            }

            return $junctions;
        });

        return $result;
    }

    /**
     * Add a model related to specific junction table
     *
     * @param string $junctionTable
     * @param PermissionJunctionModelInterface $model
     */
    public function addJunctionModel(string $junctionTable, PermissionJunctionModelInterface $model)
    {
        $this->junctionModels[$junctionTable] = $model;
    }

    /**
     *
     *
     * @param $values
     * @return array
     */
    protected function _Backtick($values)
    {
        $newValues = [];
        foreach ($values as $key => $value) {
            $newValues["`" . $key . "`"] = $value;
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
    public function addDefault($type, $permissions, $junction = null, $junctionId = null)
    {
        if (!array_key_exists($type, $this->DefaultPermissions)) {
            $this->DefaultPermissions[$type] = ["global" => []];
        }

        if ($junction && $junctionId) {
            $junctionKey = "$junction:$junctionId";
            if (!array_key_exists($junctionKey, $this->DefaultPermissions[$type])) {
                $this->DefaultPermissions[$type][$junctionKey] = [];
            }
            $defaults = &$this->DefaultPermissions[$type][$junctionKey];
        } else {
            $defaults = &$this->DefaultPermissions[$type]["global"];
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
    public static function addPermissions($perms1, $perms2)
    {
        // Union the global permissions.
        $result = array_unique(array_merge(array_filter($perms1, "is_string"), array_filter($perms2, "is_string")));

        // Union the junction permissions.
        $junctions1 = array_filter($perms1, "is_array");
        $junctions2 = array_filter($perms2, "is_array");
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
    public function assignDefaults($resetDefaults = false)
    {
        if (count($this->DefaultPermissions)) {
            if ($resetDefaults) {
                $this->DefaultPermissions = [];
            } else {
                return;
            }
        }

        $this->addDefault(RoleModel::TYPE_GUEST, [
            "Garden.Activity.View" => 1,
            "Garden.Profiles.View" => 0,
            "Garden.Uploads.Add" => 0,
            "Garden.Reactions.View" => 0,
        ]);
        $this->addDefault(RoleModel::TYPE_UNCONFIRMED, [
            "Garden.SignIn.Allow" => 1,
            "Garden.Activity.View" => 1,
            "Garden.Profiles.View" => 1,
            "Garden.Email.View" => 1,
            "Garden.Reactions.View" => 1,
            "Garden.Uploads.Add" => 0,
        ]);
        $this->addDefault(RoleModel::TYPE_APPLICANT, [
            "Garden.SignIn.Allow" => 1,
            "Garden.Activity.View" => 1,
            "Garden.Profiles.View" => 1,
            "Garden.Email.View" => 1,
            "Garden.Reactions.View" => 1,
            "Garden.Uploads.Add" => 0,
        ]);
        $this->addDefault(RoleModel::TYPE_MODERATOR, [
            "Garden.SignIn.Allow" => 1,
            "Garden.Activity.View" => 1,
            "Garden.Curation.Manage" => 1,
            "Garden.Moderation.Manage" => 1,
            "Garden.Reactions.View" => 1,
            "Garden.PersonalInfo.View" => 1,
            "Garden.Profiles.View" => 1,
            "Garden.Profiles.Edit" => 1,
            "Garden.Email.View" => 1,
            "Garden.Uploads.Add" => 1,
            "Reactions.Flag.Add" => 1,
            "Reactions.Positive.Add" => 1,
            "Reactions.Negative.Add" => 1,
        ]);
        $this->addDefault(RoleModel::TYPE_ADMINISTRATOR, [
            "Garden.SignIn.Allow" => 1,
            "Garden.Settings.View" => 1,
            "Garden.Settings.Manage" => 1,
            "Garden.Community.Manage" => 1,
            "Garden.Users.Add" => 1,
            "Garden.Users.Edit" => 1,
            "Garden.Users.Delete" => 1,
            "Garden.Users.Approve" => 1,
            "Garden.Activity.Delete" => 1,
            "Garden.Activity.View" => 1,
            "Garden.Messages.Manage" => 1,
            "Garden.PersonalInfo.View" => 1,
            "Garden.InternalInfo.View" => 1,
            "Garden.Profiles.View" => 1,
            "Garden.Profiles.Edit" => 1,
            "Garden.AdvancedNotifications.Allow" => 1,
            "Garden.Email.View" => 1,
            "Garden.Username.Edit" => 1,
            "Garden.Curation.Manage" => 1,
            "Garden.Moderation.Manage" => 1,
            "Garden.Reactions.View" => 1,
            "Garden.Uploads.Add" => 1,
            "Reactions.Flag.Add" => 1,
            "Reactions.Positive.Add" => 1,
            "Reactions.Negative.Add" => 1,
        ]);
        $this->addDefault(RoleModel::TYPE_MEMBER, [
            "Garden.SignIn.Allow" => 1,
            "Garden.Activity.View" => 1,
            "Garden.Profiles.View" => 1,
            "Garden.Profiles.Edit" => 1,
            "Garden.Email.View" => 1,
            "Garden.Uploads.Add" => 1,
            "Garden.Reactions.View" => 1,
            "Reactions.Flag.Add" => 1,
            "Reactions.Positive.Add" => 1,
            "Reactions.Negative.Add" => 1,
        ]);

        // Allow the ability for other applications and plug-ins to speak up with their own default permissions.
        $this->fireEvent("DefaultPermissions");
    }

    /**
     * Remove the cached permissions for all users.
     */
    public function clearPermissions()
    {
        $this->onUpdate();
        Gdn::userModel()->clearPermissions();
        foreach ($this->junctionModels as $table => $model) {
            $model->onPermissionChange();
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
    public function define($permissionNames, $type = "tinyint", $junctionTable = null, $junctionColumn = null)
    {
        if (!is_array($permissionNames)) {
            trigger_error(
                __CLASS__ . "->" . __METHOD__ . ' was called with an invalid $PermissionNames parameter.',
                E_USER_ERROR
            );
            return;
        }

        $structure = $this->Database->structure();
        $structure->table("Permission");
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
                } elseif ($value === 1) {
                    // "On for all"
                    $defaultPermissions[$permissionName] = 3;
                } elseif (!$structure->columnExists($value) && array_key_exists($value, $permissionNames)) {
                    // Mapped to an explicitly-defined permission.
                    $defaultPermissions[$permissionName] = $permissionNames[$value] ? 3 : 2;
                } else {
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
            ->select("*")
            ->from("Permission")
            ->where("RoleID", 0)
            ->where("JunctionTable is null")
            ->orderBy("RoleID")
            ->limit(1)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        // If this is our initial setup, map missing permissions to off.
        // Otherwise we'd be left with placeholders in our final query, which would cause a strict mode failure.
        if (!$defaultRow) {
            $defaultPermissions = array_map(function ($value) {
                // All non-numeric values are converted to "off" flag.
                return is_numeric($value) ? $value : 2;
            }, $defaultPermissions);
        }

        // Set the default permissions on the placeholder.
        $this->SQL
            ->set($this->_backtick($defaultPermissions), "", false)
            ->replace(
                "Permission",
                [],
                ["RoleID" => 0, "JunctionTable" => $junctionTable, "JunctionColumn" => $junctionColumn],
                true
            );

        // Set the default permissions for new columns on all roles.
        if (count($newColumns) > 0) {
            $where = ["RoleID <>" => 0];
            if (!$junctionTable) {
                $where["JunctionTable"] = null;
            } else {
                $where["JunctionTable"] = $junctionTable;
            }

            $this->SQL->set($this->_backtick($newColumns), "", false)->put("Permission", [], $where);
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
     * For compatibility with `Gdn_Model` only.
     *
     * @param int|null $where
     * @param string|null $options
     * @param string|null $junctionColumn
     * @param null $junctionID
     * @return false|int|void
     */
    public function delete($where = null, $options = null, $junctionColumn = null, $junctionID = null)
    {
        $this->deletePermissions($where, $options, $junctionColumn, $junctionID);
    }

    /**
     * Delete specific permission items.
     *
     * @param int|null $roleID
     * @param string|null $junctionTable
     * @param string|null $junctionColumn
     * @param int|null $junctionID
     */
    public function deletePermissions($roleID = null, $junctionTable = null, $junctionColumn = null, $junctionID = null)
    {
        // Build the where clause.
        $where = [];
        if (!is_null($roleID)) {
            $where["RoleID"] = $roleID;
        }
        if (!is_null($junctionTable)) {
            $where["JunctionTable"] = $junctionTable;
            $where["JunctionColumn"] = $junctionColumn;
            $where["JunctionID"] = $junctionID;
        }

        $this->SQL->delete("Permission", $where);

        if (!is_null($roleID)) {
            // Rebuild the permission cache.
        }
    }

    /**
     * Grab the list of default permissions by role type
     *
     * @return array List of permissions, grouped by role type
     */
    public function getDefaults()
    {
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
    public function getRowDefaults()
    {
        if (empty($this->RowDefaults)) {
            $defaultRow = $this->SQL
                ->select("*")
                ->from("Permission")
                ->where("RoleID", 0)
                ->where("JunctionTable is null")
                ->orderBy("RoleID")
                ->limit(1)
                ->get()
                ->firstRow(DATASET_TYPE_ARRAY);

            if (!$defaultRow) {
                throw new Exception(t("No default permission row."));
            }

            $this->_MergeDisabledPermissions($defaultRow);

            unset(
                $defaultRow["PermissionID"],
                $defaultRow["RoleID"],
                $defaultRow["JunctionTable"],
                $defaultRow["JunctionColumn"],
                $defaultRow["JunctionID"]
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
     * @param string|false $junctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string|false $junctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $junctionTable.
     * @param string|false $foreignKey Foreign table column to join on.
     * @param int|false $foreignID Foreign ID to limit join to.
     * @return array Permission records.
     * @throws Exception
     */
    public function getUserPermissions(
        $userID,
        $limitToSuffix = "",
        $junctionTable = false,
        $junctionColumn = false,
        $foreignKey = false,
        $foreignID = false
    ) {
        // Get all permissions
        $permissionColumns = $this->permissionColumns($junctionTable, $junctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($permissionColumns as $columnName => $value) {
            if (!empty($limitToSuffix) && substr($columnName, -strlen($limitToSuffix)) !== $limitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }
            $this->SQL->select("p.`" . $columnName . "`", "MAX");
        }

        // Generic part of query
        $this->SQL
            ->from("Permission p")
            ->join("UserRole ur", "p.RoleID = ur.RoleID")
            ->where("ur.UserID", $userID);

        // Either limit to 1 junction or exclude junctions
        if ($junctionTable && $junctionColumn) {
            $this->SQL
                ->select(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"])
                ->groupBy(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"]);
            if ($foreignKey && $foreignID) {
                $this->SQL
                    ->join("$junctionTable j", "j.$junctionColumn = p.JunctionID")
                    ->where("p.JunctionTable", $junctionTable)
                    ->where("j.$foreignKey", $foreignID);
            }
        } else {
            $this->SQL->where("p.JunctionTable is null");
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
     * @param string|false $junctionTable Optionally limit returned permissions to 1 junction (ex: 'Category').
     * @param string|false $junctionColumn Column to join junction table on (ex: 'CategoryID'). Required if using $junctionTable.
     * @param string|false $foreignKey Foreign table column to join on.
     * @param int|false $foreignID Foreign ID to limit join to.
     * @return array Permission records.
     * @throws Exception
     */
    public function getRolePermissions(
        $roleID,
        $limitToSuffix = "",
        $junctionTable = false,
        $junctionColumn = false,
        $foreignKey = false,
        $foreignID = false
    ) {
        // Get all permissions
        $permissionColumns = $this->permissionColumns($junctionTable, $junctionColumn);

        // Select any that match $LimitToSuffix
        foreach ($permissionColumns as $columnName => $value) {
            if (!empty($limitToSuffix) && substr($columnName, -strlen($limitToSuffix)) != $limitToSuffix) {
                continue; // permission not in $LimitToSuffix
            }
            $this->SQL->select("p.`" . $columnName . "`", "MAX");
        }

        // Generic part of query
        $this->SQL->from("Permission p")->where("p.RoleID", $roleID);

        // Either limit to 1 junction or exclude junctions
        if ($junctionTable && $junctionColumn) {
            $this->SQL
                ->select(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"])
                ->groupBy(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"]);
            if ($foreignKey && $foreignID) {
                $this->SQL
                    ->join("$junctionTable j", "j.$junctionColumn = p.JunctionID")
                    ->where("j.$foreignKey", $foreignID);
            }
        } else {
            $this->SQL->where("p.JunctionTable is null");
        }

        return $this->SQL->get()->resultArray();
    }

    /**
     * Returns a complete list of all enabled applications & plugins. This list
     * can act as a namespace list for permissions.
     *
     * @return array
     */
    public function getAllowedPermissionNamespaces()
    {
        $applicationManager = Gdn::applicationManager();
        $enabledApplications = $applicationManager->enabledApplications();

        $pluginNamespaces = [];
        foreach (Gdn::pluginManager()->enabledPlugins() as $plugin) {
            if (!array_key_exists("RegisterPermissions", $plugin) || !is_array($plugin["RegisterPermissions"])) {
                continue;
            }
            foreach ($plugin["RegisterPermissions"] as $index => $permissionName) {
                if (is_string($index)) {
                    $permissionName = $index;
                }

                $namespace = substr($permissionName, 0, strrpos($permissionName, "."));
                $pluginNamespaces[$namespace] = true;
            }
        }

        $result = array_merge(array_keys($enabledApplications), array_keys($pluginNamespaces));
        if (in_array("Dashboard", $result)) {
            $result[] = "Garden";
            $result[] = "Reactions";
        }
        return $result;
    }

    /**
     * Get user permissions.
     *
     * @param ?int $userID
     * @param ?int $roleID
     * @return array
     * @deprecated Use PermissionModel::getPermissionsByUser() or PermissionModel::getPermissionsByRole() instead.
     */
    public function cachePermissions($userID = null, $roleID = null)
    {
        if (!$userID) {
            $roleID = RoleModel::getDefaultRoles(RoleModel::TYPE_GUEST);
            $permissions = $this->getPermissionsByRole($roleID);
            return $permissions;
        }

        if (empty($roleID)) {
            $roleID = GDN::UserModel()->getRoleIDs($userID);
        } else {
            $roleID = (array) $roleID;
        }
        $permissions = $this->getPermissionsByRole(...$roleID);
        return $permissions;
    }

    /**
     * Get the permissions of a particular user.
     *
     * @param int $userID
     * @return array Returns an array suitable for Permissions::setPermission().
     */
    public function getPermissionsByUser(int $userID): array
    {
        if ($userID === UserModel::GUEST_USER_ID) {
            $roleIDs = RoleModel::getDefaultRoles(RoleModel::TYPE_GUEST);
        } else {
            $roleIDs = Gdn::UserModel()->getRoleIDs($userID);
        }
        $permissions = $this->getPermissionsByRole(...$roleIDs);
        return $permissions;
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
    public function getJunctionPermissions($where, $junctionTable = null, $limitToSuffix = "", $options = [])
    {
        $namespaces = $this->getNamespaces();
        $roleID = val("RoleID", $where, null);
        $junctionID = val("JunctionID", $where, null);
        $limitToDefault = val("LimitToDefault", $options);
        $sQL = $this->SQL;

        // Load all of the default junction permissions.
        $sQL->select("*")
            ->from("Permission p")
            ->where("p.RoleID", 0);

        if (is_null($junctionTable)) {
            $sQL->where("p.JunctionTable is not null");
        } else {
            $sQL->where("p.JunctionTable", $junctionTable);
        }

        // Get the disabled permissions.
        $disabledPermissions = c("Garden.Permissions.Disabled");
        if (is_array($disabledPermissions)) {
            $disabledWhere = [];
            foreach ($disabledPermissions as $tableName => $disabled) {
                if ($disabled) {
                    $disabledWhere[] = $tableName;
                }
            }
            $sQL->whereNotIn("JunctionTable", $disabledWhere);
        }

        $data = $sQL->get()->resultArray();
        $result = [];
        foreach ($data as $row) {
            $junctionTable = $row["JunctionTable"];
            $junctionColumn = $row["JunctionColumn"];
            unset(
                $row["PermissionID"],
                $row["RoleID"],
                $row["JunctionTable"],
                $row["JunctionColumn"],
                $row["JunctionID"]
            );

            unset($juncIDs);
            // If the junction column is not the primary key then we must figure out and limit the permissions.
            if ($limitToDefault === false && $junctionColumn != $junctionTable . "ID") {
                $juncIDs = $sQL
                    ->distinct(true)
                    ->select("p.{$junctionTable}ID")
                    ->select("c.$junctionColumn")
                    ->select("p.Name")
                    ->from("$junctionTable c")
                    ->join("$junctionTable p", "c.$junctionColumn = p.{$junctionTable}ID", "left")
                    ->get()
                    ->resultArray();

                foreach ($juncIDs as &$juncRow) {
                    if (!$juncRow[$junctionTable . "ID"]) {
                        $juncRow[$junctionTable . "ID"] = -1;
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
                    if ($index = strpos($permissionName, ".")) {
                        if (
                            !in_array(substr($permissionName, 0, $index), $namespaces) &&
                            !in_array(substr($permissionName, 0, strrpos($permissionName, ".")), $namespaces)
                        ) {
                            continue; // permission not in allowed namespaces
                        }
                    }

                    // If we are viewing the permissions by junction table (ex. Category) then set the default value when a permission row doesn't exist.
                    if (!$roleID && $junctionColumn != $junctionTable . "ID" && val("AddDefaults", $options)) {
                        $defaultValue = $value & 1 ? 1 : 0;
                    } else {
                        $defaultValue = 0;
                    }

                    $sQL->select("p.`$permissionName`, $defaultValue", "coalesce", $permissionName);
                }

                if (!empty($roleID)) {
                    $roleIDs = (array) $roleID;
                    if (count($roleIDs) === 1) {
                        $roleOn = "p.RoleID = " . $this->SQL->Database->connection()->quote(reset($roleIDs));
                    } else {
                        $roleIDs = array_map([$this->SQL->Database->connection(), "quote"], $roleIDs);
                        $roleOn = "p.RoleID in (" . implode(",", $roleIDs) . ")";
                    }

                    $junctionColumnsOn =
                        "p.JunctionColumn = " . $this->SQL->Database->connection()->quote($junctionColumn);
                    $junctionTablesOn =
                        "p.JunctionTable = " . $this->SQL->Database->connection()->quote($junctionTable);
                    // Get the permissions for the junction table.
                    $sQL->select("junc.Name")
                        ->select("junc." . $junctionColumn, "", "JunctionID")
                        ->from($junctionTable . " junc")
                        ->join(
                            "Permission p",
                            "p.JunctionID = junc.$junctionColumn and $roleOn and $junctionColumnsOn and $junctionTablesOn",
                            "left"
                        )
                        ->orderBy("junc.Sort")
                        ->orderBy("junc.Name");

                    if ($limitToDefault) {
                        $sQL->where("junc.{$junctionTable}ID", -1);
                    } elseif (isset($juncIDs)) {
                        $sQL->whereIn("junc.{$junctionTable}ID", array_column($juncIDs, "{$junctionTable}ID"));
                    }

                    $juncData = $sQL->get()->resultArray();
                } elseif (!empty($junctionID)) {
                    // Here we are getting permissions for all roles.
                    $juncData = $sQL
                        ->select("r.RoleID, r.Name, r.CanSession")
                        ->from("Role r")
                        ->join(
                            "Permission p",
                            "p.RoleID = r.RoleID and p.JunctionTable = '$junctionTable' and p.JunctionColumn = '$junctionColumn' and p.JunctionID = $junctionID",
                            "left"
                        )
                        ->orderBy("r.Sort, r.Name")
                        ->get()
                        ->resultArray();
                }
            } else {
                $juncData = [];
            }

            // Add all of the necessary information back to the result.
            foreach ($juncData as $juncRow) {
                $juncRow["JunctionTable"] = $junctionTable;
                $juncRow["JunctionColumn"] = $junctionColumn;
                if (!is_null($junctionID)) {
                    $juncRow["JunctionID"] = $junctionID;
                }
                if ($juncRow["JunctionID"] < 0) {
                    $juncRow["Name"] = sprintf(
                        t("Default %s Permissions"),
                        t("Permission." . $junctionTable, $junctionTable)
                    );
                }

                if (array_key_exists("CanSession", $juncRow)) {
                    if (!$juncRow["CanSession"]) {
                        // Remove view permissions.
                        foreach ($juncRow as $permissionName => $value) {
                            if (strpos($permissionName, ".") !== false && strpos($permissionName, ".View") === false) {
                                unset($juncRow[$permissionName]);
                            }
                        }
                    }

                    unset($juncRow["CanSession"]);
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
    public function getPermissions($roleID, $limitToSuffix = "", $includeJunction = true)
    {
        $roleID = (array) $roleID;
        $globalPermissions = $this->getGlobalPermissions($roleID, $limitToSuffix);

        $junctionOptions = [];
        if ($includeJunction === false) {
            // If we're skipping junction permissions, just grab the defaults.
            $junctionOptions["LimitToDefault"] = true;
        }
        $junctionPermissions = $this->getJunctionPermissions(
            ["RoleID" => $roleID],
            null,
            $limitToSuffix,
            $junctionOptions
        );
        $result = array_merge($globalPermissions, $junctionPermissions);

        return $result;
    }

    /**
     * Get the permissions for one or more roles from cache.
     *
     * @param array $roleID The role to get the permissions for.
     * @return array Returns a permission array suitable for use in a session.
     */
    public function getPermissionsByRole(...$roleID): array
    {
        asort($roleID);
        $permissions = $this->modelCache->getCachedOrHydrate(
            [
                "permsByRole" => Gdn::userModel()->getPermissionsIncrement(),
                "roleIDs" => $roleID,
            ],
            function () use ($roleID) {
                $permissions = $this->getPermissionsByRoleDb(...$roleID);
                $permissions = UserModel::compilePermissions($permissions);
                return $permissions;
            }
        );

        return $permissions;
    }

    /**
     * Get role permission from the database.
     *
     * @param array $roleID
     * @return array $permissions
     */
    protected function getPermissionsByRoleDb(...$roleID)
    {
        $sql = $this->createSql();
        // Select all permission columns.
        $permissionColumns = $this->getAllPermissions();
        foreach ($permissionColumns as $columnName) {
            $sql->select("p.`" . $columnName . "`", "MAX");
        }

        $sql->from("Permission p")
            ->where("p.RoleID", $roleID)
            ->select(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"])
            ->groupBy(["p.JunctionTable", "p.JunctionColumn", "p.JunctionID"]);

        $permissions = $sql->get()->resultArray();
        return $permissions;
    }

    /**
     * Get permissions for edit.
     *
     * @param int $roleID
     * @param string $limitToSuffix
     * @param bool $includeJunction
     * @param array|bool $overrides Form values used override current permission flags.
     * @return array
     */
    public function getPermissionsEdit($roleID, $limitToSuffix = "", $includeJunction = true, $overrides = false)
    {
        $permissions = $this->getPermissions($roleID, $limitToSuffix, $includeJunction);
        $permissions = $this->unpivotPermissions($permissions);

        if (is_array($overrides)) {
            foreach ($permissions as $namespace) {
                foreach ($namespace as $name => $currentPermission) {
                    if (stringBeginsWith("_", $name)) {
                        continue;
                    }
                    $postValue = val("PostValue", $currentPermission);
                    $currentPermission["Value"] = (int) in_array($postValue, $overrides);
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
    public function getGlobalPermissions($roleID, $limitToSuffix = "")
    {
        $roleIDs = (array) $roleID;

        // Get the global permissions.
        $data = $this->SQL
            ->select("*")
            ->from("Permission p")
            ->whereIn("p.RoleID", array_merge($roleIDs, [0]))
            ->where("p.JunctionTable is null")
            ->orderBy("p.RoleID")
            ->get()
            ->resultArray();

        $this->_MergeDisabledPermissions($data);
        $data = Gdn_DataSet::index($data, "RoleID");

        $defaultRow = $data[0];
        unset(
            $data[0],
            $defaultRow["RoleID"],
            $defaultRow["JunctionTable"],
            $defaultRow["JunctionColumn"],
            $defaultRow["JunctionID"]
        );
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
                $data[$iD]["PermissionID"] = null;
            }
        }

        if (!is_array($roleID)) {
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
    public function stripPermissions($row, $defaultRow, $limitToSuffix = "")
    {
        $namespaces = $this->getNamespaces();

        foreach ($defaultRow as $permissionName => $value) {
            if (
                in_array($permissionName, ["PermissionID", "RoleID", "JunctionTable", "JunctionColumn", "JunctionID"])
            ) {
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
    protected function isGlobalPermission($value, $permissionName, $limitToSuffix, $namespaces)
    {
        if (!($value & 2)) {
            return false;
        }
        if (
            !empty($limitToSuffix) &&
            strtolower(substr($permissionName, -strlen($limitToSuffix))) != strtolower($limitToSuffix)
        ) {
            return false;
        }
        if ($index = strpos($permissionName, ".")) {
            if (
                !in_array(substr($permissionName, 0, $index), $namespaces) &&
                !in_array(substr($permissionName, 0, strrpos($permissionName, ".")), $namespaces)
            ) {
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
    protected function _MergeDisabledPermissions(&$globalPermissions)
    {
        // Get the default permissions for junctions that are disabled.
        $disabledPermissions = c("Garden.Permissions.Disabled");
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
            ->select("*")
            ->from("Permission p")
            ->where("p.RoleID", 0)
            ->whereIn("p.JunctionTable", $disabledIn)
            ->get()
            ->resultArray();

        $defaultRow = &$globalPermissions[0];

        // Loop through each row and add it's default definition to the global permissions.
        foreach ($disabledData as $permissionRow) {
            foreach ($permissionRow as $columnName => $value) {
                if (
                    in_array($columnName, ["PermissionID", "RoleID", "JunctionTable", "JunctionColumn", "JunctionID"])
                ) {
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
     * Get all permission columns in the system.
     *
     * @param bool $junctionTable
     * @param bool $junctionColumn
     * @return mixed
     * @throws Exception
     */
    public function permissionColumns($junctionTable = false, $junctionColumn = false)
    {
        $key = "{$junctionTable}__{$junctionColumn}";

        if (!isset($this->_PermissionColumns[$key])) {
            $sQL = clone $this->SQL;
            $sQL->reset();

            $sQL->select("*")
                ->from("Permission")
                ->limit(1);

            if ($junctionTable !== false && $junctionColumn !== false) {
                $sQL->where("JunctionTable", $junctionTable)
                    ->where("JunctionColumn", $junctionColumn)
                    ->where("RoleID", 0);
            }

            $cols = $sQL->get()->firstRow(DATASET_TYPE_ARRAY);

            unset($cols["RoleID"], $cols["JunctionTable"], $cols["JunctionColumn"], $cols["JunctionID"]);

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
    public static function permissionNamespace($permissionName)
    {
        if ($index = strpos($permissionName, ".")) {
            return substr($permissionName, 0, $index);
        }
        return "";
    }

    /**
     *
     *
     * @param $data
     * @param null $overrides
     * @return array
     */
    public function pivotPermissions($data, $overrides = null)
    {
        // Get all of the columns in the permissions table.
        $schema = $this->SQL->get("Permission", "", "", 1)->firstRow(DATASET_TYPE_ARRAY);
        foreach ($schema as $key => $value) {
            if (strpos($key, ".") !== false) {
                $schema[$key] = 0;
            }
        }
        unset($schema["PermissionID"]);
        $schema["RoleID"] = 0;
        $schema["JunctionTable"] = null;
        $schema["JunctionColumn"] = null;
        $schema["JunctionID"] = null;

        $result = [];
        if (is_array($data)) {
            foreach ($data as $setPermission) {
                // Get the parts out of the permission.
                $parts = explode("//", $setPermission);
                if (count($parts) > 1) {
                    // This is a junction permission.
                    $permissionName = $parts[1];
                    $key = $parts[0];
                    $parts = explode("/", $key);
                    $junctionTable = $parts[0];
                    $junctionColumn = $parts[1];
                    $junctionID = val("JunctionID", $overrides, $parts[2]);
                    if (count($parts) >= 4) {
                        $roleID = $parts[3];
                    } else {
                        $roleID = val("RoleID", $overrides, null);
                    }
                } else {
                    // This is a global permission.
                    $permissionName = $parts[0];
                    $key = "Global";
                    $junctionTable = null;
                    $junctionColumn = null;
                    $junctionID = null;
                    $roleID = val("RoleID", $overrides, null);
                }

                // Check for a row in the result for these permissions.
                if (!array_key_exists($key, $result)) {
                    $newRow = $schema;
                    $newRow["RoleID"] = $roleID;
                    $newRow["JunctionTable"] = $junctionTable;
                    $newRow["JunctionColumn"] = $junctionColumn;
                    $newRow["JunctionID"] = $junctionID;
                    $result[$key] = $newRow;
                }
                $result[$key][$permissionName] = 1;
            }
        }

        return $result;
    }

    /**
     * Save a permission row.
     *
     * @param array $formPostValues The values you want to save. See the Permission table for possible columns.
     * @param bool $settings Also save a junction permission to the global permissions.
     */
    public function save($formPostValues, $settings = false)
    {
        $saveGlobal = $settings;

        // Get the list of columns that are available for permissions.
        $permissionColumns = Gdn::permissionModel()
            ->defineSchema()
            ->fields();
        if (isset($formPostValues["Role"])) {
            $permissionColumns["Role"] = true;
        }
        $formPostValues = array_intersect_key($formPostValues, $permissionColumns);

        // Figure out how to find the existing permission.
        if (array_key_exists("PermissionID", $formPostValues)) {
            $where = ["PermissionID" => $formPostValues["PermissionID"]];
            unset($formPostValues["PermissionID"]);

            $this->SQL->update("Permission", $this->_Backtick($formPostValues), $where)->put();
        } else {
            $where = [];

            if (array_key_exists("RoleID", $formPostValues)) {
                $where["RoleID"] = $formPostValues["RoleID"];
                unset($formPostValues["RoleID"]);
            } elseif (array_key_exists("Role", $formPostValues)) {
                // Get the RoleID.
                $roleID = $this->SQL->getWhere("Role", ["Name" => $formPostValues["Role"]])->value("RoleID");
                if (!$roleID) {
                    return;
                }
                $where["RoleID"] = $roleID;
                unset($formPostValues["Role"]);
            } else {
                $where["RoleID"] = 0; // default role.
            }

            if (array_key_exists("JunctionTable", $formPostValues)) {
                $where["JunctionTable"] = $formPostValues["JunctionTable"];

                // If the junction table was given then so must the other values.
                if (array_key_exists("JunctionColumn", $formPostValues)) {
                    $where["JunctionColumn"] = $formPostValues["JunctionColumn"];
                }
                $where["JunctionID"] = $formPostValues["JunctionID"];
            } else {
                $where["JunctionTable"] = null; // no junction table.
                $where["JunctionColumn"] = null;
                $where["JunctionID"] = null;
            }

            unset($formPostValues["JunctionTable"], $formPostValues["JunctionColumn"], $formPostValues["JunctionID"]);

            $this->SQL->replace("Permission", $this->_Backtick($formPostValues), $where, true);

            if ($saveGlobal && !is_null($where["JunctionTable"])) {
                // Save these permissions with the global permissions.
                $where["JunctionTable"] = null; // no junction table.
                $where["JunctionColumn"] = null;
                $where["JunctionID"] = null;

                $this->SQL->replace("Permission", $this->_Backtick($formPostValues), $where, true);
            }
        }

        $this->clearPermissions();
    }

    /**
     * Perform bulk save of the permissions.
     *
     * @param array $permissions rows of permissions to save.
     * @param array $all Where where statement to select which permission rows are being replaced in this call.
     */
    public function saveAll(array $permissions, array $allWhere)
    {
        try {
            $insertRows = [];
            $globalRow = [];
            // Get the list of columns that are available for permissions.
            $permissionColumns = array_fill_keys(
                array_keys(
                    Gdn::permissionModel()
                        ->defineSchema()
                        ->fields()
                ),
                0
            );

            foreach ($permissions as $permission) {
                $roleID = 0; // default role.
                // Remove non-existing columns from input row, and add  missing, to match DB, to make sure all rows in array have the same size.
                $permission = array_intersect_key($permission, $permissionColumns);
                $permission = array_merge($permissionColumns, $permission);

                if (array_key_exists("RoleID", $permission)) {
                    $roleID = $permission["RoleID"];
                } elseif (array_key_exists("Role", $permission)) {
                    // Get the RoleID.
                    $roleID = $this->SQL->getWhere("Role", ["Name" => $permission["Role"]])->value("RoleID");
                    if (!$roleID) {
                        throw new NotFoundException("Role");
                    }
                }
                // Convert all true/false options to 1 or 0 that DB expects.
                foreach ($permission as $name => $value) {
                    if (strpos($name, ".") !== false) {
                        $permission[$name] = $permission[$name] ? 1 : 0;
                    }
                }

                unset($permission["Role"], $permission["PermissionID"]);
                if (!array_key_exists("JunctionTable", $permission) || empty($permission["JunctionTable"])) {
                    unset(
                        $permission["JunctionTable"],
                        $permission["JunctionColumn"],
                        $permission["JunctionID"],
                        $permission["RoleID"]
                    ); // no junction table.

                    $globalRow = $this->_Backtick($permission);
                    $globalRow["RoleID"] = $roleID;
                } else {
                    $permission["RoleID"] = $roleID;
                    $insertRows[] = $permission;
                }
            }
            $this->Database->beginTransaction();
            // Delete all existing permissions for the given user, and re-save them again.
            if (is_array($allWhere)) {
                $this->SQL->delete($this->getTableName(), $allWhere);
            }
            if (count($globalRow) > 0) {
                $this->SQL->insert($this->getTableName(), $globalRow);
            }
            if (count($insertRows) > 0) {
                $this->SQL->insert($this->getTableName(), $insertRows);
            }
            $this->Database->commitTransaction();
            $this->clearPermissions();
        } catch (\Exception $e) {
            $this->Database->rollbackTransaction();
            $this->logger->error("Error saving permissions.", [
                Logger::FIELD_EVENT => "permissions",
                Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
                Logger::FIELD_TAGS => ["user permission"],
                Logger::ERROR => $e->getMessage(),
                "errorTrace" => DebugUtils::stackTraceString($e->getTrace()),
            ]);
            throw $e;
        }
    }

    /**
     * Reset permissions for all roles, based on the value in their Type column.
     *
     * @param string $type Role type to limit the updates to.
     */
    public static function resetAllRoles($type = null)
    {
        // Retrieve an array containing all available roles.
        $roleModel = new RoleModel();
        $roleModel->clearCache();
        if ($type) {
            $result = $roleModel->getByType($type)->resultArray();
            $roles = array_column($result, "Name", "RoleID");
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
    public function resetRole($roleId)
    {
        // Grab the value of Type for this role.
        $roleType = $this->SQL->getWhere("Role", ["RoleID" => $roleId])->value("Type");

        if ($roleType == "") {
            $roleType = RoleModel::TYPE_MEMBER;
        }

        $defaults = $this->getDefaults();
        $rowDefaults = $this->getRowDefaults();

        $resetValues = array_fill_keys(array_keys($rowDefaults), 0);

        if (array_key_exists($roleType, $defaults)) {
            foreach ($defaults[$roleType] as $specificity => $permissions) {
                $permissions["RoleID"] = $roleId;
                $permissions = array_merge($resetValues, $permissions);

                if (strpos($specificity, ":")) {
                    [$junction, $junctionId] = explode(":", $specificity);
                    if ($junction && $junctionId) {
                        switch ($junction) {
                            case "Category":
                            default:
                                $permissions["JunctionTable"] = $junction;
                                $permissions["JunctionColumn"] = "PermissionCategoryID";
                                $permissions["JunctionID"] = $junctionId;
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
    public static function splitPermission($permissionName)
    {
        $i = strpos($permissionName, ".");
        $j = strrpos($permissionName, ".");

        if ($i !== false) {
            // $j must also not be false
            return [
                substr($permissionName, 0, $i),
                substr($permissionName, $i + 1, $j - $i - 1),
                substr($permissionName, $j + 1),
            ];
        } else {
            return [$permissionName, "", ""];
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
    public function sQLPermission(
        $sQL,
        $permissions,
        $foreignAlias,
        $foreignColumn,
        $junctionTable = "",
        $junctionColumn = ""
    ) {
        $session = Gdn::session();

        // Figure out the junction table if necessary.
        if (!$junctionTable && stringEndsWith($foreignColumn, "ID")) {
            $junctionTable = substr($foreignColumn, 0, -2);
        }

        // Check to see if the permission is disabled.
        if (c("Garden.Permission.Disabled." . $junctionTable)) {
            if (!$session->checkPermission($permissions)) {
                $sQL->whereAlwaysFalse();
            }
        } elseif ($session->UserID <= 0 || (is_object($session->User) && $session->User->Admin != "1")) {
            $sQL->distinct()
                ->join("Permission _p", "_p.JunctionID = " . $foreignAlias . "." . $foreignColumn, "inner")
                ->join("UserRole _ur", "_p.RoleID = _ur.RoleID", "inner")
                ->beginWhereGroup()
                ->where("_ur.UserID", $session->UserID);

            if (!is_array($permissions)) {
                $permissions = [$permissions];
            }

            $sQL->beginWhereGroup();
            foreach ($permissions as $permission) {
                $sQL->where("_p.`" . $permission . "`", 1);
            }
            $sQL->endWhereGroup();
        } else {
            // Force this method to play nice in case it is used in an or clause
            // (ie. it returns true in a sql sense by doing 1 = 1)
            $sQL->whereAlwaysTrue();
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
    public function unpivotPermissions($permissions, $includeRole = false)
    {
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
    public function undefine($names)
    {
        $names = (array) $names;
        $st = $this->Database->structure();
        $st->table("Permission");

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
    protected function _UnpivotPermissionsRow($row, &$result, $includeRole = false)
    {
        $globalName = val("Name", $row);

        // Loop through each permission in the row and place them in the correct place in the grid.
        foreach ($row as $permissionName => $value) {
            [$namespace, $name, $suffix] = self::splitPermission($permissionName);
            if (empty($name)) {
                continue; // was some other column
            }
            if ($globalName) {
                $namespace = $globalName;
            }

            if (array_key_exists("JunctionTable", $row) && ($junctionTable = $row["JunctionTable"])) {
                $key =
                    "$junctionTable/{$row["JunctionColumn"]}/{$row["JunctionID"]}" .
                    ($includeRole ? "/" . $row["RoleID"] : "");
            } else {
                $key = "_" . $namespace;
            }

            // Check to see if the namespace is in the result.
            if (!array_key_exists($key, $result)) {
                $result[$key] = ["_Columns" => [], "_Rows" => [], "_Info" => ["Name" => $namespace]];
            }
            $namespaceArray = &$result[$key];

            // Add the names to the columns and rows.
            $namespaceArray["_Columns"][$suffix] = true;
            $namespaceArray["_Rows"][$name] = true;

            // Augment the value depending on the junction ID.
            if (substr($key, 0, 1) === "_") {
                $postValue = $permissionName;
            } else {
                $postValue = $key . "//" . $permissionName;
            }

            $namespaceArray[$name . "." . $suffix] = ["Value" => $value, "PostValue" => $postValue];
        }
    }

    /**
     * Get the namespaces from enabled permissions.
     *
     * @return array Returns an array of permission prefixes.
     */
    public function getNamespaces()
    {
        if (!isset($this->namespaces)) {
            $this->namespaces = $this->getAllowedPermissionNamespaces();
        }
        $namespaces = $this->namespaces;
        return $namespaces;
    }

    /**
     * Return the roleIDs that have a particular permission.
     * If the permission is a junction permission, the junction table and ID must be provided.
     * If a junction table is provided, the mode {@link Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION} will be used.
     *
     * @param string $permissionName
     * @param string|null $junctionTable
     * @param int|null $junctionID
     * @return int[]
     */
    public function getRoleIDsHavingSpecificPermission(
        string $permissionName,
        ?string $junctionTable = null,
        ?int $junctionID = null
    ): array {
        $permissionsInc = \Gdn::userModel()->getPermissionsIncrement();
        return $this->modelCache->getCachedOrHydrate(
            ["roleIDsByPermissions", $permissionName, $junctionTable, $junctionID, "inc" => $permissionsInc],
            function () use ($permissionName, $junctionTable, $junctionID) {
                $emptyPermissionInstance = $this->createPermissionInstance();
                $resolvePermissionName = $emptyPermissionInstance->resolvePermissionName($permissionName);
                $permissionColumn = $this->SQL->escapeIdentifier($resolvePermissionName);

                $wheres = [
                    $permissionColumn => 1,
                ];
                if ($junctionTable && $junctionID) {
                    $resolveJunctionID = $emptyPermissionInstance->hasJunctionID($junctionTable, $junctionID)
                        ? $emptyPermissionInstance->resolveJuntionAlias($junctionTable, $junctionID)
                        : -1;
                    $wheres = array_merge($wheres, [
                        "JunctionTable" => $junctionTable,
                        "JunctionID" => $resolveJunctionID,
                    ]);
                }

                $roleIDs = $this->createSql()
                    ->from("Permission")
                    ->select("RoleID", "distinct")
                    ->where($wheres)
                    ->get()
                    ->column("RoleID");

                return $roleIDs;
            }
        );
    }
}

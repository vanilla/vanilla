<?php
/**
 * A role model you can look up to.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles role data.
 */
class RoleModel extends Gdn_Model {

    /** Slug for Guest role type. */
    const TYPE_GUEST = 'guest';

    /** Slug for Unconfirmed role type. */
    const TYPE_UNCONFIRMED = 'unconfirmed';

    /** Slug for Applicant role type. */
    const TYPE_APPLICANT = 'applicant';

    /** Slug for Member role type. */
    const TYPE_MEMBER = 'member';

    /** Slug for Moderator role type. */
    const TYPE_MODERATOR = 'moderator';

    /** Slug for Administrator role type. */
    const TYPE_ADMINISTRATOR = 'administrator';

    /** @var array|null All roles. */
    public static $Roles = null;

    /** @var array A list of permissions that define an increasing ranking of permissions. */
    public $RankPermissions = [
        'Garden.Moderation.Manage',
        'Garden.Community.Manage',
        'Garden.Users.Add',
        'Garden.Settings.Manage',
        'Conversations.Moderation.Manage'
    ];

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Role');
        $this->fireEvent('Init');
    }

    /**
     * Clear the roles cache.
     */
    public function clearCache() {
        $key = 'Roles';
        Gdn::cache()->remove($key);
    }

    /**
     * Define a role.
     *
     * @param $values
     */
    public function define($values) {
        if (array_key_exists('RoleID', $values)) {
            $roleID = $values['RoleID'];
            unset($values['RoleID']);

            $this->SQL->replace('Role', $values, ['RoleID' => $roleID], true);
        } else {
            // Check to see if there is a role with the same name.
            $roleID = $this->SQL->getWhere('Role', ['Name' => $values['Name']])->value('RoleID', null);

            if (is_null($roleID)) {
                // Figure out the next role ID.
                $maxRoleID = $this->SQL->select('r.RoleID', 'MAX')->from('Role r')->get()->value('RoleID', 0);
                $roleID = $maxRoleID + 1;
                $values['RoleID'] = $roleID;

                // Insert the role.
                $this->SQL->insert('Role', $values);
            } else {
                // Update the role.
                $this->SQL->update('Role', $values, ['RoleID' => $roleID])->put();
            }
        }
        $this->clearCache();
    }

    /**
     * Use with array_filter to remove PersonalInfo roles.
     *
     * @var mixed $roles Role name (string) or $role data (array or object).
     * @return bool Whether role is NOT personal info (FALSE = remove it, it's personal).
     */
    public static function filterPersonalInfo($role) {
        if (is_string($role)) {
            $roles = self::getByName($role);
            $role = array_shift($roles);
        }

        return (val('PersonalInfo', $role)) ? false : true;
    }

    /**
     * Returns a resultset of all roles.
     *
     * @inheritdoc
     */
    public function get($orderFields = '', $orderDirection = 'asc', $limit = false, $pageNumber = false) {
        return $this->SQL
            ->select()
            ->from('Role')
            ->orderBy($orderFields ?: 'Sort', $orderDirection)
            ->get();
    }

    /**
     * Get the category specific permissions for a role.
     *
     * @param int $roleID The ID of the role to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function getCategoryPermissions($roleID) {
        $permissions = Gdn::permissionModel()->getJunctionPermissions(['RoleID' => $roleID], 'Category');
        $result = [];

        foreach ($permissions as $perm) {
            $row = ['CategoryID' => $perm['JunctionID']];
            unset($perm['Name'], $perm['JunctionID'], $perm['JunctionTable'], $perm['JunctionColumn']);
            $row += $perm;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get all of the roles including their ranking permissions.
     *
     * @return Gdn_DataSet Returns all of the roles with the ranking permissions.
     */
    public function getWithRankPermissions() {
        $this->SQL
            ->select('r.*')
            ->from('Role r')
            ->leftJoin('Permission p', 'p.RoleID = r.RoleID and p.JunctionID is null')
            ->orderBy('Sort', 'asc');

        foreach ($this->RankPermissions as $permission) {
            $this->SQL->select("`$permission`", '', $permission);
        }

        $result = $this->SQL->get();
        return $result;
    }

    /**
     * Returns an array of RoleID => RoleName pairs.
     *
     * @return array
     */
    public function getArray() {
        $roleData = $this->get()->resultArray();
        $result = array_column($roleData, 'Name', 'RoleID');

        return $result;
    }

    /**
     * Get the roles that the current user is allowed to assign to another user.
     *
     * @return array Returns an array in the format `[RoleID => 'Role Name']`.
     */
    public function getAssignable() {
        // Administrators can assign all roles.
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return $this->getArray();
        }
        // Users that can't edit other users can't assign any roles.
        if (!Gdn::session()->checkPermission('Garden.Users.Edit')) {
            return [];
        }

        $sql = Gdn::sql();

        $sql->select('r.RoleID, r.Name')
            ->from('Role r')
            ->leftJoin('Permission p', 'p.RoleID = r.RoleID and p.JunctionID is null'); // join to global permissions

        // Community managers can assign permissions they have,
        // but other users can't assign any ranking permissions.
        $cM = Gdn::session()->checkPermission('Garden.Community.Manage');
        foreach ($this->RankPermissions as $permission) {
            if (!$cM || !Gdn::session()->checkPermission($permission)) {
                $sql->where("coalesce(`$permission`, 0)", '0', false, false);
            }
        }

        $roles = $sql->get()->resultArray();
        $roles = array_column($roles, 'Name', 'RoleID');

        return $roles;
    }

    /**
     * Get the default role IDs for a type of role.
     *
     * @param string $type One of the {@link RoleModel::TYPE_*} constants.
     * @return array Returns an array of role IDs.
     */
    public static function getDefaultRoles($type) {
        // Get the roles that match the type.
        try {
            $roleData = Gdn::sql()->select('RoleID')->getWhere('Role', ['Type' => $type])->resultArray();
            $roleIDs = array_column($roleData, 'RoleID');
        } catch (Exception $ex) {
            // This exception happens when the type column hasn't been added to GDN_Role yet.
            $roleIDs = [];
        }

        // This method has to be backwards compatible with the old config roles.
        switch ($type) {
            case self::TYPE_APPLICANT:
                $backRoleIDs = (array)c('Garden.Registration.ApplicantRoleID', null);
                break;
            case self::TYPE_GUEST:
                $guestRoleData = Gdn::sql()->getWhere('UserRole', ['UserID' => 0])->resultArray();
                $backRoleIDs = array_column($guestRoleData, 'RoleID');
                break;
            case self::TYPE_MEMBER:
                $backRoleIDs = (array)c('Garden.Registration.DefaultRoles', null);
                break;
            case self::TYPE_UNCONFIRMED:
                $backRoleIDs = (array)c('Garden.Registration.ConfirmEmailRole', null);
                break;
            default:
                $backRoleIDs = [];
        }
        $roleIDs = array_merge($roleIDs, $backRoleIDs);
        $roleIDs = array_unique($roleIDs);

        return $roleIDs;
    }

    /**
     * Get the default role IDs for all types of roles.
     *
     * @return array Returns an array of arrays indexed by role type.
     */
    public static function getAllDefaultRoles() {
        $result = array_fill_keys(
            array_keys(self::getDefaultTypes(false)),
            []
        );

        // Add the roles per type from the role table.
        $roleData = Gdn::sql()->getWhere('Role', ['Type is not null' => ''])->resultArray();
        foreach ($roleData as $row) {
            $result[$row['Type']][] = $row['RoleID'];
        }

        // Add the backwards compatible roles.
        $result[self::TYPE_APPLICANT] = array_merge(
            $result[self::TYPE_APPLICANT],
            (array)c('Garden.Registration.ApplicantRoleID', null)
        );

        $guestRoleIDs = Gdn::sql()->getWhere('UserRole', ['UserID' => 0])->resultArray();
        $guestRoleIDs = array_column($guestRoleIDs, 'RoleID');
        $result[self::TYPE_GUEST] = array_merge(
            $result[self::TYPE_GUEST],
            $guestRoleIDs
        );

        $result[self::TYPE_MEMBER] = array_merge(
            $result[self::TYPE_MEMBER],
            (array)c('Garden.Registration.DefaultRoles', [])
        );

        $result[self::TYPE_UNCONFIRMED] = array_merge(
            $result[self::TYPE_UNCONFIRMED],
            (array)c('Garden.Registration.ConfirmEmailRole', null)
        );

        $result = array_map('array_unique', $result);

        return $result;
    }

    /**
     * Get an array of default role types.
     *
     * @param bool $translate Whether or not to translate the type names.
     * @return array Returns an array in the form `[type => name]`.
     */
    public static function getDefaultTypes($translate = true) {
        $result = [
            self::TYPE_MEMBER => self::TYPE_MEMBER,
            self::TYPE_GUEST => self::TYPE_GUEST,
            self::TYPE_UNCONFIRMED => self::TYPE_UNCONFIRMED,
            self::TYPE_APPLICANT => self::TYPE_APPLICANT,
            self::TYPE_MODERATOR => self::TYPE_MODERATOR,
            self::TYPE_ADMINISTRATOR => self::TYPE_ADMINISTRATOR
        ];
        if ($translate) {
            $result = array_map('t', $result);
        }
        return $result;
    }

    /**
     * Returns a resultset of all roles that have editable permissions.
     *
     * public function getEditablePermissions() {
     * return $this->SQL
     * ->select()
     * ->from('Role')
     * ->where('EditablePermissions', '1')
     * ->orderBy('Sort', 'asc')
     * ->get();
     * }
     */

    /**
     * Returns a resultset of role data related to the specified RoleID.
     *
     * @param int The RoleID to filter to.
     */
    public function getByRoleID($roleID) {
        return $this->getWhere(['RoleID' => $roleID])->firstRow();
    }

    /**
     * Get the roles for a user.
     *
     * @param int $userID The user to get the roles for.
     * @return Gdn_DataSet Returns the roles as a dataset (with array values).
     * @see UserModel::getRoles()
     */
    public function getByUserID($userID) {
        $result = Gdn::userModel()->getRoles($userID);
        return $result;
    }

    /**
     * Return all roles matching a specific type.
     *
     * @param string $type Type slug to match role records against.
     * @return Gdn_DataSet
     */
    public function getByType($type) {
        return $this->SQL->select()
            ->from('Role')
            ->where('Type', $type)
            ->get();
    }

    /**
     * Returns a resultset of role data NOT related to the specified RoleID.
     *
     * @param int The RoleID to filter out.
     */
    public function getByNotRoleID($roleID) {
        return $this->getWhere(['RoleID <>' => $roleID]);
    }

    /**
     * Get the permissions for one or more roles.
     *
     * @param int|array $roleID One or more role IDs to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function getPermissions($roleID) {
        $permissionModel = Gdn::permissionModel();
        $roleIDs = (array)$roleID;

        foreach ($roleIDs as $iD) {
            $role = self::roles($iD);
            $limitToSuffix = val('CanSession', $role, true) ? '' : 'View';
        }

        $result = $permissionModel->getPermissions($roleIDs, $limitToSuffix);
        return $result;
    }

    /**
     * Returns the number of users assigned to the provided RoleID. If
     * $usersOnlyWithThisRole is TRUE, it will return the number of users who
     * are assigned to this RoleID and NO OTHER.
     *
     * @param int The RoleID to filter to.
     * @param bool Indicating if the count should be any users with this RoleID, or users who are ONLY assigned to this RoleID.
     */
    public function getUserCount($roleID, $usersOnlyWithThisRole = false) {
        if ($usersOnlyWithThisRole) {
            $data = $this->SQL->select('ur.UserID', 'count', 'UserCount')
                ->from('UserRole ur')
                ->join('UserRole urs', 'ur.UserID = urs.UserID')
                ->groupBy('urs.UserID')
                ->having('count(urs.RoleID) =', '1', false, false)
                ->where('ur.RoleID', $roleID)
                ->get()
                ->firstRow();

            return $data ? $data->UserCount : 0;
        } else {
            return $this->SQL->getCount('UserRole', ['RoleID' => $roleID]);
        }
    }

    /**
     * Get the current number of applicants waiting to be approved.
     *
     * @param bool $force Whether or not to force a cache refresh.
     * @return int Returns the number of applicants or 0 if the registration method isn't set to approval.
     */
    public function getApplicantCount($force = false) {
        if (c('Garden.Registration.Method') != 'Approval') {
            return 0;
        }

        $cacheKey = 'Moderation.ApplicantCount';

        if ($force) {
            Gdn::cache()->remove($cacheKey);
        }

        $applicantRoleIDs = static::getDefaultRoles(self::TYPE_APPLICANT);

        $count = Gdn::cache()->get($cacheKey);
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count = Gdn::sql()
                ->select('u.UserID', 'count', 'UserCount')
                ->from('User u')
                ->join('UserRole ur', 'u.UserID = ur.UserID')
                ->where('ur.RoleID', $applicantRoleIDs)
                ->where('u.Deleted', '0')
                ->get()->value('UserCount', 0);

            Gdn::cache()->store($cacheKey, $count, [
                Gdn_Cache::FEATURE_EXPIRY => 300 // 5 minutes
            ]);
        }
        return $count;
    }

    /**
     * Retrieves all roles with the specified permission(s).
     *
     * @param mixed A permission (or array of permissions) to match.
     */
    public function getByPermission($permission) {
        if (!is_array($permission)) {
            $permission = [$permission];
        }

        $this->SQL->select('r.*')
            ->from('Role r')
            ->join('Permission per', "per.RoleID = r.RoleID")
            ->where('per.JunctionTable is null');

        $this->SQL->beginWhereGroup();
        $permissionCount = count($permission);
        for ($i = 0; $i < $permissionCount; ++$i) {
            $this->SQL->where('per.'.$permission[$i], 1);
        }
        $this->SQL->endWhereGroup();
        return $this->SQL->get();
    }

    /**
     * Get a role by name.
     *
     * @param array|string $names
     */
    public static function getByName($names, &$missing = null) {
        if (is_string($names)) {
            $names = explode(',', $names);
            $names = array_map('trim', $names);
        }

        // Make a lookup array of the names.
        $names = array_unique($names);
        $names = array_combine($names, $names);
        $names = array_change_key_case($names);

        $roles = RoleModel::roles();
        $result = [];
        foreach ($roles as $roleID => $role) {
            $name = strtolower($role['Name']);

            if (isset($names[$name])) {
                $result[$roleID] = $role;
                unset($names[$name]);
            }
        }

        $missing = array_values($names);

        return $result;
    }

    /**
     *
     *
     * @param null $roleID
     * @param bool $force
     * @return array|mixed|null|type
     */
    public static function roles($roleID = null, $force = false) {
        if (self::$Roles == null) {
            $key = 'Roles';
            $roles = Gdn::cache()->get($key);
            if ($roles === Gdn_Cache::CACHEOP_FAILURE) {
                $roles = Gdn::sql()->get('Role', 'Sort')->resultArray();
                $roles = Gdn_DataSet::index($roles, ['RoleID']);
                Gdn::cache()->store($key, $roles, [Gdn_Cache::FEATURE_EXPIRY => 24 * 3600]);
            }
        } else {
            $roles = self::$Roles;
        }

        if ($roleID === null) {
            return $roles;
        } elseif (array_key_exists($roleID, $roles))
            return $roles[$roleID];
        elseif ($force)
            return ['RoleID' => $roleID, 'Name' => ''];
        else {
            return null;
        }
    }

    /**
     * Save role data.
     *
     * @param array $formPostValues The role row to save.
     * @param array|false $settings Additional settings for the save.
     * @return bool|mixed Returns the role ID or false on error.
     */
    public function save($formPostValues, $settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        $roleID = val('RoleID', $formPostValues);
        $insert = $roleID > 0 ? false : true;
        $doPermissions = val('DoPermissions', $settings, true);

        if ($insert) {
            // Figure out the next role ID.
            $maxRoleID = $this->SQL->select('r.RoleID', 'MAX')->from('Role r')->get()->value('RoleID', 0);
            $roleID = $maxRoleID + 1;

            $this->addInsertFields($formPostValues);
            $formPostValues['RoleID'] = strval($roleID); // string for validation
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert)) {
            $fields = $this->Validation->schemaValidationFields();
            $fields = $this->coerceData($fields);

            if ($insert === false) {
                $this->update($fields, ['RoleID' => $roleID]);
            } else {
                $this->insert($fields);
            }
            // Now update the role permissions
            $role = $this->getByRoleID($roleID);

            if ($doPermissions) {
                $permissionModel = Gdn::permissionModel();

                if (array_key_exists('Permissions', $formPostValues)) {
                    $globalPermissions = $formPostValues['Permissions'];
                    $categoryPermissions = val('Category', $globalPermissions, []);

                    // Massage the global permissions.
                    unset($globalPermissions['Category']);
                    $globalPermissions['RoleID'] = $roleID;
                    $globalPermissions['JunctionTable'] = null;
                    $globalPermissions['JunctionColumn'] = null;
                    $globalPermissions['JunctionID'] = null;
                    $permissions = [$globalPermissions];

                    // Massage the category permissions.
                    foreach ($categoryPermissions as $perm) {
                        $row = $perm;
                        $row['RoleID'] = $roleID;
                        $row['JunctionTable'] = 'Category';
                        $row['JunctionColumn'] = 'PermissionCategoryID';
                        $row['JunctionID'] = $row['CategoryID'];
                        unset($row['CategoryID']);
                        $permissions[] = $row;
                    }
                } else {
                    $permissions = val('Permission', $formPostValues);
                    $permissions = $permissionModel->pivotPermissions($permissions, ['RoleID' => $roleID]);
                }

                $permissionsWhere = ['RoleID' => $roleID];
                if (val('IgnoreCategoryPermissions', $formPostValues)) {
                    // Include the default category permissions when ignoring the rest.
                    $permissionsWhere['JunctionID'] = [null, -1];
                }
                $permissionModel->saveAll($permissions, $permissionsWhere);
            }

            if (Gdn::cache()->activeEnabled()) {
                // Don't update the user table if we are just using cached permissions.
                $this->clearCache();
                Gdn::userModel()->clearPermissions();
            } else {
                // Remove the cached permissions for all users with this role.
                $this->SQL->update('User')
                    ->join('UserRole', 'User.UserID = UserRole.UserID')
                    ->set('Permissions', '')
                    ->where(['UserRole.RoleID' => $roleID])
                    ->put();
            }
        } else {
            $roleID = false;
        }
        return $roleID;
    }

    /**
     *
     *
     * @param $users
     * @param string $userIDColumn
     * @param string $rolesColumn
     */
    public static function setUserRoles(&$users, $userIDColumn = 'UserID', $rolesColumn = 'Roles') {
        $userIDs = array_unique(array_column($users, $userIDColumn));

        // Try and get all of the mappings from the cache.
        $keys = [];
        foreach ($userIDs as $userID) {
            $keys[$userID] = formatString(UserModel::USERROLES_KEY, ['UserID' => $userID]);
        }
        $userRoles = Gdn::cache()->get($keys);
        if (!is_array($userRoles)) {
            $userRoles = [];
        }

        // Grab all of the data that doesn't exist from the DB.
        $missingIDs = [];
        foreach ($keys as $userID => $key) {
            if (!array_key_exists($key, $userRoles)) {
                $missingIDs[$userID] = $key;
            }
        }
        if (count($missingIDs) > 0) {
            $dbUserRoles = Gdn::sql()
                ->select('ur.*')
                ->from('UserRole ur')
                ->whereIn('ur.UserID', array_keys($missingIDs))
                ->get()->resultArray();

            $dbUserRoles = Gdn_DataSet::index($dbUserRoles, 'UserID', ['Unique' => false]);

            // Store the user role mappings.
            foreach ($dbUserRoles as $userID => $rows) {
                $roleIDs = array_column($rows, 'RoleID');
                $key = $keys[$userID];
                Gdn::cache()->store($key, $roleIDs);
                $userRoles[$key] = $roleIDs;
            }
        }

        $allRoles = self::roles(); // roles indexed by role id.

        // Skip personal info roles
        if (!checkPermission('Garden.PersonalInfo.View')) {
            $allRoles = array_filter($allRoles, 'self::FilterPersonalInfo');
        }

        // Join the users.
        foreach ($users as &$user) {
            $userID = val($userIDColumn, $user);
            $key = $keys[$userID];

            $roleIDs = val($key, $userRoles, []);
            $roles = [];
            foreach ($roleIDs as $roleID) {
                if (!array_key_exists($roleID, $allRoles)) {
                    continue;
                }
                $roles[$roleID] = $allRoles[$roleID]['Name'];
            }
            setValue($rolesColumn, $user, $roles);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($where = [], $options = []) {
        if (is_numeric($where) || is_object($where)) {
            deprecated('RoleModel->delete()', 'RoleModel->deleteandReplace()');

            $result = $this->deleteAndReplace($where, $options);
            return $result;
        }

        throw new \BadMethodCallException("RoleModel->delete() is not supported.", 400);
    }

    /**
     * Delete a role.
     *
     * @param int $roleID The ID of the role to delete.
     * @param array $options An array of options to affect the behavior of the delete.
     *
     * - **newRoleID**: The new role to point users to.
     * @return bool Returns **true** on success or **false** otherwise.
     */
    public function deleteID($roleID, $options = []) {
        $result = $this->deleteAndReplace($roleID, val('newRoleID', $options));
        return $result;
    }

    /**
     * Delete a role.
     *
     * @param int $roleID The ID of the role to delete.
     * @param int $newRoleID Assign users of the deleted role to this new role.
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function deleteAndReplace($roleID, $newRoleID) {
        // First update users that will be orphaned
        if (is_numeric($newRoleID) && $newRoleID > 0) {
            $this->SQL
                ->options('Ignore', true)
                ->update('UserRole')
                ->join('UserRole urs', 'UserRole.UserID = urs.UserID')
                ->groupBy('urs.UserID')
                ->having('count(urs.RoleID) =', '1', false, false)
                ->set('UserRole.RoleID', $newRoleID)
                ->where(['UserRole.RoleID' => $roleID])
                ->put();
        } else {
            $this->SQL->delete('UserRole', ['RoleID' => $roleID]);
        }

        // Remove permissions for this role.
        $permissionModel = Gdn::permissionModel();
        $permissionModel->delete($roleID);

        // Remove the role
        $result = $this->SQL->delete('Role', ['RoleID' => $roleID]);
        return $result;
    }

    /**
     * Get a list of a user's roles that are permitted to be seen.
     * Optionally return all the role data or just one field name.
     *
     * @param $userID
     * @param string $field optionally the field name from the role table to return.
     * @return array|null|void
     */
    public function getPublicUserRoles($userID, $field = "Name") {
        if (!$userID) {
            return;
        }

        $unfilteredRoles = $this->getByUserID($userID)->resultArray();

        // Hide personal info roles
        $unformattedRoles = [];
        if (!checkPermission('Garden.PersonalInfo.View')) {
            $unformattedRoles = array_filter($unfilteredRoles, 'self::FilterPersonalInfo');
        } else {
            $unformattedRoles = $unfilteredRoles;
        }

        // If an empty string is passed as the field, return all the data from gdn_role row.
        if (!$field) {
            return $unformattedRoles;
        }

        // If there is a return key, return an array with the field as the key
        // and the value of the field as the value.
        $formattedRoles = array_column($unformattedRoles, $field);

        return $formattedRoles;
    }

    /**
     * Enforce integrity between users and roles.
     */
    public static function cleanUserRoles() {
        $px = Gdn::database()->DatabasePrefix;
        Gdn::sql()->query("
            delete ur
            from {$px}UserRole as ur
                left join {$px}Role as r on r.RoleID = ur.RoleID
                left join {$px}User as u on u.UserID = ur.UserID
            where r.RoleID is null
                or u.UserID is null
        ");
    }

    /**
     * @inheritdoc
     */
    public function validate($values, $insert = false) {
        $result = true;
        $roleID = val('RoleID', $values);

        if ($roleID && !$insert) {
            $role = $this->getID($roleID, DATASET_TYPE_ARRAY);
            if ($role) {
                $roleType = val('Type', $role);
                $newType = val('Type', $values);
                if (c('Garden.Registration.ConfirmEmail') && $roleType === self::TYPE_UNCONFIRMED && $newType !== self::TYPE_UNCONFIRMED) {
                    $totalUnconfirmedRoles = $this->getByType(self::TYPE_UNCONFIRMED)->count();
                    if ($totalUnconfirmedRoles === 1) {
                        $this->Validation->addValidationResult('Type', 'One unconfirmed role is required for email confirmation.');
                    }
                }
            }
        }

        $result = $result && parent::validate($values, $insert);
        return $result;
    }
}

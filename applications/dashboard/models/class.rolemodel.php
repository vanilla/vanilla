<?php
/**
 * A role model you can look up to.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public $RankPermissions = array(
        'Garden.Moderation.Manage',
        'Garden.Community.Manage',
        'Garden.Users.Add',
        'Garden.Settings.Manage',
        'Conversations.Moderation.Manage'
    );

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
        $Key = 'Roles';
        Gdn::cache()->remove($Key);
    }

    /**
     * Define a role.
     *
     * @param $Values
     */
    public function define($Values) {
        if (array_key_exists('RoleID', $Values)) {
            $RoleID = $Values['RoleID'];
            unset($Values['RoleID']);

            $this->SQL->replace('Role', $Values, array('RoleID' => $RoleID), true);
        } else {
            // Check to see if there is a role with the same name.
            $RoleID = $this->SQL->getWhere('Role', array('Name' => $Values['Name']))->value('RoleID', null);

            if (is_null($RoleID)) {
                // Figure out the next role ID.
                $MaxRoleID = $this->SQL->select('r.RoleID', 'MAX')->from('Role r')->get()->value('RoleID', 0);
                $RoleID = $MaxRoleID + 1;
                $Values['RoleID'] = $RoleID;

                // Insert the role.
                $this->SQL->insert('Role', $Values);
            } else {
                // Update the role.
                $this->SQL->update('Role', $Values, array('RoleID' => $RoleID))->put();
            }
        }
        $this->clearCache();
    }

    /**
     * Use with array_filter to remove PersonalInfo roles.
     *
     * @var mixed $Roles Role name (string) or $Role data (array or object).
     * @return bool Whether role is NOT personal info (FALSE = remove it, it's personal).
     */
    public static function filterPersonalInfo($Role) {
        if (is_string($Role)) {
            $Role = array_shift(self::getByName($Role));
        }

        return (val('PersonalInfo', $Role)) ? false : true;
    }

    /**
     * Returns a resultset of all roles.
     */
    public function get() {
        return $this->SQL
            ->select()
            ->from('Role')
            ->orderBy('Sort', 'asc')
            ->get();
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

        foreach ($this->RankPermissions as $Permission) {
            $this->SQL->select("`$Permission`", '', $Permission);
        }

        $Result = $this->SQL->get();
        return $Result;
    }

    /**
     * Returns an array of RoleID => RoleName pairs.
     *
     * @return array
     */
    public function getArray() {
        $RoleData = $this->get()->resultArray();
        $Result = array_column($RoleData, 'Name', 'RoleID');

        return $Result;
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
            return array();
        }

        $Sql = Gdn::sql();

        $Sql->select('r.RoleID, r.Name')
            ->from('Role r')
            ->leftJoin('Permission p', 'p.RoleID = r.RoleID and p.JunctionID is null'); // join to global permissions

        // Community managers can assign permissions they have,
        // but other users can't assign any ranking permissions.
        $CM = Gdn::session()->checkPermission('Garden.Community.Manage');
        foreach ($this->RankPermissions as $Permission) {
            if (!$CM || !Gdn::session()->checkPermission($Permission)) {
                $Sql->where("coalesce(`$Permission`, 0)", '0', false, false);
            }
        }

        $Roles = $Sql->get()->resultArray();
        $Roles = array_column($Roles, 'Name', 'RoleID');

        return $Roles;
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
            $roleData = Gdn::sql()->select('RoleID')->getWhere('Role', array('Type' => $type))->resultArray();
            $roleIDs = array_column($roleData, 'RoleID');
        } catch (Exception $ex) {
            // This exception happens when the type column hasn't been added to GDN_Role yet.
            $roleIDs = array();
        }

        // This method has to be backwards compatible with the old config roles.
        switch ($type) {
            case self::TYPE_APPLICANT:
                $backRoleIDs = (array)c('Garden.Registration.ApplicantRoleID', null);
                break;
            case self::TYPE_GUEST:
                $guestRoleData = Gdn::sql()->getWhere('UserRole', array('UserID' => 0))->resultArray();
                $backRoleIDs = array_column($guestRoleData, 'RoleID');
                break;
            case self::TYPE_MEMBER:
                $backRoleIDs = (array)c('Garden.Registration.DefaultRoles', null);
                break;
            case self::TYPE_UNCONFIRMED:
                $backRoleIDs = (array)c('Garden.Registration.ConfirmEmailRole', null);
                break;
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
            array()
        );

        // Add the roles per type from the role table.
        $roleData = Gdn::sql()->getWhere('Role', array('Type is not null' => ''))->resultArray();
        foreach ($roleData as $row) {
            $result[$row['Type']][] = $row['RoleID'];
        }

        // Add the backwards compatible roles.
        $result[self::TYPE_APPLICANT] = array_merge(
            $result[self::TYPE_APPLICANT],
            (array)c('Garden.Registration.ApplicantRoleID', null)
        );

        $guestRoleIDs = Gdn::sql()->getWhere('UserRole', array('UserID' => 0))->resultArray();
        $guestRoleIDs = array_column($guestRoleIDs, 'RoleID');
        $result[self::TYPE_GUEST] = array_merge(
            $result[self::TYPE_GUEST],
            $guestRoleIDs
        );

        $result[self::TYPE_MEMBER] = array_merge(
            $result[self::TYPE_MEMBER],
            (array)c('Garden.Registration.DefaultRoles', array())
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
        $result = array(
            self::TYPE_MEMBER => self::TYPE_MEMBER,
            self::TYPE_GUEST => self::TYPE_GUEST,
            self::TYPE_UNCONFIRMED => self::TYPE_UNCONFIRMED,
            self::TYPE_APPLICANT => self::TYPE_APPLICANT,
            self::TYPE_MODERATOR => self::TYPE_MODERATOR,
            self::TYPE_ADMINISTRATOR => self::TYPE_ADMINISTRATOR
        );
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
    public function getByRoleID($RoleID) {
        return $this->getWhere(array('RoleID' => $RoleID))->firstRow();
    }

    /**
     * Returns a resultset of role data related to the specified UserID.
     *
     * @param int The UserID to filter to.
     * @return Gdn_DataSet
     */
    public function getByUserID($UserID) {
        return $this->SQL->select()
            ->from('Role')
            ->join('UserRole', 'Role.RoleID = UserRole.RoleID')
            ->where('UserRole.UserID', $UserID)
            ->get();
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
    public function getByNotRoleID($RoleID) {
        return $this->getWhere(array('RoleID <>' => $RoleID));
    }

    /**
     * Get the permissions for one or more roles.
     *
     * @param int|array $RoleID One or more role IDs to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function getPermissions($RoleID) {
        $PermissionModel = Gdn::permissionModel();
        $roleIDs = (array)$RoleID;

        foreach ($roleIDs as $ID) {
            $Role = self::roles($ID);
            $LimitToSuffix = val('CanSession', $Role, true) ? '' : 'View';
        }

        $Result = $PermissionModel->GetPermissions($roleIDs, $LimitToSuffix);
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
    public function getUserCount($RoleID, $UsersOnlyWithThisRole = false) {
        if ($UsersOnlyWithThisRole) {
            $Data = $this->SQL->select('ur.UserID', 'count', 'UserCount')
                ->from('UserRole ur')
                ->join('UserRole urs', 'ur.UserID = urs.UserID')
                ->groupBy('urs.UserID')
                ->having('count(urs.RoleID) =', '1', true, false)
                ->where('ur.RoleID', $RoleID)
                ->get()
                ->firstRow();

            return $Data ? $Data->UserCount : 0;
        } else {
            return $this->SQL->getCount('UserRole', array('RoleID' => $RoleID));
        }
    }

    /**
     * Get the current number of applicants waiting to be approved.
     *
     * @param bool $Force Whether or not to force a cache refresh.
     * @return int Returns the number of applicants or 0 if the registration method isn't set to approval.
     */
    public function getApplicantCount($Force = false) {
        if (c('Garden.Registration.Method') != 'Approval') {
            return 0;
        }

        $CacheKey = 'Moderation.ApplicantCount';

        if ($Force) {
            Gdn::cache()->Remove($CacheKey);
        }

        $applicantRoleIDs = static::getDefaultRoles(self::TYPE_APPLICANT);

        $Count = Gdn::cache()->get($CacheKey);
        if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
            $Count = Gdn::sql()
                ->select('u.UserID', 'count', 'UserCount')
                ->from('User u')
                ->join('UserRole ur', 'u.UserID = ur.UserID')
                ->where('ur.RoleID', $applicantRoleIDs)
                ->where('u.Deleted', '0')
                ->get()->value('UserCount', 0);

            Gdn::cache()->store($CacheKey, $Count, array(
                Gdn_Cache::FEATURE_EXPIRY => 300 // 5 minutes
            ));
        }
        return $Count;
    }

    /**
     * Retrieves all roles with the specified permission(s).
     *
     * @param mixed A permission (or array of permissions) to match.
     */
    public function getByPermission($Permission) {
        if (!is_array($Permission)) {
            $Permission = array($Permission);
        }

        $this->SQL->select('r.*')
            ->from('Role r')
            ->join('Permission per', "per.RoleID = r.RoleID")
            ->where('per.JunctionTable is null');

        $this->SQL->beginWhereGroup();
        $PermissionCount = count($Permission);
        for ($i = 0; $i < $PermissionCount; ++$i) {
            $this->SQL->where('per.`'.$Permission[$i].'`', 1);
        }
        $this->SQL->endWhereGroup();
        return $this->SQL->get();
    }

    /**
     * Get a role by name.
     *
     * @param array|string $Names
     */
    public static function getByName($Names, &$Missing = null) {
        if (is_string($Names)) {
            $Names = explode(',', $Names);
            $Names = array_map('trim', $Names);
        }

        // Make a lookup array of the names.
        $Names = array_unique($Names);
        $Names = array_combine($Names, $Names);
        $Names = array_change_key_case($Names);

        $Roles = RoleModel::roles();
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

    /**
     *
     *
     * @param null $RoleID
     * @param bool $Force
     * @return array|mixed|null|type
     */
    public static function roles($RoleID = null, $Force = false) {
        if (self::$Roles == null) {
            $Key = 'Roles';
            $Roles = Gdn::cache()->get($Key);
            if ($Roles === Gdn_Cache::CACHEOP_FAILURE) {
                $Roles = Gdn::sql()->get('Role', 'Sort')->resultArray();
                $Roles = Gdn_DataSet::Index($Roles, array('RoleID'));
                Gdn::cache()->store($Key, $Roles, array(Gdn_Cache::FEATURE_EXPIRY => 24 * 3600));
            }
        } else {
            $Roles = self::$Roles;
        }

        if ($RoleID === null) {
            return $Roles;
        } elseif (array_key_exists($RoleID, $Roles))
            return $Roles[$RoleID];
        elseif ($Force)
            return array('RoleID' => $RoleID, 'Name' => '');
        else {
            return null;
        }
    }

    /**
     * Save role data.
     *
     * @param array $FormPostValues
     * @return bool|mixed
     * @throws Exception
     */
    public function save($FormPostValues) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        $RoleID = arrayValue('RoleID', $FormPostValues);
        $Insert = $RoleID > 0 ? false : true;
        if ($Insert) {
            // Figure out the next role ID.
            $MaxRoleID = $this->SQL->select('r.RoleID', 'MAX')->from('Role r')->get()->value('RoleID', 0);
            $RoleID = $MaxRoleID + 1;

            $this->addInsertFields($FormPostValues);
            $FormPostValues['RoleID'] = strval($RoleID); // string for validation
        } else {
            $this->addUpdateFields($FormPostValues);
        }

        // Validate the form posted values
        if ($this->validate($FormPostValues, $Insert)) {
            $Permissions = arrayValue('Permission', $FormPostValues);
            $Fields = $this->Validation->schemaValidationFields();

            if ($Insert === false) {
                $this->update($Fields, array('RoleID' => $RoleID));
            } else {
                $this->insert($Fields);
            }
            // Now update the role permissions
            $Role = $this->GetByRoleID($RoleID);

            $PermissionModel = Gdn::permissionModel();
            $Permissions = $PermissionModel->pivotPermissions($Permissions, array('RoleID' => $RoleID));
            $PermissionModel->saveAll($Permissions, array('RoleID' => $RoleID));

            if (Gdn::cache()->activeEnabled()) {
                // Don't update the user table if we are just using cached permissions.
                $this->ClearCache();
                Gdn::userModel()->clearPermissions();
            } else {
                // Remove the cached permissions for all users with this role.
                $this->SQL->update('User')
                    ->join('UserRole', 'User.UserID = UserRole.UserID')
                    ->set('Permissions', '')
                    ->where(array('UserRole.RoleID' => $RoleID))
                    ->put();
            }
        } else {
            $RoleID = false;
        }
        return $RoleID;
    }

    /**
     *
     *
     * @param $Users
     * @param string $UserIDColumn
     * @param string $RolesColumn
     */
    public static function setUserRoles(&$Users, $UserIDColumn = 'UserID', $RolesColumn = 'Roles') {
        $UserIDs = array_unique(ConsolidateArrayValuesByKey($Users, $UserIDColumn));

        // Try and get all of the mappings from the cache.
        $Keys = array();
        foreach ($UserIDs as $UserID) {
            $Keys[$UserID] = formatString(UserModel::USERROLES_KEY, array('UserID' => $UserID));
        }
        $UserRoles = Gdn::cache()->get($Keys);
        if (!is_array($UserRoles)) {
            $UserRoles = array();
        }

        // Grab all of the data that doesn't exist from the DB.
        $MissingIDs = array();
        foreach ($Keys as $UserID => $Key) {
            if (!array_key_exists($Key, $UserRoles)) {
                $MissingIDs[$UserID] = $Key;
            }
        }
        if (count($MissingIDs) > 0) {
            $DbUserRoles = Gdn::sql()
                ->select('ur.*')
                ->from('UserRole ur')
                ->whereIn('ur.UserID', array_keys($MissingIDs))
                ->get()->resultArray();

            $DbUserRoles = Gdn_DataSet::Index($DbUserRoles, 'UserID', array('Unique' => false));

            // Store the user role mappings.
            foreach ($DbUserRoles as $UserID => $Rows) {
                $RoleIDs = consolidateArrayValuesByKey($Rows, 'RoleID');
                $Key = $Keys[$UserID];
                Gdn::cache()->store($Key, $RoleIDs);
                $UserRoles[$Key] = $RoleIDs;
            }
        }

        $AllRoles = self::roles(); // roles indexed by role id.

        // Skip personal info roles
        if (!checkPermission('Garden.PersonalInfo.View')) {
            $AllRoles = array_filter($AllRoles, 'self::FilterPersonalInfo');
        }

        // Join the users.
        foreach ($Users as &$User) {
            $UserID = val($UserIDColumn, $User);
            $Key = $Keys[$UserID];

            $RoleIDs = val($Key, $UserRoles, array());
            $Roles = array();
            foreach ($RoleIDs as $RoleID) {
                if (!array_key_exists($RoleID, $AllRoles)) {
                    continue;
                }
                $Roles[$RoleID] = $AllRoles[$RoleID]['Name'];
            }
            setValue($RolesColumn, $User, $Roles);
        }
    }

    /**
     * Delete a role.
     *
     * @param string|unknown_type $RoleID
     * @param bool|unknown_type $ReplacementRoleID
     */
    public function delete($RoleID, $ReplacementRoleID) {
        // First update users that will be orphaned
        if (is_numeric($ReplacementRoleID) && $ReplacementRoleID > 0) {
            $this->SQL
                ->options('Ignore', true)
                ->update('UserRole')
                ->join('UserRole urs', 'UserRole.UserID = urs.UserID')
                ->groupBy('urs.UserID')
                ->having('count(urs.RoleID) =', '1', true, false)
                ->set('UserRole.RoleID', $ReplacementRoleID)
                ->where(array('UserRole.RoleID' => $RoleID))
                ->put();
        } else {
            $this->SQL->delete('UserRole', array('RoleID' => $RoleID));
        }

        // Remove permissions for this role.
        $PermissionModel = Gdn::permissionModel();
        $PermissionModel->delete($RoleID);

        // Remove the role
        $this->SQL->delete('Role', array('RoleID' => $RoleID));
    }
}

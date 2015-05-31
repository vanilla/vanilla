<?php
/**
 * A role model you can look up to.
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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
    public static $Roles = NULL;

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
        $this->FireEvent('Init');
    }

    /**
     * Clear the roles cache.
     */
    public function ClearCache() {
        $Key = 'Roles';
        Gdn::Cache()->Remove($Key);
    }

    /**
     * Define a role.
     *
     * @param $Values
     */
    public function Define($Values) {
        if (array_key_exists('RoleID', $Values)) {
            $RoleID = $Values['RoleID'];
            unset($Values['RoleID']);

            $this->SQL->Replace('Role', $Values, array('RoleID' => $RoleID), TRUE);
        } else {
            // Check to see if there is a role with the same name.
            $RoleID = $this->SQL->GetWhere('Role', array('Name' => $Values['Name']))->Value('RoleID', NULL);

            if (is_null($RoleID)) {
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
     * Use with array_filter to remove PersonalInfo roles.
     *
     * @var mixed $Roles Role name (string) or $Role data (array or object).
     * @return bool Whether role is NOT personal info (FALSE = remove it, it's personal).
     */
    public static function FilterPersonalInfo($Role) {
        if (is_string($Role)) {
            $Role = array_shift(self::GetByName($Role));
        }

        return (GetValue('PersonalInfo', $Role)) ? FALSE : TRUE;
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
     * Get all of the roles including their ranking permissions.
     *
     * @return Gdn_DataSet Returns all of the roles with the ranking permissions.
     */
    public function GetWithRankPermissions() {
        $this->SQL
            ->Select('r.*')
            ->From('Role r')
            ->LeftJoin('Permission p', 'p.RoleID = r.RoleID and p.JunctionID is null')
            ->OrderBy('Sort', 'asc');

        foreach ($this->RankPermissions as $Permission) {
            $this->SQL->Select("`$Permission`", '', $Permission);
        }

        $Result = $this->SQL->Get();
        return $Result;
    }

    /**
     * Returns an array of RoleID => RoleName pairs.
     *
     * @return array
     */
    public function GetArray() {
        $RoleData = $this->Get()->ResultArray();
        $Result = array_column($RoleData, 'Name', 'RoleID');

        return $Result;
    }

    /**
     * Get the roles that the current user is allowed to assign to another user.
     *
     * @return array Returns an array in the format `[RoleID => 'Role Name']`.
     */
    public function GetAssignable() {
        // Administrators can assign all roles.
        if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            return $this->GetArray();
        }
        // Users that can't edit other users can't assign any roles.
        if (!Gdn::Session()->CheckPermission('Garden.Users.Edit')) {
            return array();
        }

        $Sql = Gdn::SQL();

        $Sql->Select('r.RoleID, r.Name')
            ->From('Role r')
            ->LeftJoin('Permission p', 'p.RoleID = r.RoleID and p.JunctionID is null'); // join to global permissions

        // Community managers can assign permissions they have,
        // but other users can't assign any ranking permissions.
        $CM = Gdn::Session()->CheckPermission('Garden.Community.Manage');
        foreach ($this->RankPermissions as $Permission) {
            if (!$CM || !Gdn::Session()->CheckPermission($Permission)) {
                $Sql->Where("coalesce(`$Permission`, 0)", '0', FALSE, FALSE);
            }
        }

        $Roles = $Sql->Get()->ResultArray();
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
            $roleData = Gdn::SQL()->Select('RoleID')->GetWhere('Role', array('Type' => $type))->ResultArray();
            $roleIDs = array_column($roleData, 'RoleID');
        } catch (Exception $ex) {
            // This exception happens when the type column hasn't been added to GDN_Role yet.
            $roleIDs = array();
        }

        // This method has to be backwards compatible with the old config roles.
        switch ($type) {
            case self::TYPE_APPLICANT:
                $backRoleIDs = (array)C('Garden.Registration.ApplicantRoleID', null);
                break;
            case self::TYPE_GUEST:
                $guestRoleData = Gdn::SQL()->GetWhere('UserRole', array('UserID' => 0))->ResultArray();
                $backRoleIDs = array_column($guestRoleData, 'RoleID');
                break;
            case self::TYPE_MEMBER:
                $backRoleIDs = (array)C('Garden.Registration.DefaultRoles', null);
                break;
            case self::TYPE_UNCONFIRMED:
                $backRoleIDs = (array)C('Garden.Registration.ConfirmEmailRole', null);
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
        $roleData = Gdn::SQL()->GetWhere('Role', array('Type is not null' => ''))->ResultArray();
        foreach ($roleData as $row) {
            $result[$row['Type']][] = $row['RoleID'];
        }

        // Add the backwards compatible roles.
        $result[self::TYPE_APPLICANT] = array_merge(
            $result[self::TYPE_APPLICANT],
            (array)C('Garden.Registration.ApplicantRoleID', null)
        );

        $guestRoleIDs = Gdn::SQL()->GetWhere('UserRole', array('UserID' => 0))->ResultArray();
        $guestRoleIDs = array_column($guestRoleIDs, 'RoleID');
        $result[self::TYPE_GUEST] = array_merge(
            $result[self::TYPE_GUEST],
            $guestRoleIDs
        );

        $result[self::TYPE_MEMBER] = array_merge(
            $result[self::TYPE_MEMBER],
            (array)C('Garden.Registration.DefaultRoles', array())
        );

        $result[self::TYPE_UNCONFIRMED] = array_merge(
            $result[self::TYPE_UNCONFIRMED],
            (array)C('Garden.Registration.ConfirmEmailRole', null)
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
     * public function GetEditablePermissions() {
     * return $this->SQL
     * ->Select()
     * ->From('Role')
     * ->Where('EditablePermissions', '1')
     * ->OrderBy('Sort', 'asc')
     * ->Get();
     * }
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
     * Return all roles matching a specific type.
     *
     * @param string $type Type slug to match role records against.
     * @return Gdn_DataSet
     */
    public function getByType($type) {
        return $this->SQL->Select()
            ->From('Role')
            ->Where('Type', $type)
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

    /**
     * Get the permissions for one or more roles.
     *
     * @param int|array $RoleID One or more role IDs to get the permissions for.
     * @return array Returns an array of permissions.
     */
    public function GetPermissions($RoleID) {
        $PermissionModel = Gdn::PermissionModel();
        $roleIDs = (array)$RoleID;

        foreach ($roleIDs as $ID) {
            $Role = self::Roles($ID);
            $LimitToSuffix = GetValue('CanSession', $Role, TRUE) ? '' : 'View';
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

    /**
     * Get the current number of applicants waiting to be approved.
     *
     * @param bool $Force Whether or not to force a cache refresh.
     * @return int Returns the number of applicants or 0 if the registration method isn't set to approval.
     */
    public function GetApplicantCount($Force = FALSE) {
        if (C('Garden.Registration.Method') != 'Approval') {
            return 0;
        }

        $CacheKey = 'Moderation.ApplicantCount';

        if ($Force)
            Gdn::Cache()->Remove($CacheKey);

        $applicantRoleIDs = static::getDefaultRoles(self::TYPE_APPLICANT);

        $Count = Gdn::Cache()->Get($CacheKey);
        if ($Count === Gdn_Cache::CACHEOP_FAILURE) {
            $Count = Gdn::SQL()
                ->Select('u.UserID', 'count', 'UserCount')
                ->From('User u')
                ->Join('UserRole ur', 'u.UserID = ur.UserID')
                ->Where('ur.RoleID', $applicantRoleIDs)
                ->Where('u.Deleted', '0')
                ->Get()->Value('UserCount', 0);

            Gdn::Cache()->Store($CacheKey, $Count, array(
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
     * Get a role by name.
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

    /**
     *
     *
     * @param null $RoleID
     * @param bool $Force
     * @return array|mixed|null|type
     */
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

    /**
     * Save role data.
     *
     * @param array $FormPostValues
     * @return bool|mixed
     * @throws Exception
     */
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
            $Permissions = ArrayValue('Permission', $FormPostValues);
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

    /**
     *
     *
     * @param $Users
     * @param string $UserIDColumn
     * @param string $RolesColumn
     */
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
        foreach ($Keys as $UserID => $Key) {
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

        // Skip personal info roles
        if (!CheckPermission('Garden.PersonalInfo.View')) {
            $AllRoles = array_filter($AllRoles, 'self::FilterPersonalInfo');
        }

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

    /**
     * Delete a role.
     *
     * @param string|unknown_type $RoleID
     * @param bool|unknown_type $ReplacementRoleID
     */
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
        } else {
            $this->SQL->Delete('UserRole', array('RoleID' => $RoleID));
        }

        // Remove permissions for this role.
        $PermissionModel = Gdn::PermissionModel();
        $PermissionModel->Delete($RoleID);

        // Remove the role
        $this->SQL->Delete('Role', array('RoleID' => $RoleID));
    }
}

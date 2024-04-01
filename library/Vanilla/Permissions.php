<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Vanilla\Models\PermissionFragmentSchema;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;
use Webmozart\Assert\Assert;

/**
 * Compile, manage and check user permissions.
 */
class Permissions implements \JsonSerializable
{
    use PermissionsTranslationTrait;

    /** @var string Mode used when you want to know only if the user has the global permission, and not a resource specific one. */
    const CHECK_MODE_GLOBAL_ONLY = "checkGlobalOnly";

    /** @var string Standard mode for checking. If the use has the global or any resource specific permission, will return it. */
    const CHECK_MODE_GLOBAL_OR_RESOURCE = "checkGlobalOrResource";

    /** @var string Check only on the specific resource. */
    const CHECK_MODE_RESOURCE_ONLY = "checkResource";

    /** @var string Check only on the specific resource. */
    const CHECK_MODE_RESOURCE_IF_JUNCTION = "checkResourceIfJunction";

    /** @var int Items with this junctionID are special and are treated as global permissions. */
    public const GLOBAL_JUNCTION_ID = -1;

    const PERMISSION_CHECK_MODES = [
        self::CHECK_MODE_GLOBAL_ONLY,
        self::CHECK_MODE_GLOBAL_OR_RESOURCE,
        self::CHECK_MODE_RESOURCE_ONLY,
    ];

    public const PERMISSION_SYSTEM = "system";

    /** Array of ranked permissions. */
    private const RANKED_PERMISSIONS = [
        "Garden.Admin.Allow" => 6, // virtual permission for isAdmin
        "Garden.Settings.Manage" => 5,
        "Garden.Community.Manage" => 4,
        "Garden.Moderation.Manage" => 3,
        "Garden.Curation.Manage" => 2,
        "Garden.SignIn.Allow" => 1,
    ];

    // These permissions used to exist, but are no longer created in new applications.
    private const LEGACY_PERMISSION_NAMES = [
        "Moderation.Profiles.Edit" => true,
        "Garden.Admin.Only" => true, // Used as "non-existant" permission so that only users with the admin flag can access.
        self::PERMISSION_SYSTEM => true, // Also used as an admin flag check.
        "Plugins.Online.ViewHidden" => true, // This permission exists for some reason but I haven't seen it actually defined anywhere.
        "Moderation.UserNotes.View" => true,
        "Moderation.Reactions.Edit" => true, // Legacy reactions permission.
        "Moderation.Warnings.Add" => true, // Legacy reactions permission.
        "Moderation.Warnings.View" => true, // Maybe a typo?
        "Moderation.Signatures.Edit" => true,
        "Moderation.ModerationQueue.Manage" => true, // Legacy moderation permission.
        "Moderation.Spam.Manage" => true, // Legacy moderation permission.
        "Garden.Profile.EditPhotos" => true,
        "Moderation.Users.Ban" => true,
        "Moderation.UserNotes.Add" => true,
    ];

    /**
     * An array of ranked permissions that won't ever change. Suitable for storing in the config.
     */
    private const RANKED_PERMISSION_ALIASES = [
        self::RANK_MEMBER => "Garden.SignIn.Allow",
        self::RANK_MODERATOR => "Garden.Moderation.Manage",
        self::RANK_COMMUNITY_MANAGER => "Garden.Community.Manage",
        self::RANK_ADMIN => "Garden.Settings.Manage",
    ];

    const BAN_BANNED = "!banned";
    const BAN_DELETED = "!deleted";
    const BAN_UPDATING = "!updating";
    const BAN_PRIVATE = "!private";
    const BAN_2FA = "!2fa";
    const BAN_CSRF = "!csrf";
    const BAN_UNINSTALLED = "!uninstalled";
    const BAN_ROLE_TOKEN = "!roleToken";

    public const RANK_EVERYONE = "everyone";
    public const RANK_MEMBER = "member";
    public const RANK_MODERATOR = "moderator";
    public const RANK_COMMUNITY_MANAGER = "communityManager";
    public const RANK_ADMIN = "admin";

    /**
     * Global permissions are stored as numerical indexes.
     * Per-ID permissions are stored as associative keys. The key is the permission name and the values are the IDs.
     * @var array
     */
    private $permissions = [];

    /** @var bool */
    private $isAdmin = false;

    private $isSysAdmin = false;

    /**
     * @var array An array of bans that override all permissions a user may have.
     */
    private $bans = [];

    /**
     * Track junctions.
     *
     * @var array $junctions
     * @example
     * [
     *      'Category' => [1, 49, 100],
     *      'knowledgeBase' => [53, 60, 100],
     * ]
     */
    private $junctions = [];

    /**
     * @var array Track aliases for junction IDs.
     *
     * @example
     * [
     *     'Category' => [
     *         // ALIAS => ACTUAL
     *         5 => 21,
     *         15 => 24,
     *     ]
     * ]
     */
    private $junctionAliases = [];

    /** @var string[] */
    private $validPermissionNames = [];

    /**
     * Permissions constructor.
     *
     * @param array $permissions The internal permissions array, usually from a cache.
     */
    public function __construct($permissions = [])
    {
        $this->nameScheme = new DelimitedScheme(".", new CamelCaseScheme());
        $this->setPermissions($permissions);
    }

    /**
     * @param string[] $validPermissionNames
     */
    public function setValidPermissionNames(array $validPermissionNames): void
    {
        $this->validPermissionNames = array_merge(
            array_flip($validPermissionNames),
            self::RANKED_PERMISSIONS,
            self::LEGACY_PERMISSION_NAMES
        );
    }

    /**
     * Check if a permission name is valid.
     *
     * @param string $permission
     */
    public function checkValidPermissionName(string $permission): void
    {
        if (stringBeginsWith($permission, "!")) {
            // We're checking ban.
            return;
        }

        if (stringBeginsWith($permission, "__")) {
            // Sometimes we set a "marker" permission starting with a __
            return;
        }

        if (empty($this->validPermissionNames)) {
            // We haven't had permissions loaded yet.
            return;
        }

        if (!isset($this->validPermissionNames[$permission])) {
            trigger_error("Invalid permission name: '$permission'", E_USER_WARNING);
        }
    }

    /**
     * @return array
     */
    public function getJunctions(): array
    {
        return $this->junctions;
    }

    /**
     * Add a set of junctions.
     *
     * @param array $junctions
     *
     * @return $this
     */
    public function addJunctions(array $junctions): Permissions
    {
        $this->junctions = array_merge_recursive($this->junctions, $junctions);
        return $this;
    }

    /**
     * Add a junction.
     *
     * @param string $junctionTable
     * @param int $junctionID
     *
     * @return $this
     */
    public function addJunction(string $junctionTable, int $junctionID): Permissions
    {
        $junctions = $this->junctions[$junctionTable] ?? [];
        if (!in_array($junctionID, $junctions, true)) {
            $junctions[] = $junctionID;
        }
        $this->junctions[$junctionTable] = $junctions;
        return $this;
    }

    /**
     * @return array
     */
    public function getJunctionAliases(): array
    {
        return $this->junctionAliases;
    }

    /**
     * Add some junction aliases.
     *
     * @param array $junctionAliases
     *
     * @return $this
     */
    public function addJunctionAliases(array $junctionAliases): Permissions
    {
        $this->junctionAliases = array_merge_recursive($this->junctionAliases, $junctionAliases);
        return $this;
    }

    /**
     * Add a permission.
     *
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View).
     * @param int|array $ids One or more IDs of foreign objects (e.g. category IDs).
     * @return $this
     */
    public function add($permission, $ids): self
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        if (!array_key_exists($permission, $this->permissions) || !is_array($this->permissions[$permission])) {
            $this->permissions[$permission] = [];
        }

        $this->permissions[$permission] = array_unique(array_merge($this->permissions[$permission], $ids));

        return $this;
    }

    /**
     * Add a ban to the ban list.
     *
     * Bans override all permissions unless there is an exception. There are two ways a ban can be excepted.
     *
     * 1. When calling {@link Permissions::hasAny()} or {@link Permissions::hasAll()} you can pass the name of a ban as
     * a permission and it will be ignored.
     * 2. Bans can be added with the "except" key. This takes an array of permission names and will not apply to users
     * with any of those permissions.
     *
     * @param string $name The name of the ban. Use one of the **BAN_*** constants. If you add a ban with the same name
     * as an existing ban then the existing ban will be removed first.
     * @param array $ban The ban to add. This as an array with the following optional keys:
     *
     * - **msg**: A message associated with the ban.
     * - **code**: An HTTP response code associated with the ban.
     * - **except**: An array of permission names that override the ban.
     * @param bool $prepend If **true**, the ban will be prepended to the ban list instead of appending it.
     * @return $this
     */
    public function addBan(string $name, array $ban = [], bool $prepend = false): self
    {
        if (substr($name, 0, 1) !== "!") {
            throw new \InvalidArgumentException('Ban names must start with "!".', 500);
        }
        $name = strtolower($name);
        $ban += ["msg" => "Permission denied.", "code" => 401, "except" => []];

        unset($this->bans[$name]);

        if ($prepend) {
            $this->bans = array_merge([$name => $ban], $this->bans);
        } else {
            $this->bans[$name] = $ban;
        }

        return $this;
    }

    /**
     * Remove a ban from the ban list.
     *
     * @param string $name The name of the ban to remove.
     * @return $this
     */
    public function removeBan(string $name): self
    {
        $name = strtolower($name);
        unset($this->bans[$name]);
        return $this;
    }

    /**
     * Compile raw permission rows into a formatted array of granted permissions.
     *
     * @param array $permissions Rows from the Permissions database table.
     * @return $this
     */
    public function compileAndLoad(array $permissions): self
    {
        foreach ($permissions as $row) {
            // Store the junction ID, if we have one.
            $junctionID = $row["JunctionID"] ?? null;
            $junctionTable = $row["JunctionTable"] ?? null;

            // Handle root resourceIDs.
            if ($junctionID === self::GLOBAL_JUNCTION_ID) {
                $junctionID = null;
                $junctionTable = null;
            }

            if ($junctionID !== null && $junctionTable !== null) {
                $this->addJunction($junctionTable, $junctionID);
            }

            // Clear out any non-permission fields.
            unset(
                $row["PermissionID"],
                $row["RoleID"],
                $row["JunctionTable"],
                $row["JunctionColumn"],
                $row["JunctionID"]
            );

            // Iterate through the row's individual permissions.
            foreach ($row as $permission => $value) {
                $hasPermission = $value != 0;
                if (!$hasPermission) {
                    // If the user doesn't have this permission, move on to the next one.
                    continue;
                }

                if ($junctionID === null) {
                    $this->set($permission, true);
                } else {
                    $this->add($permission, $junctionID);
                }
            }
        }

        return $this;
    }

    /**
     * Grab the current permissions.
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Determine if the current user is banned.
     *
     * @param array $permissions An optional array of permissions being checked. Any permission starting with "!" means
     * that a ban with that name is ignored.
     * @return bool Returns **true** if the user is banned or **false** otherwise.
     * @see Permissions::addBan(), Permissions::getBan()
     */
    public function isBanned(array $permissions = []): bool
    {
        $ban = $this->getBan($permissions);
        return $ban !== null;
    }

    /**
     * Get the currently active ban.
     *
     * @param array $permissions An optional array of permissions being checked. Any permission starting with "!" means
     * that a ban with that name is ignored.
     * @return array|null Returns the currently active ban or **null** if there is no active ban.
     * @see Permissions::addBan()
     */
    public function getBan(array $permissions = [])
    {
        $permissions = array_change_key_case(array_flip($permissions));

        foreach ($this->bans as $name => $ban) {
            if (isset($permissions[$name])) {
                // The permission check is overriding the ban.
                continue;
            } elseif (!empty($ban["except"])) {
                // There is an exception, so see if any of those permissions apply.
                foreach ((array) $ban["except"] as $permission) {
                    if ($this->hasInternal($permission)) {
                        continue 2;
                    }
                }
            }
            // There was no exception to the ban so we are banned.
            $ban["type"] = $name;
            return $ban;
        }

        return null;
    }

    /**
     * Determine if the permission is present.
     *
     * @param string $permission Permission slug to check the value for (e.g. Vanilla.Discussions.View).
     * @param int|null $id Foreign object ID to validate the permission against (e.g. a category ID).
     * @param string|null $checkMode One of {self::PERMISSION_CHECK_MODES}
     * @param string|null $junctionTable Provide a junction table to check. Needed for some checks.
     * @return bool
     */
    public function has(
        $permission,
        $id = null,
        string $checkMode = self::CHECK_MODE_GLOBAL_OR_RESOURCE,
        ?string $junctionTable = null
    ): bool {
        return $this->hasAll((array) $permission, $id, $checkMode, $junctionTable);
    }

    /**
     * Determine if all of the provided permissions are present.
     *
     * @param array $permissions Permission slugs to check the value for (e.g. Vanilla.Discussions.View).
     * @param int|null $id Foreign object ID to validate the permissions against (e.g. a category ID).
     * @param string|null $checkMode One of {self::PERMISSION_CHECK_MODES}
     * @param string|null $junctionTable Provide a junction table to check. Needed for some checks.
     *
     * @return bool
     */
    public function hasAll(
        array $permissions,
        $id = null,
        string $checkMode = self::CHECK_MODE_GLOBAL_OR_RESOURCE,
        ?string $junctionTable = null
    ): bool {
        // Check if new permission is used, try to revert it back to old permission style
        foreach ($permissions as &$permissionName) {
            if ($this->isTwoPartPermission($permissionName)) {
                $permissionName = $this->untranslatePermission($permissionName);
            }
        }
        // Look for the bans first.
        if ($this->isBanned($permissions)) {
            return false;
        }

        if (!in_array(self::PERMISSION_SYSTEM, $permissions) && $this->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasInternal($permission, $id, $checkMode, $junctionTable) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the provided permissions are present.
     *
     * @param array $permissions Permission slugs to check the value for (e.g. Vanilla.Discussions.View).
     * @param int|null $id Foreign object ID to validate the permissions against (e.g. a category ID).
     * @param string|null $checkMode One of {self::PERMISSION_CHECK_MODES}
     * @param string|null $junctionTable Provide a junction table to check. Needed for some checks.
     *
     * @return bool
     */
    public function hasAny(
        array $permissions,
        $id = null,
        string $checkMode = self::CHECK_MODE_GLOBAL_OR_RESOURCE,
        ?string $junctionTable = null
    ): bool {
        // Check if new permission is used, try to revert it back to old permission style
        foreach ($permissions as &$permissionName) {
            if ($this->isTwoPartPermission($permissionName)) {
                $permissionName = $this->untranslatePermission($permissionName);
            }
        }
        // Look for the bans first.
        if ($this->isBanned($permissions)) {
            return false;
        }

        if (!in_array(self::PERMISSION_SYSTEM, $permissions) && $this->isAdmin()) {
            return true;
        }

        $nullCount = 0;
        foreach ($permissions as $permission) {
            $has = $this->hasInternal($permission, $id, $checkMode, $junctionTable);
            if ($has === true) {
                return true;
            } elseif ($has === null) {
                $nullCount++;
            }
        }

        return $nullCount === count($permissions);
    }

    /**
     * Determine if the admin flag is set.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin === true;
    }

    /**
     * Determine if the sysAdmin flag is set.
     *
     * @return bool
     */
    public function isSysAdmin(): bool
    {
        return $this->isSysAdmin === true;
    }

    /**
     * Merge in data from another Permissions instance.
     *
     * @param Permissions $source The source Permissions instance to import permissions from.
     * @return $this
     */
    public function merge(Permissions $source): self
    {
        $this->setPermissions(array_merge_recursive($this->permissions, $source->getPermissions()));

        $this->addJunctions($source->getJunctions());
        $this->addJunctionAliases($source->getJunctionAliases());
        $this->setAdmin($this->isAdmin() || $source->isAdmin());
        $this->setSysAdmin($this->isSysAdmin() || $source->isSysAdmin());

        return $this;
    }

    /**
     * Set global or replace per-ID permissions.
     *
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View).
     * @param bool|array $value A single value for global permissions or an array of foreign object IDs for per-ID permissions.
     * @return $this
     */
    public function overwrite(string $permission, $value): self
    {
        if ($value === true || $value === false) {
            $this->set($permission, $value);
        } elseif (is_array($value)) {
            // Set the resource specific permissions.
            $this->permissions[$permission] = $value;

            if (empty($value)) {
                // Sometimes this method is used to clear out permissions.
                // If the permission is in as global, we need to clear it as well.
                $this->set($permission, false);
            }
        }

        return $this;
    }

    /**
     * Remove a permission.
     *
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View).
     * @param int|array $ids One or more IDs of foreign objects (e.g. category IDs).
     * @return $this;
     */
    public function remove($permission, $ids): self
    {
        if (array_key_exists($permission, $this->permissions)) {
            if (!is_array($ids)) {
                $ids = [$ids];
            }

            foreach ($ids as $currentID) {
                $index = array_search($currentID, $this->permissions[$permission]);

                if ($index !== false) {
                    unset($this->permissions[$permission][$index]);
                }
            }
        }

        return $this;
    }

    /**
     * Add a global permission.
     *
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View).
     * @param bool $value Toggle value for the permission: true for granted, false for revoked.
     * @return $this
     */
    public function set($permission, $value): self
    {
        $exists = array_search($permission, $this->permissions);

        if ($value) {
            if ($exists === false) {
                $this->permissions[] = $permission;
            }
        } elseif ($exists !== false) {
            unset($this->permissions[$exists]);
        }

        return $this;
    }

    /**
     * Set the admin flag.
     *
     * @param bool $isAdmin Is the user an administrator?
     * @return $this
     */
    public function setAdmin($isAdmin): self
    {
        $this->isAdmin = (bool) $isAdmin;
        return $this;
    }

    /**
     * Set the sysAdmin flag.
     *
     * @param bool $isSysAdmin Is the user an system administrator?
     * @return $this
     */
    public function setSysAdmin($isSysAdmin): self
    {
        $this->isSysAdmin = (bool) $isSysAdmin;
        return $this;
    }

    /**
     * Set the permission array.
     *
     * @param array $permissions A properly-formatted permissions array.
     * @return $this
     */
    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    /**
     * Given a ranked permission alias, resolve it to a full permission.
     *
     * @param string $permission
     * @return string
     */
    public static function resolveRankedPermissionAlias(string $permission): string
    {
        if (isset(self::RANKED_PERMISSION_ALIASES[$permission])) {
            $permission = self::RANKED_PERMISSION_ALIASES[$permission];
        }
        return $permission;
    }

    /**
     * Check the given permission, but also return true if the user has a higher permission.
     *
     * @param string $permission The permission to check.
     * @return boolean True on valid authorization, false on failure to authorize
     */
    public function hasRanked(string $permission): bool
    {
        $permission = self::resolveRankedPermissionAlias($permission);

        if (!isset(self::RANKED_PERMISSIONS[$permission])) {
            return $this->has($permission);
        } else {
            $minRank = self::RANKED_PERMISSIONS[$permission];

            /**
             * If the current permission is in our ranked list, iterate through the list, starting from the highest
             * ranked permission down to our target permission, and determine if any are applicable to the current
             * user.  This is done so that a user with a permission like Garden.Settings.Manage can still validate
             * permissions against a Garden.Moderation.Manage permission check, without explicitly having it
             * assigned to their role.
             */
            foreach (self::RANKED_PERMISSIONS as $name => $rank) {
                if ($rank < $minRank) {
                    return false;
                } elseif ($this->has($name)) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Return the highest ranking permission the user has.
     *
     * @return string
     */
    public function getRankingPermission(): string
    {
        foreach (self::RANKED_PERMISSIONS as $name => $rank) {
            if ($this->has($name)) {
                return $name;
            }
        }
        return "";
    }

    /**
     * Get the names of the ranked permissions, suitable for drop downs and whatnot.
     *
     * @return array
     */
    public static function getRankedPermissionAliases(bool $includeEveryone = false): array
    {
        $aliases = array_keys(self::RANKED_PERMISSION_ALIASES);
        if ($includeEveryone) {
            array_unshift($aliases, self::RANK_EVERYONE);
        }
        $result = array_combine($aliases, $aliases);
        return $result;
    }

    /**
     * Compare this permission set to another set to see which has the higher rank.
     *
     * @param Permissions $permissions The permissions to compare to.
     * @return int Returns -1, 0, 1 for less than, equal, or greater than.
     */
    public function compareRankTo(Permissions $permissions): int
    {
        $myRank = $this->getRankingPermission();
        $otherRank = $permissions->getRankingPermission();

        $r = (self::RANKED_PERMISSIONS[$myRank] ?? 0) <=> (self::RANKED_PERMISSIONS[$otherRank] ?? 0);
        return $r;
    }

    /**
     * Check just the permissions array, ignoring overrides from admin/bans.
     *
     * @param string $permission The permission to check.
     * @param int|null $junctionID The database ID of a non-global permission or **null** if this is a global check.
     * @param string|null $checkMode One of {self::PERMISSION_CHECK_MODES}
     * @param string|null $junctionTable Provide a junction table to check. Needed for some checks.
     *
     * @return bool|null Returns **true** if the user has the permission, **false** if they don't, or **null** if the permissions isn't applicable.
     */
    private function hasInternal(
        $permission,
        $junctionID = null,
        string $checkMode = self::CHECK_MODE_GLOBAL_OR_RESOURCE,
        ?string $junctionTable = null
    ) {
        $this->checkValidPermissionName($permission);
        if ($checkMode === self::CHECK_MODE_RESOURCE_IF_JUNCTION) {
            Assert::notNull(
                $junctionTable,
                'When using "CHECK_MODE_RESOURCE_IF_JUNCTION" you must provide a junctionTable.'
            );
            if ($junctionID !== null) {
                $junctionID = $this->resolveJuntionAlias($junctionTable, $junctionID);
            }
        }

        if ($permission === self::RANK_EVERYONE) {
            return true;
        }

        // Fix the mode of checking.
        $hasID = !in_array($junctionID, [null, self::GLOBAL_JUNCTION_ID, ""], true);
        if ($hasID && $checkMode !== self::CHECK_MODE_RESOURCE_IF_JUNCTION) {
            // We have a resource so we should check that.
            $checkMode = self::CHECK_MODE_RESOURCE_ONLY;
        }

        if (strcasecmp($permission, "admin") === 0) {
            return $this->isAdmin();
        } elseif (substr($permission, 0, 1) === "!") {
            // This is a ban so skip it.
            return null;
        } else {
            $hasGlobal = array_search($permission, $this->permissions) !== false;
            $hasResource =
                array_key_exists($permission, $this->permissions) && is_array($this->permissions[$permission])
                    ? array_search($junctionID, $this->permissions[$permission]) !== false
                    : false;
            $hasAnyResourceSpecific = !empty($this->permissions[$permission]);

            switch ($checkMode) {
                case self::CHECK_MODE_RESOURCE_IF_JUNCTION:
                    $junctionIDs = $this->junctions[$junctionTable] ?? [];
                    $hasJunction = in_array($junctionID, $junctionIDs, true);
                    return $hasJunction ? $hasResource : $hasGlobal;
                case self::CHECK_MODE_RESOURCE_ONLY:
                    return $hasResource;
                case self::CHECK_MODE_GLOBAL_ONLY:
                    return $hasGlobal;
                case self::CHECK_MODE_GLOBAL_OR_RESOURCE:
                default:
                    return $hasGlobal || $hasAnyResourceSpecific;
            }
        }
    }

    /**
     * Resolve a junction alias, falling back to the initial if there is no alias.
     *
     * @param string $junctionTable
     * @param int $junctionID
     *
     * @return int
     */
    private function resolveJuntionAlias(string $junctionTable, int $junctionID): int
    {
        $junctionsToCheck = $this->junctionAliases[$junctionTable] ?? [];
        return $junctionsToCheck[$junctionID] ?? $junctionID;
    }

    /**
     * @return array[] An array of permission fragments. See PermissionFragmentSchema
     */
    public function asPermissionFragments(): array
    {
        $resultsByTypeAndID = [
            "global" => [
                "type" => "global",
                "id" => null,
                "permissions" => [],
            ],
        ];

        /**
         * Push an item into the permission array.
         *
         * @param string $permissionName
         * @param int|null $resourceID
         */
        $pushItem = function (string $permissionName, ?int $resourceID = null) use (&$resultsByTypeAndID) {
            // Map permission name to API style.
            $permissionName = $this->renamePermission($permissionName);
            $junctionTable =
                $this->getJunctionTableForPermission($permissionName) ?? PermissionFragmentSchema::TYPE_GLOBAL;
            $type = $junctionTable && $resourceID !== null ? $junctionTable : PermissionFragmentSchema::TYPE_GLOBAL;
            $typeAndID = $type . $resourceID;

            if (!empty($resultsByTypeAndID[$typeAndID])) {
                $resultsByTypeAndID[$typeAndID]["permissions"][$permissionName] = true;
            } else {
                $resultsByTypeAndID[$typeAndID] = [
                    "type" => $type,
                    "id" => $resourceID,
                    "permissions" => [$permissionName => true],
                ];
            }
        };

        // Push all of the permissions in.
        foreach ($this->permissions as $key => $value) {
            if (is_array($value)) {
                // We have some resource specific information.
                $permissionName = $key;
                if ($this->isPermissionDeprecated($permissionName)) {
                    continue;
                }
                foreach ($value as $resourceID) {
                    $pushItem($permissionName, $resourceID);
                }
            } else {
                // This is a global permission.
                $permissionName = $value;
                if ($this->isPermissionDeprecated($value)) {
                    continue;
                }
                $pushItem($permissionName);
            }
        }

        foreach ($resultsByTypeAndID as &$result) {
            $permissions = $this->consolidatePermissions($result["permissions"]);
            ksort($permissions);
            $result["permissions"] = $permissions;
        }

        return array_values($resultsByTypeAndID);
    }

    /**
     * Get junction values for JSON.
     *
     * @param array $junctions
     * @return array|\stdClass
     */
    private function getJsonObjectOutput(array $junctions)
    {
        if (empty($junctions)) {
            return new \stdClass();
        }

        $camelCase = new CamelCaseScheme();
        return $camelCase->convertArrayKeys($junctions);
    }

    /**
     * Get our output for the API.
     *
     * @param bool $withJunctions
     * @return array
     */
    public function asApiOutput(bool $withJunctions): array
    {
        $result = [
            "permissions" => $this->asPermissionFragments(),
            "isAdmin" => $this->isAdmin(),
            "isSysAdmin" => $this->isSysAdmin(),
        ];

        if ($withJunctions) {
            $result["junctions"] = $this->getJsonObjectOutput($this->getJunctions());
            $result["junctionAliases"] = $this->getJsonObjectOutput($this->getJunctionAliases());
        }
        return $result;
    }

    /**
     * Get an array representation of the permissions suitable for the page.
     *
     * @return array Returns an array with permissions, bans, and the isAdmin flag.
     */
    public function jsonSerialize(): array
    {
        // Translate the internal permissions into a better one for json.
        $permissions = [];
        foreach ($this->permissions as $key => $value) {
            if (is_string($value)) {
                $permissions[$this->renamePermission($value)] = true;
            } elseif (is_array($value)) {
                $newKey = $this->renamePermission($key);
                if (empty($permissions[$newKey])) {
                    $permissions[$newKey] = array_map("intval", $value);
                }
            }
        }
        $result = [
            "permissions" => $permissions,
            "bans" => $this->bans,
            "isAdmin" => $this->isAdmin,
        ];

        return $result;
    }
}

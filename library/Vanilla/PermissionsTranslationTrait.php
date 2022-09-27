<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Gdn;
use Vanilla\Utility\NameScheme;
use PermissionModel;

/**
 * Translates permission names between the old and new formats.
 *
 * Vanilla is changing the naming convention of permissions so this class is necessary to aid in that.
 */
trait PermissionsTranslationTrait
{
    /** @var NameScheme */
    private $nameScheme;

    /**
     * TECH DEBT HERE
     * Currently our permission caches and main permission object do not keep the junction table that a permission maps to.
     *
     * TEMPORARY ASSUMPTION
     * This _assumes_ that each permission name only has 1 junction table to map to. This may not hold true in the future.
     * If that ever becomes the case, this mapping will need to be removed, and the junction tables stored in Permission object & cache.
     *
     * @var array [ JunctionTable => [permission.name, permission.otherName] ]
     */
    private $junctionTableMappings = [
        "knowledgeBase" => ["kb.view", "articles.add"],
        "category" => [
            "comments.add",
            "comments.delete",
            "comments.edit",
            "discussions.add",
            "discussions.manage",
            "discussions.moderate",
            "discussions.view",
            "discussions.edit",
            "discussions.announce",
            "discussions.sink",
            "discussions.close",
            "discussions.delete",
            "events.view",
            "events.manage",
        ],
    ];

    /**
     * Get the "assumed" junction table name
     *
     * @param string $permissionName
     * @return string|null
     */
    private function getJunctionTableForPermission(string $permissionName): ?string
    {
        foreach ($this->junctionTableMappings as $table => $permissions) {
            if (in_array($permissionName, $permissions)) {
                return $table;
            }
        }

        return null;
    }

    /** @var array Groups of permissions that can be consolidated into one. */
    private $consolidatedPermissions = [
        "discussions.moderate" => ["discussions.announce", "discussions.close", "discussions.sink"],
        "discussions.manage" => ["discussions.delete", "discussions.edit"],
    ];

    /** @var array Permissions that have been deprecated and should no longer be used. */
    private $deprecatedPermissions = [
        "Garden.Activity.Delete",
        "Garden.Activity.View",
        "Garden.Curation.Manage",
        "Vanilla.Approval.Require",
        "Vanilla.Comments.Me",
    ];

    /** @var array A static mapping of updated permission names. */
    private $renamedPermissions = [
        "Conversations.Moderation.Manage" => "conversations.moderate",
        "Email.Comments.Add" => "comments.email",
        "Email.Conversations.Add" => "conversations.email",
        "Email.Discussions.Add" => "discussions.email",
        "Garden.Moderation.Manage" => "community.moderate",
        "Garden.NoAds.Allow" => "noAds.use",
        "Garden.Settings.Manage" => "site.manage",
        "Garden.Users.Approve" => "applicants.manage",
        "Groups.Group.Add" => "groups.add",
        "Groups.Moderation.Manage" => "groups.moderate",
        "Reputation.Badges.Give" => "badges.moderate",
        "Vanilla.Tagging.Add" => "tags.add",
        "Garden.SignIn.Allow" => "session.valid",
    ];

    private $reverseRenamedPermissions = [
        "conversations.moderate" => "Conversations.Moderation.Manage",
        "comments.email" => "Email.Comments.Add",
        "conversations.email" => "Email.Conversations.Add",
        "discussions.email" => "Email.Discussions.Add",
        "community.moderate" => "Garden.Moderation.Manage",
        "noads.use" => "Garden.NoAds.Allow",
        "site.manage" => "Garden.Settings.Manage",
        "applicants.manage" => "Garden.Users.Approve",
        "groups.add" => "Groups.Group.Add",
        "groups.moderate" => "Groups.Moderation.Manage",
        "badges.moderate" => "Reputation.Badges.Give",
        "tags.add" => "Vanilla.Tagging.Add",
        "session.valid" => "Garden.SignIn.Allow",
    ];

    /** @var array These permissions should not be renamed. */
    private $fixedPermissions = ["Reactions.Negative.Add", "Reactions.Positive.Add"];

    /**
     * Collapse multiple permissions down into a single one, where possible.
     *
     * @param array $permissions An array of permissions.
     * @return array
     */
    private function consolidatePermissions(array $permissions)
    {
        $result = $permissions;

        foreach ($this->consolidatedPermissions as $name => $perms) {
            $pass = 0;
            $total = count($perms);
            foreach ($perms as $currentPerm) {
                if (!array_key_exists($currentPerm, $permissions)) {
                    // If a key isn't present, assume this is the wrong permission type (e.g. global, category).
                    continue 2;
                } elseif ($permissions[$currentPerm]) {
                    $pass++;
                }
            }

            if ($pass == $total) {
                $val = true;
            } elseif ($pass == 0) {
                $val = false;
            } else {
                $val = null;
            }

            $result[$name] = $val;
            unset($currentPerm, $pass);
        }

        return $result;
    }

    /**
     * Determine if a permission slug is deprecated.
     *
     * @param string $permission The name of the permission to check.
     * @return bool
     */
    private function isPermissionDeprecated($permission)
    {
        $result = str_starts_with($permission, "_") || in_array($permission, $this->deprecatedPermissions);
        return $result;
    }

    /**
     * Rename a legacy Vanilla permission slug.
     *
     * @param string $permission
     * @return string
     */
    private function renamePermission($permission)
    {
        if (array_key_exists($permission, $this->renamedPermissions)) {
            // Already got a mapping for this permission? Go ahead and use it.
            $result = $this->renamedPermissions[$permission];
        } else {
            // Time to format the permission name.
            $segments = explode(".", $permission);

            // Pop the application off the top, if it seems safe to do so.
            if (!in_array($permission, $this->fixedPermissions) && count($segments) == 3) {
                unset($segments[0]);
            }

            foreach ($segments as &$seg) {
                $seg = $this->nameScheme->convert($seg);
            }

            // Cache the renamed permission for this request.
            $result = implode(".", $segments);
            $this->renamedPermissions[$permission] = $result;
            $this->reverseRenamedPermissions[$result] = $permission;
        }

        return $result;
    }

    /**
     * Check if permission is 2 part
     *
     * @param string $permission
     * @return boolean
     */
    private function isTwoPartPermission($permission)
    {
        $segments = explode(".", $permission);
        return count($segments) == 2;
    }

    /**
     * Untranslate a permission name from the new API style name to the old permission name.
     *
     * @param string $newName
     * @return string
     */
    public function untranslatePermission(string $newName): string
    {
        $newName = strtolower($newName);
        if (array_key_exists($newName, $this->reverseRenamedPermissions)) {
            return $this->reverseRenamedPermissions[$newName];
        }

        $segments = explode(".", $newName);
        $updatedPermission = implode(".", array_map("ucfirst", $segments));
        if (in_array($updatedPermission, $this->fixedPermissions) && count($segments) == 3) {
            return $updatedPermission;
        }

        $result = $newName;
        $permissionModel = Gdn::getContainer()->get(PermissionModel::class);
        $permissionList = $permissionModel->getAllPermissions();
        foreach ($permissionList as $permission) {
            if (str_ends_with(strtolower($permission), strtolower($updatedPermission))) {
                $result = $permission;
                $this->renamedPermissions[$result] = $newName;
                $this->reverseRenamedPermissions[$newName] = $result;
                break;
            }
        }
        return $result;
    }
}

<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GNU GPLv2
 */

namespace Vanilla;

/**
 * Compile, manage and check user permissions.
 *
 * @package Vanilla
 */
class Permissions {
    /**
     * Global permissions are stored as numerical indexes.
     * Per-ID permissions are stored as associative keys. The key is the permission name and the values are the IDs.
     * @var array
     */
    private $permissions = [];

    /** @var bool */
    private $isAdmin = false;

    /** @var bool */
    private $isBanned = false;

    /**
     * Permissions constructor.
     *
     * @param array $permissions
     */
    public function __construct($permissions = []) {
        $this->setPermissions($permissions);
    }

    /**
     * Add a permission.
     *
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View)
     * @param int|array $IDs One or more IDs of foreign objects (e.g. category IDs)
     * @return $this
     */
    public function add($permission, $IDs) {
        if (!is_array($IDs)) {
            $IDs = [$IDs];
        }

        if (!array_key_exists($permission, $this->permissions) || !is_array($this->permissions[$permission])) {
            $this->permissions[$permission] = [];
        }

        $this->permissions[$permission] = array_unique(array_merge($this->permissions[$permission], $IDs));

        return $this;
    }

    /**
     * Compile raw permission rows into a formatted array of granted permissions.
     *
     * @param array $permissions Rows from the Permissions database table.
     * @return $this
     */
    public function compileAndLoad(array $permissions) {
        foreach ($permissions as $row) {
            // Store the junction ID, if we have one.
            $junctionID = array_key_exists('JunctionID', $row) ? $row['JunctionID'] : null;

            // Clear out any non-permission fields.
            unset(
                $row['PermissionID'],
                $row['RoleID'],
                $row['JunctionTable'],
                $row['JunctionColumn'],
                $row['JunctionID']
            );

            // Iterate through the row's individual permissions.
            foreach ($row as $permission => $value) {
                // If the user doesn't have this permission, move on to the next one.
                if ($value == 0) {
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
    public function getPermissions() {
        return $this->permissions;
    }

    /**
     * Determine if the permission is present.
     *
     * @param string $permission Permission slug to check the value for (e.g. Vanilla.Discussions.View)
     * @param int|null $id Foreign object ID to validate the permission against (e.g. a category ID)
     * @return bool
     */
    public function has($permission, $id = null) {
        if ($id === null) {
            return !empty($this->permissions[$permission]) || (array_search($permission, $this->permissions) !== false);
        } else {
            if (array_key_exists($permission, $this->permissions) && is_array($this->permissions[$permission])) {
                return (array_search($id, $this->permissions[$permission]) !== false);
            } else {
                return false;
            }
        }
    }

    /**
     * Determine if all of the provided permissions are present.
     *
     * @param array $permissions Permission slugs to check the value for (e.g. Vanilla.Discussions.View)
     * @param int|null $id Foreign object ID to validate the permissions against (e.g. a category ID)
     * @return bool
     */
    public function hasAll(array $permissions, $id = null) {
        foreach ($permissions as $permission) {
            if ($this->has($permission, $id) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the provided permissions are present.
     *
     * @param array $permissions Permission slugs to check the value for (e.g. Vanilla.Discussions.View)
     * @param int|null $id Foreign object ID to validate the permissions against (e.g. a category ID)
     * @return bool
     */
    public function hasAny(array $permissions, $id = null) {
        foreach ($permissions as $permission) {
            if ($this->has($permission, $id) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the admin flag is set.
     *
     * @return bool
     */
    public function isAdmin() {
        return $this->isAdmin === true;
    }

    /**
     * Determine if the banned flag is set.
     *
     * @return bool
     */
    public function isBanned() {
        return $this->isBanned === true;
    }

    /**
     * Merge in data from another Permissions instance.
     *
     * @param Permissions $source The source Permissions instance to import permissions from.
     * @return $this
     */
    public function merge(Permissions $source) {
        $this->setPermissions(array_merge_recursive(
            $this->permissions,
            $source->getPermissions()
        ));

        return $this;
    }

    /**
     * Set global or replace per-ID permissions.
     *
     * @param string $permission  Permission slug to set the value for (e.g. Vanilla.Discussions.View)
     * @param bool|array $value A single value for global permissions or an array of foreign object IDs for per-ID permissions.
     * @return $this
     */
    public function overwrite($permission, $value) {
        if ($value === true || $value === false) {
            $this->set($permission, $value);
        } elseif (is_array($value)) {
            $this->permissions[$permission] = $value;
        }

        return $this;
    }

    /**
     * Remove a permission.
     *
     * @param $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View)
     * @param int|array $IDs One or more IDs of foreign objects (e.g. category IDs)
     * @return $this;
     */
    public function remove($permission, $IDs) {
        if (array_key_exists($permission, $this->permissions)) {
            if (!is_array($IDs)) {
                $IDs = [$IDs];
            }

            foreach ($IDs as $currentID) {
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
     * @param string $permission Permission slug to set the value for (e.g. Vanilla.Discussions.View)
     * @param bool $value Toggle value for the permission: true for granted, false for revoked.
     * @return $this
     */
    public function set($permission, $value) {
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
    public function setAdmin($isAdmin) {
        $this->isAdmin = ($isAdmin == true);
        return $this;
    }

    /**
     * Set the banned flag.
     *
     * @param bool $isBanned Is the user banned?
     * @return $this
     */
    public function setBanned($isBanned) {
        $this->isBanned = ($isBanned == true);
        return $this;
    }

    /**
     * Set the permission array.
     *
     * @param array $permissions A properly-formatted permissions array.
     * @return $this
     */
    public function setPermissions(array $permissions) {
        $this->permissions = $permissions;
        return $this;
    }
}

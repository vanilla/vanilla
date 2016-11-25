<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

class Permissions {

    /** @var array */
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
    }

    /**
     * @param string $permission
     * @param int|array|null $id
     */
    public function add($permission, $id = null) {
    }

    /**
     * @param string $permission
     * @param int|null $id
     */
    public function has($permission, $id = null) {
    }

    /**
     * @param array $permissions
     * @param int|null $id
     */
    public function hasAll($permissions, $id = null) {
    }

    /**
     * @param array $permissions
     * @param int|null $id
     */
    public function hasAny(array $permissions, $id = null) {
    }

    /**
     * @return bool
     */
    public function isAdmin() {
        return $this->isAdmin === true;
    }

    /**
     * @return bool
     */
    public function isBanned() {
        return $this->isBanned === true;
    }

    /**
     * @param array $permissions
     */
    public function loadAndCompile($permissions) {
    }

    /**
     * @param Permissions $permissions
     */
    public function merge(Permissions $permissions) {
    }

    /**
     * @param string $permission
     * @param bool|array $value
     */
    public function overwrite($permission, $value) {
    }

    /**
     * @param $permission
     * @param int|array|null $id
     */
    public function remove($permission, $id = null) {
    }


    /**
     * @param string $permission
     * @param bool $value
     * @param int|array $id
     */
    public function set($permission, $value, $id = null) {
    }

    /**
     * @param bool $isAdmin
     * @return $this
     */
    public function setAdmin($isAdmin) {
        $this->isAdmin = ($isAdmin == true);
        return $this;
    }

    /**
     * @param bool $isBanned
     * @return $this
     */
    public function setBanned($isBanned) {
        $this->isBanned = ($isBanned == true);
        return $this;
    }
}

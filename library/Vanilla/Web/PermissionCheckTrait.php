<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\ServerException;
use Vanilla\Exception\PermissionException;
use Vanilla\Permissions;

/**
 * Trait for checking permissions in controllers.
 */
trait PermissionCheckTrait
{
    /**
     * Get the users permissions object.
     *
     * @return Permissions|null
     */
    abstract protected function getPermissions(): ?Permissions;

    /**
     * Enforce the following permission(s) or throw an exception that the dispatcher can handle.
     *
     * When passing several permissions to check the user can have any of the permissions. If you want to force several
     * permissions then make several calls to this method.
     *
     * @throws \Exception If no session is available.
     * @throws HttpException If a ban has been applied on the permission(s) for this session.
     * @throws PermissionException If the user does not have the specified permission(s).
     *
     * @param string|array $permissionToCheck The permissions you are requiring.
     * @param int|null $id The ID of the record we are checking the permission of.
     *
     * @return $this
     */
    public function permission($permissionToCheck = null, $id = null): self
    {
        $permissions = $this->getPermissions();
        if ($permissions === null) {
            throw new ServerException("Permissions not available.", 500);
        }
        $permissionToCheck = (array) $permissionToCheck;

        /**
         * First check to see if the user is banned.
         */
        if ($ban = $permissions->getBan($permissionToCheck)) {
            $ban += ["code" => 401, "msg" => "Access denied."];

            throw HttpException::createFromStatus($ban["code"], $ban["msg"], $ban);
        }

        if ($id === null) {
            $hasPermission = $permissions->hasAny($permissionToCheck, null, Permissions::CHECK_MODE_GLOBAL_OR_RESOURCE);
        } else {
            $hasPermission = $permissions->hasAny(
                $permissionToCheck,
                $id,
                Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                \CategoryModel::PERM_JUNCTION_TABLE
            );
        }

        if (!$hasPermission) {
            throw new PermissionException($permissionToCheck);
        }

        return $this;
    }
}

<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Exception;

use Garden\Web\Exception\ForbiddenException;

/**
 * An exception tha represents a permission test failing.
 */
class PermissionException extends ForbiddenException {
    /**
     * Construct a {@link PermissionException} object.
     *
     * @param string|string[] $permission The permission(s) that failed.
     * @param array $context Additional information for the error.
     */
    public function __construct($permission, array $context = []) {
        $permissions = array_filter((array)$permission, function ($v) {
            return $v[0] !== '!';
        });
        $context['permissions'] = $permissions;

        if (count($permissions) === 1) {
            $msg = sprintft('You need the %s permission to do that.', $permissions[0]);
        } else {
            $msg = sprintft('You need one of %s permissions to do that.', implode(', ', $permissions));
        }

        parent::__construct($msg, $context);
    }
}

/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hasPermission } from "@library/features/users/Permission";

/**
 * Check if the current user can view other users.
 */
export function hasUserViewPermission() {
    return hasPermission([
        // Any of these permissions will suffice.
        "profiles.view",

        // These ones give access to extra personal information.
        "users.add",
        "users.edit",
        "users.delete",
    ]);
}

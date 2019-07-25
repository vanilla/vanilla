/**
 * Permissions utility functions ported over from the server.
 *
 * @see {\Vanilla\Permsissions} for the server-side implementation.
 * @see {Gdn_Controller->renderMaster()} for the injection of permissions into the client.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import gdn from "@library/gdn";

/**
 * Determine if all of the provided permissions are present.
 */
export function hasPermission(permissions: string | string[], id: number | null = null) {
    if (typeof permissions === "string") {
        permissions = [permissions];
    }

    if (isBanned(permissions)) {
        return false;
    }

    if (gdn.permissions.isAdmin) {
        return true;
    }

    for (const permission of permissions) {
        if (hasInternal(permission, id) === false) {
            return false;
        }
    }

    return true;
}

/**
 * Determine if any of the provided permissions are present.
 */
export function hasAny(permissions: string | string[], id: number | null = null): boolean {
    if (typeof permissions === "string") {
        permissions = [permissions];
    }

    if (isBanned(permissions)) {
        return false;
    }

    if (gdn.permissions.isAdmin) {
        return true;
    }

    let nullCount = 0;
    for (const permission of permissions) {
        const has = hasInternal(permission, id);
        if (has === true) {
            return true;
        } else if (has === null) {
            nullCount++;
        }
    }
    return nullCount === permissions.length;
}

/**
 * Determine if the current user is banned.
 *
 * @param permissions - An optional array of permissions being checked. Any permission starting with "!" means
 * that a ban with that name is ignored.
 */
export function isBanned(permissions: string[] = []): boolean {
    const ban = getBan(permissions);
    return ban !== null;
}

/**
 * Get the currently active ban.
 *
 * @param permissions - An optional array of permissions being checked. Any permission starting with "!" means
 * that a ban with that name is ignored.
 */
function getBan(permissions: string[] = []): object | null {
    permissions = permissions.map(str => str.toLowerCase());
    const bans = gdn.permissions.bans || {};

    for (const name of Object.keys(bans)) {
        const ban = bans[name];

        if (name in permissions) {
            // The permission check is overriding the ban.
            continue;
        } else if (ban.except) {
            const except = typeof ban.except === "string" ? [ban.except] : ban.except;

            // There is an exception, so see if any of those permissions apply.
            let has = false;
            for (const permission of except) {
                if (hasInternal(permission)) {
                    has = true;
                    break;
                }
            }
            if (has) {
                continue;
            }
        }
        // There was no exception to the ban so we are banned.
        ban.type = name;
        return ban;
    }
    return null;
}

/**
 * Check just the permissions array, ignoring overrides from admin/bans.
 */
function hasInternal(permission: string, id: number | null = null): boolean | null {
    const permissions = gdn.permissions.permissions || {};

    if (permission === "admin") {
        return !!gdn.permissions.isAdmin;
    } else if (permission.substr(0, 1) === "!") {
        // This is a ban so skip it.
        return null;
    } else if (permissions[permission] === true) {
        return true;
    } else if (id !== null && permissions[permission].indexOf && permissions[permission].indexOf(id) !== -1) {
        return true;
    }
    return false;
}

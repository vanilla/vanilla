/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import gdn from "@core/gdn";

/**
 * Determine if all of the provided permissions are present.
 *
 * @param {string|string[]} permissions
 * @param {number|null} id
 * @returns {boolean}
 */
export function hasPermission(permissions, id = null) {
    if (typeof permissions === 'string') {
        permissions = [permissions];
    }

    if (isBanned(permissions)) {
        return false;
    }

    if (gdn.permissions.isAdmin) {
        return true;
    }

    for (let permission of permissions) {
        if (hasInternal(permission, id) === false) {
            return false;
        }
    }

    return true;
}

/**
 * Determine if any of the provided permissions are present.
 *
 * @param {string|string[]} permissions
 * @param {number|null} id
 * @returns {boolean}
 */
export function hasAny(permissions, id = null) {
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
 * @param {string[]} permissions An optional array of permissions being checked. Any permission starting with "!" means
 * that a ban with that name is ignored.
 * @returns {boolean}
 */
export function isBanned(permissions = []) {
    let ban = getBan(permissions);
    return ban !== null;
}

/**
 * Get the currently active ban.
 *
 * @param {string[]} permissions An optional array of permissions being checked. Any permission starting with "!" means
 * that a ban with that name is ignored.
 * @returns {Object|null}
 */
function getBan(permissions = []) {
    permissions = permissions.map((str) => str.toLowerCase());
    const bans = gdn.permissions.bans || {};

    for (let name in bans) {
        let ban = bans[name];

        if (name in permissions) {
            // The permission check is overriding the ban.
            continue;
        } else if (ban.except) {
            let except = typeof ban.except === 'string' ? [ban.except] : ban.except;

            // There is an exception, so see if any of those permissions apply.
            let has = false;
            for (let permission of except) {
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
        ban['type'] = name;
        return ban;
    }
    return null;
}

/**
 * Check just the permissions array, ignoring overrides from admin/bans.
 *
 * @param {string} permission
 * @param {int|null} id
 * @returns {boolean|null}
 */
function hasInternal(permission, id = null) {
    let permissions = gdn.permissions.permissions || {};

    if (permission === 'admin') {
        return !!gdn.permissions.isAdmin;
    } else if (permission.substr(0, 1) === '!') {
        // This is a ban so skip it.
        return null;
    } else if (permissions[permission] === true) {
        return true;
    } else if (id !== null && permissions[permission].indexOf && permissions[permission].indexOf(id) !== -1) {
        return true;
    }
    return false;
}

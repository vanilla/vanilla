/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { IPermissionOptions, PermissionChecker } from "@library/features/users/Permission";
import { PermissionsContext, usePermissionsContext } from "@library/features/users/PermissionsContext";

type IPermissionOverridesContext = {
    permissions?: Record<string, boolean>;
    children?: React.ReactNode;
};

export function PermissionOverridesContext(props: IPermissionOverridesContext) {
    const discusionPermissions = props;
    const globalPermissions = usePermissionsContext();
    const hasPermission: PermissionChecker = (permission: string | string[], options?: IPermissionOptions) => {
        if (globalPermissions.hasPermission("system.only")) {
            // User has admin bypass.
            return true;
        }

        const allPermissions = Array.isArray(permission) ? permission : [permission];
        for (const singlePermission of allPermissions) {
            if (singlePermission in (discusionPermissions.permissions ?? {})) {
                if (discusionPermissions.permissions![singlePermission]) {
                    // We had a "true" overridden. Use that.
                    return true;
                }
            } else {
                if (globalPermissions.hasPermission(singlePermission, options)) {
                    return true;
                }
            }
        }

        // If none matched
        return false;
    };

    const value = { hasPermission };
    return <PermissionsContext.Provider value={value}>{props.children}</PermissionsContext.Provider>;
}

/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { PermissionsContext } from "@library/features/users/PermissionsContext";

function AllPermissions(props: React.PropsWithChildren<{}>) {
    return (
        <PermissionsContext.Provider
            value={{
                hasPermission: (_permission) => true,
            }}
        >
            {props.children}
        </PermissionsContext.Provider>
    );
}

function NoPermissions(props: React.PropsWithChildren<{}>) {
    return (
        <PermissionsContext.Provider
            value={{
                hasPermission: (_permission) => false,
            }}
        >
            {props.children}
        </PermissionsContext.Provider>
    );
}

function SpecificPermissions(props: React.PropsWithChildren<{ permissions: string[] }>) {
    const permissions = props.permissions ?? [];

    return (
        <PermissionsContext.Provider
            value={{
                hasPermission: (permissionsToCheck: string | string[]) =>
                    [permissionsToCheck].flat().some((permission) => permissions.includes(permission)),
            }}
        >
            {props.children}
        </PermissionsContext.Provider>
    );
}

export const PermissionsFixtures = {
    AllPermissions,
    NoPermissions,
    SpecificPermissions,
};

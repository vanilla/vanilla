/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import getStore from "@library/redux/getStore";
import React from "react";
import { usePermissions } from "@library/features/users/userModel";

export enum PermissionMode {
    /**
     * Check that the user has a global permission for the resource.
     * NOTE, a user may not have the global permission, but they may have permission to a specific resource.
     * @deprecated Use GLOBAL_OR_RESOURCE instead.
     */
    GLOBAL = "global",

    /**
     * Check if the user has the resource specific permission.
     * IMPORTANT, this is unaware of if the resource actually _has_ custom permissions.
     *
     * @deprecated Recommended to use `RESOURCE_IF_JUNCTION`.
     */
    RESOURCE = "resource",

    /**
     * Check if the user has either global or a resource specific permission.
     *
     * Ideal for components that mix data from multiple resources.
     */
    GLOBAL_OR_RESOURCE = "globalOrResource",

    /**
     * If the resource has custom permissions, check for permsision on that resource.
     * Otherwise check the global permission.
     */
    RESOURCE_IF_JUNCTION = "resourceIfJunction",
}

interface IProps {
    permission?: string | string[];
    mode?: PermissionMode;
    resourceType?: string;
    resourceID?: number;
    children: React.ReactNode;
    fallback?: React.ReactNode;
}

/**
 * Component for checking one or many permissions.
 *
 * Conditionally renders either it's children or a fallback based on if the user has a permission.
 */
export default function Permission(props: IProps) {
    usePermissions();

    if (!props.permission) {
        return <>{props.children}</>;
    }

    const result = hasPermission(props.permission, {
        mode:
            props.mode ??
            (props.resourceID != null ? PermissionMode.RESOURCE_IF_JUNCTION : PermissionMode.GLOBAL_OR_RESOURCE),
        resourceType: props.resourceType,
        resourceID: props.resourceID,
    })
        ? props.children
        : props.fallback ?? null;
    return <>{result}</>;
}

export interface IPermission {
    permission: string | string[];
    options?: IPermissionOptions;
}

export interface IPermissionOptions {
    mode: PermissionMode;
    resourceType?: string;
    resourceID?: number | null;
}

/**
 * Determine if the user has one of the given permissions.
 *
 * - Always false if the data isn't loaded yet.
 * - Always true if the user has the admin flag set.
 * - Only 1 one of the provided permissions needs to match.
 */
export function hasPermission(permission: string | string[], options?: IPermissionOptions) {
    const { permissions } = getStore().getState().users;

    let permissionsToCheck = permission;
    if (!Array.isArray(permissionsToCheck)) {
        permissionsToCheck = [permissionsToCheck];
    }

    if (!permissions.data || permissions.status !== LoadStatus.SUCCESS) {
        return false;
    }

    if (permissions.data.isAdmin) {
        return true;
    }

    if (!options) {
        options = {
            mode: PermissionMode.GLOBAL_OR_RESOURCE,
        };
    }

    let { resourceID, resourceType } = options;

    if (!resourceType) {
        resourceType = "global";
        resourceID = null;
    }

    let resourceHasJunction = false;
    if (resourceID != null && resourceType != null) {
        // Resolve the alias if it exists.
        const aliases = permissions.data.junctionAliases?.[resourceType];
        if (aliases) {
            resourceID = aliases[resourceID] ?? aliases[resourceID.toString()] ?? resourceID;
        }

        const junctions = permissions.data.junctions?.[resourceType] ?? null;
        if (junctions) {
            resourceHasJunction = junctions.includes(resourceID);
        }
    }

    const permissionGroups = permissions.data.permissions.filter((permission) => {
        const matchesGlobal = permission.type === "global";
        const matchesResource = permission.type === resourceType && permission.id === resourceID;
        switch (options!.mode) {
            case PermissionMode.RESOURCE_IF_JUNCTION:
                return resourceHasJunction ? matchesResource : matchesGlobal;
            case PermissionMode.GLOBAL:
                return matchesGlobal;
            case PermissionMode.GLOBAL_OR_RESOURCE:
                return true; // All allowed to be checked.
            case PermissionMode.RESOURCE:
                return matchesResource;
        }
    });

    let hasMatch = false;
    permissionGroups.forEach((permissionGroupToCheck) => {
        for (const [permissionKey, permissionValue] of Object.entries(permissionGroupToCheck.permissions)) {
            if (permissionsToCheck.includes(permissionKey) && permissionValue) {
                hasMatch = true;
            }
        }
    });
    return hasMatch;
}

export function isUserAdmin(): boolean {
    return getStore().getState().users.permissions.data?.isAdmin ?? false;
}

export function isUserSysAdmin(): boolean {
    return getStore().getState().users.permissions.data?.isSysAdmin ?? false;
}

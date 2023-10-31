/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { useConfigsByKeys } from "@library/config/configHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import React, { ReactNode, useContext, useMemo } from "react";
import { useCurrentUser } from "@library/features/users/userHooks";
import { ICustomControl, JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";
import { UserManagementTableColumnName } from "./UserManagementUtils";

const PASSWORD_CONFIG_KEY = "password.minLength";

interface IUserManagementContext {
    minPasswordLength?: number;
    profileFields?: ProfileField[];
    permissions: {
        canAddUsers: boolean;
        canEditUsers: boolean;
        canDeleteUsers: boolean;
        canViewPersonalInfo: boolean;
        canSpoofUsers: boolean;
    };
    currentUserID?: number;
    RanksWrapperComponent: React.ComponentType | undefined;
    additionalFiltersSchemaFields: IAdditionalFiltersSchemaField[] | undefined;
}
interface IAdditionalFiltersSchemaField {
    fieldName: string;
    columnName: UserManagementTableColumnName;
    schema: JsonSchema | PartialSchemaDefinition;
}

/**
 * Data holder for user management page
 */
export const UserManagementContext = React.createContext<IUserManagementContext>({
    profileFields: undefined,
    minPasswordLength: 12, // Default defined in configuration
    permissions: {
        canAddUsers: false,
        canEditUsers: false,
        canDeleteUsers: false,
        canViewPersonalInfo: false,
        canSpoofUsers: false,
    },
    currentUserID: undefined,
    RanksWrapperComponent: undefined,
    additionalFiltersSchemaFields: undefined,
});

let RanksWrapperComponent: React.ComponentType | undefined = undefined;
UserManagementProvider.setRanksWrapperComponent = (component: React.ComponentType<{ children?: ReactNode }>) => {
    RanksWrapperComponent = component;
};

/**
 * This is responsible for adding additional schemas to the base one, for filter modal form, e.g. ranks schema etc
 */
let additionalFiltersSchemaFields: IAdditionalFiltersSchemaField[] = [];
UserManagementProvider.setAdditionalFiltersSchema = (
    fieldName: IAdditionalFiltersSchemaField["fieldName"],
    columnName: IAdditionalFiltersSchemaField["columnName"],
    schema: IAdditionalFiltersSchemaField["schema"],
) => {
    additionalFiltersSchemaFields.push({ fieldName, columnName, schema });
};

export function UserManagementProvider(props: { children: ReactNode }) {
    const { hasPermission } = usePermissionsContext();
    const profileFieldsConfig = useProfileFields({ enabled: true });

    // Data from config
    const configs = useConfigsByKeys([PASSWORD_CONFIG_KEY]);

    const minPasswordLength = useMemo(() => {
        return configs.data?.[PASSWORD_CONFIG_KEY] ?? 12;
    }, [configs]);

    const profileFields = useMemo(() => {
        return profileFieldsConfig.data;
    }, [profileFieldsConfig]);

    const currentUser = useCurrentUser();
    const currentUserID = useMemo(() => {
        return currentUser?.userID ?? -1;
    }, [currentUser]);
    return (
        <UserManagementContext.Provider
            value={{
                minPasswordLength,
                profileFields: profileFields,
                permissions: {
                    canAddUsers: hasPermission(["users.add"]),
                    canEditUsers: hasPermission(["users.edit"]),
                    canDeleteUsers: hasPermission(["users.delete"]),
                    canViewPersonalInfo: hasPermission("personalInfo.view"),
                    canSpoofUsers: hasPermission("site.manage"),
                },
                currentUserID: currentUserID,
                RanksWrapperComponent,
                additionalFiltersSchemaFields,
            }}
        >
            {props.children}
        </UserManagementContext.Provider>
    );
}

export function useUserManagement() {
    return useContext(UserManagementContext);
}

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useRoleActions } from "@dashboard/roles/RoleActions";
import { useEffect } from "react";
import { useSelector } from "react-redux";
import { IRoleStoreState } from "@dashboard/roles/roleReducer";
import { LoadStatus, ILoadable } from "@vanilla/library/src/scripts/@types/api/core";
import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";

export function useRoles() {
    const { getAllRoles } = useRoleActions();

    const rolesByID = useSelector((state: IRoleStoreState) => state.roles.rolesByID);

    useEffect(() => {
        if (rolesByID.status === LoadStatus.PENDING) {
            void getAllRoles();
        }
    }, [getAllRoles, rolesByID]);

    return rolesByID;
}

export function useRoleSelectOptions(): ILoadable<IComboBoxOption[]> {
    const roles = useRoles();

    if (roles.data) {
        return {
            ...roles,
            data: Object.values(roles.data).map((role) => {
                return {
                    value: role.roleID,
                    label: role.name,
                };
            }),
        };
    } else {
        return {
            ...roles,
            data: undefined,
        };
    }
}

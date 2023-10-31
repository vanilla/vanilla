import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import React, { PropsWithChildren, useCallback, useContext, useEffect } from "react";
import { useUserActions } from "@library/features/users/UserActions";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { useSelector } from "react-redux";
import { IPermissionOptions, PermissionChecker } from "@library/features/users/Permission";
import { checkPermission } from "@library/features/users/Permission";

interface IPermissionsContextValue {
    hasPermission: PermissionChecker;
}

export const PermissionsContext = React.createContext<IPermissionsContextValue>({
    hasPermission: (_permission, _options) => false,
});

export function PermissionsContextProvider(props: PropsWithChildren<{}>) {
    const permissions = useSelector((state: ICoreStoreState) => state.users.permissions);
    const { getPermissions } = useUserActions();
    const { status } = permissions;

    const hasPermission = useCallback<IPermissionsContextValue["hasPermission"]>(
        (permission, options) => {
            return checkPermission(permissions, permission, options);
        },
        [permissions],
    );

    useEffect(() => {
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            void getPermissions();
        }
    }, [status, getPermissions]);

    return (
        <PermissionsContext.Provider
            value={{
                hasPermission,
            }}
        >
            {props.children}
        </PermissionsContext.Provider>
    );
}

export function usePermissionsContext() {
    return useContext(PermissionsContext);
}

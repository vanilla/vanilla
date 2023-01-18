/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import { useConfigsByKeys } from "@library/config/configHooks";
import { hasPermission } from "@library/features/users/Permission";
import { useCurrentUser, useUser } from "@library/features/users/userHooks";
import React, { ReactNode, useContext, useMemo } from "react";

const METHOD_CONFIG_KEY = "registration.method";
const PASSWORD_CONFIG_KEY = "password.minLength";

interface IAccountSettingsContext {
    isRegistrationConnect: boolean;
    canEditUsers: boolean;
    canEditUsernames: boolean;
    viewingUserID: IUser["userID"];
    viewingUser: IUser | null;
    isViewingSelf: boolean;
    minPasswordLength: number;
}

/**
 * Permission and configs for the account and privacy editing
 */
export const AccountSettingContext = React.createContext<IAccountSettingsContext>({
    isRegistrationConnect: false,
    canEditUsers: false,
    canEditUsernames: false,
    viewingUser: null,
    viewingUserID: -1,
    isViewingSelf: false,
    minPasswordLength: 12, // Default defined in configuration
});

export function AccountSettingProvider(props: { userID: IUser["userID"]; children: ReactNode }) {
    const { userID, children } = props;

    // Data from config
    const configs = useConfigsByKeys([METHOD_CONFIG_KEY, PASSWORD_CONFIG_KEY]);

    const isRegistrationConnect = useMemo(() => {
        return configs?.data?.[METHOD_CONFIG_KEY] === "Connect";
    }, [configs]);

    const minPasswordLength = useMemo(() => {
        return configs.data?.[PASSWORD_CONFIG_KEY] ?? 12;
    }, [configs]);

    // Data from permissions
    const canEditUsernames = hasPermission(["users.edit", "profile.editusernames"]);
    const canEditUsers = hasPermission(["users.edit"]);

    // Data from server/redux store
    const sessionUser = useCurrentUser();
    const sessionUserID = useMemo(() => {
        return sessionUser?.userID ?? -1;
    }, [sessionUser]);

    const viewingUserLoadable = useUser({ userID });
    const viewingUser = useMemo(() => {
        return viewingUserLoadable?.data ?? null;
    }, [viewingUserLoadable]);

    const isViewingSelf = sessionUserID === userID;

    return (
        <AccountSettingContext.Provider
            value={{
                isRegistrationConnect,
                canEditUsernames,
                canEditUsers,
                viewingUser,
                viewingUserID: props.userID,
                isViewingSelf,
                minPasswordLength,
            }}
        >
            {children}
        </AccountSettingContext.Provider>
    );
}

export function useAccountSettings() {
    return useContext(AccountSettingContext);
}

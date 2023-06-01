/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import { useConfigsByKeys } from "@library/config/configHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser, useUser } from "@library/features/users/userHooks";
import React, { ReactNode, useContext, useMemo } from "react";

const PROFILE_EDIT_EMAILS_CONFIG_KEY = "profile.editEmails";
const PASSWORD_CONFIG_KEY = "password.minLength";

interface IAccountSettingsContext {
    canEditEmails: boolean;
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
export const AccountSettingsContext = React.createContext<IAccountSettingsContext>({
    canEditEmails: false,
    canEditUsers: false,
    canEditUsernames: false,
    viewingUser: null,
    viewingUserID: -1,
    isViewingSelf: false,
    minPasswordLength: 12, // Default defined in configuration
});

export function AccountSettingProvider(props: { userID: IUser["userID"]; children: ReactNode }) {
    const { userID, children } = props;

    const { hasPermission } = usePermissionsContext();

    // Data from config
    const configs = useConfigsByKeys([PROFILE_EDIT_EMAILS_CONFIG_KEY, PASSWORD_CONFIG_KEY]);

    const canEditEmails = useMemo(() => {
        return configs?.data?.[PROFILE_EDIT_EMAILS_CONFIG_KEY] === true;
    }, [configs]);

    const minPasswordLength = useMemo(() => {
        return configs.data?.[PASSWORD_CONFIG_KEY] ?? 12;
    }, [configs]);

    // Data from permissions
    const canEditUsernames = hasPermission(["profile.editusernames"]);
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
        <AccountSettingsContext.Provider
            value={{
                canEditEmails,
                canEditUsernames,
                canEditUsers,
                viewingUser,
                viewingUserID: props.userID,
                isViewingSelf,
                minPasswordLength,
            }}
        >
            {children}
        </AccountSettingsContext.Provider>
    );
}

export function useAccountSettings() {
    return useContext(AccountSettingsContext);
}

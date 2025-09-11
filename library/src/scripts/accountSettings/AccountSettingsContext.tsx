/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUser } from "@library/@types/api/users";
import { useConfigsByKeys } from "@library/config/configHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useCurrentUser, useUserQuery } from "@library/features/users/userHooks";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { getAnonymizeData, setAnonymizeData } from "@library/analytics/anonymizeData";
import React, { ReactNode, useContext, useMemo } from "react";

const PROFILE_EDIT_EMAILS_CONFIG_KEY = "profile.editEmails";
const PASSWORD_CONFIG_KEY = "password.minLength";
const ANONYMIZE_CONFIG_KEY = "analytics.anonymize";

interface IAccountSettingsContext {
    canEditEmails: boolean;
    canEditUsers: boolean;
    canEditUsernames: boolean;
    viewingUserID: IUser["userID"];
    viewingUser: IUser | null;
    isViewingSelf: boolean;
    minPasswordLength: number;

    isAnalyticsAnonymized?: boolean;
    isCurrentUserAnonymized?: boolean;
    setUserAnonymizeData?: (anonymize: boolean) => Promise<boolean>;
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
    const queryClient = useQueryClient();

    // Data from config
    const configs = useConfigsByKeys([PROFILE_EDIT_EMAILS_CONFIG_KEY, PASSWORD_CONFIG_KEY, ANONYMIZE_CONFIG_KEY]);

    const canEditEmails = useMemo(() => {
        return configs?.data?.[PROFILE_EDIT_EMAILS_CONFIG_KEY] === true;
    }, [configs]);

    const minPasswordLength = useMemo(() => {
        return configs.data?.[PASSWORD_CONFIG_KEY] ?? 12;
    }, [configs]);

    const isAnalyticsAnonymized = useMemo(() => {
        return configs?.data?.[ANONYMIZE_CONFIG_KEY] === true;
    }, [configs]);

    const isCurrentUserAnonymized = useQuery({
        queryKey: ["getAnonymizeData"],
        queryFn: getAnonymizeData,
    }).data;

    const setUserAnonymizeData = async (anonymize: boolean) => {
        try {
            const result = await setAnonymizeData(anonymize);
            void queryClient.invalidateQueries({ queryKey: ["getAnonymizeData"] });
            return result;
        } catch (error) {
            void queryClient.invalidateQueries({ queryKey: ["getAnonymizeData"] });
            throw error;
        }
    };

    // Data from permissions
    const canEditUsernames = hasPermission(["profile.editusernames"]);
    const canEditUsers = hasPermission(["users.edit"]);

    // Data from server/redux store
    const sessionUser = useCurrentUser();
    const sessionUserID = useMemo(() => {
        return sessionUser?.userID ?? -1;
    }, [sessionUser]);

    const viewingUserQuery = useUserQuery({ userID });
    const viewingUser = viewingUserQuery?.data ?? null;

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
                isAnalyticsAnonymized,
                isCurrentUserAnonymized,
                setUserAnonymizeData,
            }}
        >
            {children}
        </AccountSettingsContext.Provider>
    );
}

export function useAccountSettings() {
    return useContext(AccountSettingsContext);
}

/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { AccountSettingsContext } from "@library/accountSettings/AccountSettingsContext";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IUser } from "@library/@types/api/users";

export function UserEditingSelf({
    children,
    state = {},
    mockUserOverrides,
    hasEditUsernamesPermission = true,
    editEmailsConfigValue = true,
}: {
    children: ReactNode;
    state?: Partial<ICoreStoreState["users"]>;
    mockUserOverrides?: Partial<IUser>;
    hasEditUsernamesPermission?: boolean;
    editEmailsConfigValue?: boolean;
}) {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    usersByID: {
                        2: {
                            status: LoadStatus.SUCCESS,
                            data: UserFixture.createMockUser({ userID: 2, ...mockUserOverrides }),
                        },
                    },
                    ...state,
                },
            }}
        >
            <AccountSettingsContext.Provider
                value={{
                    canEditEmails: editEmailsConfigValue,
                    canEditUsers: false,
                    canEditUsernames: hasEditUsernamesPermission,
                    viewingUser: UserFixture.createMockUser({ userID: 2, ...mockUserOverrides }),
                    minPasswordLength: 12,
                    isViewingSelf: true,
                    viewingUserID: 2,
                }}
            >
                {children}
            </AccountSettingsContext.Provider>
        </TestReduxProvider>
    );
}

export function UserWithEditingPermissionEditingAnotherUser({
    children,
    mockUserOverrides,
    hasEditUsernamesPermission = true,
    editEmailsConfigValue = true,
}: {
    children: ReactNode;
    mockUserOverrides?: Partial<IUser>;
    hasEditUsernamesPermission?: boolean;
    editEmailsConfigValue?: boolean;
}) {
    return (
        <TestReduxProvider>
            <AccountSettingsContext.Provider
                value={{
                    canEditEmails: editEmailsConfigValue,
                    isViewingSelf: false,
                    canEditUsers: true,
                    canEditUsernames: hasEditUsernamesPermission,
                    viewingUser: UserFixture.createMockUser({ userID: 3, ...mockUserOverrides }),
                    minPasswordLength: 12,
                    viewingUserID: 3,
                }}
            >
                {children}
            </AccountSettingsContext.Provider>
        </TestReduxProvider>
    );
}

export function UserWithNoSpecificPermissionsEditingAnotherUser({
    children,
    mockUserOverrides,
}: {
    children: ReactNode;
    mockUserOverrides?: Partial<IUser>;
}) {
    return (
        <TestReduxProvider>
            <AccountSettingsContext.Provider
                value={{
                    canEditEmails: false,
                    isViewingSelf: false,
                    canEditUsers: false,
                    canEditUsernames: false,
                    viewingUser: UserFixture.createMockUser({ userID: 3, ...mockUserOverrides }),
                    minPasswordLength: 12,
                    viewingUserID: 3,
                }}
            >
                {children}
            </AccountSettingsContext.Provider>
        </TestReduxProvider>
    );
}

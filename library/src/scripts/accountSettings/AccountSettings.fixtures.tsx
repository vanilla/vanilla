/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { AccountSettingContext } from "@library/accountSettings/AccountSettingsContext";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

export function UserEditingSelf({
    children,
    state = {},
}: {
    children: ReactNode;
    state?: Partial<ICoreStoreState["users"]>;
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
                            data: UserFixture.createMockUser({ userID: 2 }),
                        },
                    },
                    ...state,
                },
            }}
        >
            <AccountSettingContext.Provider
                value={{
                    isRegistrationConnect: false,
                    canEditUsers: false,
                    canEditUsernames: true,
                    viewingUser: UserFixture.createMockUser({ userID: 2 }),
                    minPasswordLength: 12,
                    isViewingSelf: true,
                    viewingUserID: 2,
                }}
            >
                {children}
            </AccountSettingContext.Provider>
        </TestReduxProvider>
    );
}

export function UserWithEditingPermissionEditingAnotherUser({
    children,
    state = {},
}: {
    children: ReactNode;
    state?: Partial<ICoreStoreState["users"]>;
}) {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    permissions: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            isAdmin: true,
                            isSysAdmin: false,
                            permissions: [UserFixture.globalAdminPermissions],
                        },
                    },
                    ...state,
                },
            }}
        >
            <AccountSettingContext.Provider
                value={{
                    isRegistrationConnect: false,
                    isViewingSelf: false,
                    canEditUsers: true,
                    canEditUsernames: true,
                    viewingUser: UserFixture.createMockUser({ userID: 3 }),
                    minPasswordLength: 12,
                    viewingUserID: 3,
                }}
            >
                {children}
            </AccountSettingContext.Provider>
        </TestReduxProvider>
    );
}

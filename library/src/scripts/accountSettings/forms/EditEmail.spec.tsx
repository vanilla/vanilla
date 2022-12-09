/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { render, screen } from "@testing-library/react";
import { LoadStatus } from "@library/@types/api/core";
import { EditEmail } from "@library/accountSettings/forms/EditEmail";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { AccountSettingProvider } from "@library/accountSettings/AccountSettingsContext";

function ProviderWrapper({ children, state }: { children: ReactNode; state?: any }) {
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
                            data: UserFixture.adminAsCurrent,
                        },
                    },
                    ...state,
                },
            }}
        >
            <AccountSettingProvider userID={2}>{children}</AccountSettingProvider>
        </TestReduxProvider>
    );
}

function DifferentUser({ isSaving, isDirty, isSuccess }) {
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
                },
            }}
        >
            <AccountSettingProvider userID={3}>
                <EditEmail setIsSaving={isSaving} setIsFormDirty={isDirty} setIsSuccess={isSuccess} />
            </AccountSettingProvider>
        </TestReduxProvider>
    );
}

describe("Edit Email", () => {
    it("Requires a password confirmation if the session user is editing their own profile", () => {
        render(
            <ProviderWrapper>
                <EditEmail setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        expect(screen.getByText(/New Email/)).toBeInTheDocument();
        expect(screen.getByText(/Password/)).toBeInTheDocument();
    });
    it("Cannot verify an email if the session user does not have the user.edit permission", () => {
        render(
            <ProviderWrapper>
                <EditEmail setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        expect(screen.queryByText(/Verified/)).not.toBeInTheDocument();
    });
    it("Does not ask for passwords if the session user has the user.edit permission", () => {
        render(<DifferentUser isSaving={() => null} isDirty={() => null} isSuccess={() => null} />);
        expect(screen.queryByText(/Password/)).not.toBeInTheDocument();
    });
});

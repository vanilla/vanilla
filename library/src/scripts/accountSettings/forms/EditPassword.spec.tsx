/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { EditPassword } from "@library/accountSettings/forms/EditPassword";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
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
                            data: UserFixture.createMockUser({ userID: 2 }),
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

describe("Edit Password", () => {
    it("Render Initial Form", () => {
        render(
            <ProviderWrapper>
                <EditPassword setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );

        // Labels are displayed
        expect(screen.getByText("New Password")).toBeInTheDocument();
        expect(screen.getByText("Confirm New Password")).toBeInTheDocument();

        // New password input is displayed and is a password field
        const newPasswordInput = screen.getByLabelText("New Password");
        expect(newPasswordInput).toBeInTheDocument();
        expect(newPasswordInput.tagName.toLowerCase()).toEqual("input");
        expect(newPasswordInput.getAttribute("type")).toEqual("password");

        // Confirm password input is display and is a password field
        const confirmPasswordInput = screen.getByLabelText("Confirm New Password");
        expect(confirmPasswordInput).toBeInTheDocument();
        expect(confirmPasswordInput.tagName.toLowerCase()).toEqual("input");
        expect(confirmPasswordInput.getAttribute("type")).toEqual("password");
    });

    it("Sets form dirty state after field change", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper>
                <EditPassword setIsSaving={() => null} setIsFormDirty={mockFunction} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );

        const passwordInput = screen.getByLabelText("New Password");
        fireEvent.change(passwordInput, { target: { value: "h3ll0Wor|d" } });
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets saving state when patchStatusByUserID status is loading", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper
                state={{
                    patchStatusByUserID: {
                        2: {
                            status: LoadStatus.LOADING,
                        },
                    },
                }}
            >
                <EditPassword setIsSaving={mockFunction} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets success state when patchStatusByUserID status is successful", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper
                state={{
                    patchStatusByUserID: {
                        2: {
                            status: LoadStatus.SUCCESS,
                        },
                    },
                }}
            >
                <EditPassword setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={mockFunction} />
            </ProviderWrapper>,
        );
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Displays error when the passwords do not match", async () => {
        render(
            <ProviderWrapper>
                <EditPassword setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );

        const newPasswordInput = screen.getByLabelText("New Password");
        const confirmPasswordInput = screen.getByLabelText("Confirm New Password");

        expect(screen.queryByText("This must match the new password field")).not.toBeInTheDocument();
        fireEvent.change(newPasswordInput, { target: { value: "hell0myFri3nd$" } });

        await waitFor(() => {
            expect(screen.queryByText("This must match the new password field")).toBeInTheDocument();
        });

        fireEvent.change(confirmPasswordInput, { target: { value: "HelloMyFriends" } });

        await waitFor(() => {
            expect(
                screen.queryByText("New password does not match. Please reconfirm your new password."),
            ).toBeInTheDocument();
        });
    });

    it("Displays success when the passwords do match", async () => {
        render(
            <ProviderWrapper>
                <EditPassword setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );

        const newPasswordInput = screen.getByLabelText("New Password");
        const confirmPasswordInput = screen.getByLabelText("Confirm New Password");

        expect(screen.queryByText("This must match the new password field")).not.toBeInTheDocument();

        fireEvent.change(newPasswordInput, { target: { value: "hell0myFri3nd$" } });
        fireEvent.change(confirmPasswordInput, { target: { value: "hell0myFri3nd$" } });

        await waitFor(() => {
            expect(screen.queryByText("Passwords Match")).toBeInTheDocument();
        });
    });

    it("Show the text of the New Password field", async () => {
        render(
            <ProviderWrapper>
                <EditPassword setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );

        const currentPasswordInput = screen.getByLabelText("Current Password");
        const [showHideBtn] = screen.queryAllByLabelText("Show Password");

        fireEvent.change(currentPasswordInput, { target: { value: "supercalifragilist" } });
        fireEvent.click(showHideBtn);

        await waitFor(() => {
            expect(currentPasswordInput.getAttribute("type")).toEqual("text");
            expect(currentPasswordInput).toHaveDisplayValue("supercalifragilist");
        });
    });
});

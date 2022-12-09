/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { EditUsername } from "@library/accountSettings/forms/EditUsername";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LoadStatus } from "@library/@types/api/core";
import { act, renderHook } from "@testing-library/react-hooks";
import { useUsernameAvailability } from "@library/accountSettings/forms/EditUsername.hooks";
import { mockAPI } from "@library/__tests__/utility";
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

describe("Edit Username", () => {
    beforeEach(() => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").replyOnce(200, []);
    });
    it("Requires a password confirmation if the session user does not have the user.edit permission", () => {
        render(
            <ProviderWrapper>
                <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        expect(screen.getByText(/New Username/)).toBeInTheDocument();
        expect(screen.getByText(/Password/)).toBeInTheDocument();
    });
    it("Sets form dirty state after username change", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper>
                <EditUsername setIsSaving={() => null} setIsFormDirty={mockFunction} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        const usernameInput = screen.getByRole("textbox");
        fireEvent.change(usernameInput, { target: { value: "test name" } });
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets saving state when patchStatusByPatchID status is loading", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper
                state={{
                    patchStatusByPatchID: {
                        "2-userPatch-0": {
                            status: LoadStatus.LOADING,
                        },
                    },
                }}
            >
                <EditUsername setIsSaving={mockFunction} setIsFormDirty={() => null} setIsSuccess={() => null} />
            </ProviderWrapper>,
        );
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets success state when patchStatusByPatchID status is successful", () => {
        const mockFunction = jest.fn();
        render(
            <ProviderWrapper
                state={{
                    patchStatusByPatchID: {
                        "2-userPatch-0": {
                            status: LoadStatus.SUCCESS,
                        },
                    },
                }}
            >
                <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={mockFunction} />
            </ProviderWrapper>,
        );
        expect(mockFunction).toHaveBeenCalled();
    });
});

describe("useUsernameAvailability", () => {
    it("Make a network request when username is updated", async () => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").replyOnce(200, []);
        const { result, rerender } = renderHook(() => useUsernameAvailability("admin"));
        await act(async () => {
            await rerender();
            await result.current;
        });
        expect(mockAdapter.history.get.length).toBeGreaterThan(0);
    });
});

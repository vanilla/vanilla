/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, screen, act } from "@testing-library/react";
import { EditUsername } from "@library/accountSettings/forms/EditUsername";
import { LoadStatus } from "@library/@types/api/core";
import { renderHook } from "@testing-library/react-hooks";
import { useUsernameAvailability } from "@library/accountSettings/forms/EditUsername.hooks";
import { mockAPI } from "@library/__tests__/utility";
import * as AccountSettingsFixtures from "@library/accountSettings/AccountSettings.fixtures";
import { vitest } from "vitest";

describe("Edit Username", () => {
    beforeEach(() => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").reply(200, []);
        mockAdapter.onPatch(/\/users\/.*/).reply(200, {});
    });

    it("Requires a password confirmation if the session user does not have the user.edit permission", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf>
                    <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        expect(screen.getByText(/New Username/)).toBeInTheDocument();
        expect(screen.getByText(/Password/)).toBeInTheDocument();
    });
    it("Will not require a password confirmation if the user does not have one (e.g. ssoed user)", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf mockUserOverrides={{ hashMethod: "Random" }}>
                    <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        expect(screen.getByText(/New Username/)).toBeInTheDocument();
        expect(screen.queryByText(/Password/)).not.toBeInTheDocument();
    });
    it("Will not require a password confirmation if the user already asked to reset his password by email", async () => {
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf mockUserOverrides={{ hashMethod: "Reset" }}>
                    <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        expect(screen.getByText(/New Username/)).toBeInTheDocument();
        expect(screen.queryByText(/Password/)).not.toBeInTheDocument();
    });
    it("Sets form dirty state after username change", async () => {
        const mockFunction = vitest.fn();
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf>
                    <EditUsername setIsSaving={() => null} setIsFormDirty={mockFunction} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        const usernameInput = screen.getByRole("textbox");
        fireEvent.change(usernameInput, { target: { value: "test name" } });
        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets saving state when patchStatusByPatchID status is loading", async () => {
        const mockFunction = vitest.fn();
        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf
                    state={{
                        patchStatusByPatchID: {
                            "2-userPatch-0": {
                                status: LoadStatus.LOADING,
                            },
                        },
                    }}
                >
                    <EditUsername setIsSaving={mockFunction} setIsFormDirty={() => null} setIsSuccess={() => null} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });

        expect(mockFunction).toHaveBeenCalled();
    });

    it("Sets success state when patchStatusByPatchID status is successful", async () => {
        const mockFunction = vitest.fn();

        await act(async () => {
            render(
                <AccountSettingsFixtures.UserEditingSelf
                    state={{
                        patchStatusByPatchID: {
                            "2-userPatch-0": {
                                status: LoadStatus.SUCCESS,
                            },
                        },
                    }}
                >
                    <EditUsername setIsSaving={() => null} setIsFormDirty={() => null} setIsSuccess={mockFunction} />
                </AccountSettingsFixtures.UserEditingSelf>,
            );
        });
        expect(mockFunction).toHaveBeenCalled();
    });
});

describe("useUsernameAvailability", () => {
    it("Make a network request when username is updated", async () => {
        const mockAdapter = mockAPI();
        mockAdapter.onGet("/users/by-names").replyOnce(200, []);
        const { result, rerender } = renderHook(() => useUsernameAvailability("admin"));
        await act(async () => {
            rerender();
        });
        expect(mockAdapter.history.get.length).toBeGreaterThan(0);
    });
});

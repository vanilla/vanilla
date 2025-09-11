/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as AccountSettingsFixtures from "@library/accountSettings/AccountSettings.fixtures";

import { act, fireEvent, render, screen, waitFor } from "@testing-library/react";

import { EditUsername } from "@library/accountSettings/forms/EditUsername";
import { mockAPI } from "@library/__tests__/utility";
import { renderHook } from "@testing-library/react-hooks";
import { useUsernameAvailability } from "@library/accountSettings/forms/EditUsername.hooks";
import { vitest } from "vitest";

// AIDEV-TODO: Update tests to work with react-query mutation states instead of Redux
// The component now uses useUserMutation() which provides isPending, isSuccess, isError states

describe("Edit Username", () => {
    beforeEach(() => {
        const mockAdapter = mockAPI();
        // Mock the username availability check - catch all requests to this endpoint
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

    it("Sets saving state when mutation is loading", async () => {
        const mockFunction = vitest.fn();
        let editUsernameRef: any;

        render(
            <AccountSettingsFixtures.UserEditingSelf>
                <EditUsername
                    ref={(ref) => {
                        editUsernameRef = ref;
                    }}
                    setIsSaving={mockFunction}
                    setIsFormDirty={() => null}
                    setIsSuccess={() => null}
                />
            </AccountSettingsFixtures.UserEditingSelf>,
        );

        // Trigger the mutation by calling onSave
        act(() => {
            if (editUsernameRef?.onSave) {
                editUsernameRef.onSave();
            }
        });

        // Wait for the mutation to start and check if setIsSaving was called
        await waitFor(() => {
            expect(mockFunction).toHaveBeenCalled();
        });
    });

    it("Sets success state when mutation is successful", async () => {
        const mockFunction = vitest.fn();
        let editUsernameRef: any;

        // Mock a successful API response
        const mockAdapter = mockAPI();
        mockAdapter.onPatch(/users.*/).reply(200, { userID: 2, name: "newusername" });
        // Ensure the username availability endpoint is also mocked
        mockAdapter.onGet("/users/by-names").reply(200, []);

        render(
            <AccountSettingsFixtures.UserEditingSelf>
                <EditUsername
                    ref={(ref) => {
                        editUsernameRef = ref;
                    }}
                    setIsSaving={() => null}
                    setIsFormDirty={() => null}
                    setIsSuccess={mockFunction}
                />
            </AccountSettingsFixtures.UserEditingSelf>,
        );

        // Trigger the mutation by calling onSave
        act(() => {
            if (editUsernameRef?.onSave) {
                editUsernameRef.onSave();
            }
        });

        // Wait for the mutation to complete and check if setIsSuccess was called
        await waitFor(() => {
            expect(mockFunction).toHaveBeenCalled();
        });
    });
});

describe("useUsernameAvailability", () => {
    it("Make a network request when username is updated", async () => {
        const mockAdapter = mockAPI();
        // Mock the exact endpoint that the hook calls
        mockAdapter.onGet("/users/by-names").reply(200, []);
        const { result, rerender } = renderHook(() => useUsernameAvailability("admin"));
        await act(async () => {
            rerender();
        });
        expect(mockAdapter.history.get.length).toBeGreaterThan(0);
    });
});

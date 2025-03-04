/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { useEmailConfirmationToast } from "@library/features/Layout/EmailConfirmation.hook";
import { ToastContext } from "@library/features/toaster/ToastContext";
import { renderHook } from "@testing-library/react-hooks";
import { vitest } from "vitest";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { setMeta } from "@library/utility/appUtils";

describe("EmailConfirmation", () => {
    it("Toast is created when a user is unconfirmed", async () => {
        const addToast = vitest.fn();
        const mockCurrentUser = UserFixture.createMockUser({ userID: 7, name: "new-test-user", emailConfirmed: false });
        setMeta("isConfirmEmailRequired", true);

        const wrapper = ({ children }) => {
            return (
                <CurrentUserContextProvider currentUser={mockCurrentUser}>
                    <ToastContext.Provider
                        value={{
                            toasts: null,
                            addToast,
                            updateToast: () => null,
                            removeToast: () => null,
                        }}
                    >
                        {children}
                    </ToastContext.Provider>
                </CurrentUserContextProvider>
            );
        };

        renderHook(async () => useEmailConfirmationToast(), { wrapper });
        expect(addToast).toHaveBeenCalledTimes(1);
    });
    it("Toast is not created when a user is confirmed", async () => {
        const addToast = vitest.fn();
        const mockCurrentUser = UserFixture.createMockUser({ userID: 8, name: "new-test-user", emailConfirmed: true });

        const wrapper = ({ children }) => {
            return (
                <CurrentUserContextProvider currentUser={mockCurrentUser}>
                    <ToastContext.Provider
                        value={{
                            toasts: null,
                            addToast,
                            updateToast: () => null,
                            removeToast: () => null,
                        }}
                    >
                        {children}
                    </ToastContext.Provider>
                </CurrentUserContextProvider>
            );
        };

        renderHook(async () => useEmailConfirmationToast(), { wrapper });
        expect(addToast).not.toHaveBeenCalled();
    });
    it("Toast is not created for guest users", async () => {
        const addToast = vitest.fn();
        const mockCurrentUser = UserFixture.createMockUser({ userID: 0, name: "Guest" });

        const wrapper = ({ children }) => {
            return (
                <CurrentUserContextProvider currentUser={mockCurrentUser}>
                    <ToastContext.Provider
                        value={{
                            toasts: null,
                            addToast,
                            updateToast: () => null,
                            removeToast: () => null,
                        }}
                    >
                        {children}
                    </ToastContext.Provider>
                </CurrentUserContextProvider>
            );
        };

        renderHook(async () => useEmailConfirmationToast(), { wrapper });
        expect(addToast).not.toHaveBeenCalled();
    });
});

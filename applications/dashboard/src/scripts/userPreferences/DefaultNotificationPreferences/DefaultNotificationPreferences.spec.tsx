/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createMockApi } from "@library/notificationPreferences/fixtures/NotificationPreferences.fixtures";
import { NotificationPreferencesContextProvider } from "@library/notificationPreferences";
import { act, fireEvent, render, screen, within } from "@testing-library/react";
import React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { DefaultNotificationPreferencesModal } from "./DefaultNotificationPreferences";
import { vitest } from "vitest";

describe("Default Notification Preferences Form", () => {
    const mockApi = createMockApi();
    const mockExitHandler = vitest.fn();

    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    beforeEach(async () => {
        await act(async () => {
            render(
                <QueryClientProvider client={queryClient}>
                    <NotificationPreferencesContextProvider {...{ api: mockApi, userID: "defaults" }}>
                        <DefaultNotificationPreferencesModal isVisible exitHandler={mockExitHandler} />
                    </NotificationPreferencesContextProvider>
                </QueryClientProvider>,
            );
        });
    });

    async function switchToTabTwo() {
        await act(async () => {
            const tabTwo = await screen.findByRole("tab", { name: "Group Two" });
            fireEvent.click(tabTwo);
        });
    }

    it("Renders a modal containing a form.", async () => {
        const modal = await screen.findByRole("dialog");
        expect(modal).toBeInTheDocument();
        const form = await within(modal).findByRole("form");
        expect(form).toBeInTheDocument();
    });

    it("There is a tab for each group.", async () => {
        const tabOne = await screen.findByRole("tab", { name: "Group One" });
        expect(tabOne).toBeInTheDocument();
        const tabTwo = await screen.findByRole("tab", { name: "Group Two" });
        expect(tabTwo).toBeInTheDocument();
    });

    it("A given Activity group contains Email and Notification popup checkboxes", async () => {
        await switchToTabTwo();
        const groupInTabTwo = await screen.findByLabelText("Reactions to my comments");
        expect(groupInTabTwo).toBeInTheDocument();
        const emailCheckbox = await within(groupInTabTwo).findByRole("checkbox", { name: "Email" });
        const notificationPopupCheckbox = await within(groupInTabTwo).findByRole("checkbox", {
            name: "Notification popup",
        });
        expect(emailCheckbox).toBeInTheDocument();
        expect(notificationPopupCheckbox).toBeInTheDocument();
    });

    it("Submitting the form dispatches a PATCH request, and the exitHandler callback", async () => {
        const form = await screen.findByRole("form");
        await act(async () => {
            fireEvent.submit(form);
        });
        expect(mockApi.patchUserPreferences).toHaveBeenCalledTimes(1);
        expect(mockExitHandler).toHaveBeenCalledTimes(1);
    });
});

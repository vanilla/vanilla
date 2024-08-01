/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    createMockApi,
    mockPreferences,
} from "@library/notificationPreferences/fixtures/NotificationPreferences.fixtures";
import { NotificationPreferencesContextProvider } from "@library/notificationPreferences";
import { act, fireEvent, render, screen, within } from "@testing-library/react";
import React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { NotificationPreferencesForm } from "./NotificationPreferences";
import { EmailLanguagePreferencesImpl } from "@library/notificationPreferences/EmailLanguagePreferences";
import { isINotificationPreference } from "@library/notificationPreferences/utils";
import { vitest } from "vitest";

const mockUserID = 2;

describe("Notification Preferences Form", () => {
    describe("Form structure", () => {
        const mockApi = createMockApi();
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });
        it("A given group contains checkboxes that can be interacted with", async () => {
            render(
                <QueryClientProvider client={queryClient}>
                    <NotificationPreferencesContextProvider {...{ api: mockApi, userID: mockUserID }}>
                        <NotificationPreferencesForm />
                    </NotificationPreferencesContextProvider>
                </QueryClientProvider>,
            );

            const groupTwoTitle = await screen.findByText("Group Two");
            expect(groupTwoTitle).toBeInTheDocument();

            const groupTwo = groupTwoTitle.closest("div")!;
            const rowDescription = within(groupTwo).getByText("Reactions to my comments");
            expect(rowDescription).toBeInTheDocument();

            const row = rowDescription.closest("tr")!;

            // There are two checkboxes described by the same description
            const checkboxes = within(row).getAllByRole("checkbox", { description: "Reactions to my comments" });
            expect(checkboxes.length).toEqual(2);

            // The initial state of a given preference checkbox corresponds to the known data
            const emailCheckbox = within(row).getByLabelText<HTMLInputElement>("Email", { exact: false });
            expect(emailCheckbox).toBeChecked();

            // un-check the email checkbox
            fireEvent.click(emailCheckbox);

            const emailCheckboxAfterClick = within(row).getByLabelText<HTMLInputElement>("Email", { exact: false });
            expect(emailCheckboxAfterClick).not.toBeChecked();

            await vitest.waitFor(() => expect(mockApi.patchUserPreferences).toHaveBeenCalled());
            expect(mockApi.patchUserPreferences).toHaveBeenCalledWith({
                userID: mockUserID,
                preferences: {
                    ...mockPreferences,
                    reactionsToMyComments: {
                        ...(isINotificationPreference(mockPreferences.reactionsToMyComments)
                            ? mockPreferences.reactionsToMyComments
                            : {}),
                        email: false,
                    },
                },
            });
        });
    });

    describe("Debounced submissions", () => {
        const mockApi = createMockApi();
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    retry: false,
                },
            },
        });

        const mockDebounceInterval = 500;

        it("Only calls the submit function once per debounceInterval", async () => {
            render(
                <QueryClientProvider client={queryClient}>
                    <NotificationPreferencesContextProvider {...{ api: mockApi, userID: mockUserID }}>
                        <NotificationPreferencesForm debounceInterval={mockDebounceInterval} />
                    </NotificationPreferencesContextProvider>
                </QueryClientProvider>,
            );

            const form = await screen.findByRole("form", undefined, { timeout: 1000 });
            expect(form).toBeInTheDocument();

            const checkboxes = within(form).getAllByRole("checkbox");

            vitest.useFakeTimers();

            // click one checkbox
            fireEvent.click(checkboxes[0]);

            await act(async () => {
                vitest.advanceTimersByTime(100);
            });

            // the interval is not elapsed, so it hasn't been called yet
            expect(mockApi.patchUserPreferences).not.toHaveBeenCalled();

            // click anothercheckbox
            fireEvent.click(checkboxes[1]);

            await act(async () => {
                vitest.advanceTimersByTime(100);
            });

            // still not elapsed, so not called yet.
            expect(mockApi.patchUserPreferences).not.toHaveBeenCalled();

            await act(async () => {
                vitest.advanceTimersByTime(mockDebounceInterval);
            });

            // the interval has elapsed since last click, and the function was only called once even though there were two clicks
            expect(mockApi.patchUserPreferences).toHaveBeenCalledTimes(1);
            vitest.runOnlyPendingTimers();
            vitest.useRealTimers();
        });
    });
});

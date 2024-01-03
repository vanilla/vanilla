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
            act(() => {
                render(
                    <QueryClientProvider client={queryClient}>
                        <NotificationPreferencesContextProvider {...{ api: mockApi, userID: mockUserID }}>
                            <NotificationPreferencesForm />
                        </NotificationPreferencesContextProvider>
                    </QueryClientProvider>,
                );
            });

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
            await act(async () => {
                fireEvent.click(emailCheckbox);
            });

            const emailCheckboxAfterClick = within(row).getByLabelText<HTMLInputElement>("Email", { exact: false });
            expect(emailCheckboxAfterClick).not.toBeChecked();

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

        const mockDebounceInterval = 3000;

        it("Only calls the submit function once per debounceInterval", async () => {
            act(() => {
                render(
                    <QueryClientProvider client={queryClient}>
                        <NotificationPreferencesContextProvider {...{ api: mockApi, userID: mockUserID }}>
                            <NotificationPreferencesForm debounceInterval={mockDebounceInterval} />
                        </NotificationPreferencesContextProvider>
                    </QueryClientProvider>,
                );
            });

            jest.useFakeTimers();

            const form = await screen.findByRole("form");
            expect(form).toBeInTheDocument();

            const checkboxes = await within(form).findAllByRole("checkbox");

            // click one checkbox
            await act(async () => {
                fireEvent.click(checkboxes[0]);
            });

            await act(async () => {
                jest.advanceTimersByTime(100);
            });

            // the interval is not elapsed, so it hasn't been called yet
            expect(mockApi.patchUserPreferences).not.toHaveBeenCalled();

            // click anothercheckbox
            await act(async () => {
                fireEvent.click(checkboxes[1]);
            });

            await act(async () => {
                jest.advanceTimersByTime(100);
            });

            // still not elapsed, so not called yet.
            expect(mockApi.patchUserPreferences).not.toHaveBeenCalled();

            await act(async () => {
                jest.advanceTimersByTime(mockDebounceInterval);
            });

            // the interval has elapsed since last click, and the function was only called once even though there were two clicks
            expect(mockApi.patchUserPreferences).toHaveBeenCalledTimes(1);
            jest.runOnlyPendingTimers();
            jest.useRealTimers();
        });
    });
});

/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { createMockApi } from "@library/notificationPreferences/fixtures/NotificationPreferences.fixtures";
import { NotificationPreferencesContextProvider } from "@library/notificationPreferences";
import { act, render, screen } from "@testing-library/react";
import React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { EmailLanguagePreferencesImpl } from "@library/notificationPreferences/EmailLanguagePreferences";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";

const mockUserID = 2;

describe("Language preferences", () => {
    const mockApi = createMockApi();
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    const renderInProvider = async (localeOptions: IComboBoxOption[]) => {
        act(() => {
            render(
                <QueryClientProvider client={queryClient}>
                    <NotificationPreferencesContextProvider
                        {...{
                            api: mockApi,
                            userID: mockUserID,
                        }}
                    >
                        <EmailLanguagePreferencesImpl localeOptions={localeOptions} />
                    </NotificationPreferencesContextProvider>
                </QueryClientProvider>,
            );
        });
    };

    it("Displays the language preferences when more than 1 localeOption", async () => {
        await renderInProvider([
            { label: "English", value: "en" },
            { label: "French", value: "fr" },
        ]);

        const languageTitle = await screen.findByText("Notification Language");
        expect(languageTitle).toBeInTheDocument();
    });

    it("Does not display the language preferences when only 1 localeOption", async () => {
        await renderInProvider([{ label: "English", value: "en" }]);

        expect(await screen.queryByText("Notification Language")).not.toBeInTheDocument();
    });
});

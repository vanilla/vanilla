/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    createMockApi,
    mockEditPreferencesParams,
} from "@library/notificationPreferences/fixtures/NotificationPreferences.fixtures";
import {
    NotificationPreferencesContextProvider,
    useNotificationPreferencesContext,
} from "@library/notificationPreferences";
import { act, cleanup, fireEvent, render, screen } from "@testing-library/react";
import React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockApi = createMockApi();
const mockUserID = 2;

function MockForm() {
    const { editPreferences } = useNotificationPreferencesContext();

    return (
        <form
            data-testid={"fakeForm"}
            onSubmit={async (e) => {
                e.preventDefault();
                await editPreferences(mockEditPreferencesParams);
            }}
        />
    );
}

function MockPage() {
    return (
        <QueryClientProvider client={queryClient}>
            <NotificationPreferencesContextProvider {...{ api: mockApi, userID: mockUserID }}>
                <MockForm />
            </NotificationPreferencesContextProvider>
        </QueryClientProvider>
    );
}

describe("NotificationPreferencesContextProvider", () => {
    it("Makes API calls to getSchema and getUserPreferences", () => {
        act(() => {
            render(<MockPage />);
        });
        expect(mockApi.getSchema).toHaveBeenCalledTimes(1);
        expect(mockApi.getUserPreferences).toHaveBeenCalledWith({ userID: mockUserID });
        expect(mockApi.getUserPreferences).toHaveBeenCalledTimes(1);
        cleanup();
    });

    describe("editPreferences", () => {
        it("makes API call to patchUserPreferences", async () => {
            act(() => {
                render(<MockPage />);
            });

            const form = await screen.findByTestId("fakeForm");

            await act(async () => {
                fireEvent.submit(form);
            });

            expect(mockApi.patchUserPreferences).toHaveBeenCalledWith({
                userID: mockUserID,
                preferences: mockEditPreferencesParams,
            });
        });
    });
});

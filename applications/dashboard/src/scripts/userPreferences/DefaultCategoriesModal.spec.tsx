/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, fireEvent, waitFor, screen, getByRole } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import DefaultCategoriesModal, {
    ILegacyCategoryPreferences,
    ISavedDefaultCategory,
} from "@dashboard/userPreferences/DefaultCategoriesModal";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { mockAPI } from "@library/__tests__/utility";
import { DEFAULT_NOTIFICATION_PREFERENCES } from "@vanilla/addon-vanilla/categories/categoriesTypes";

const queryClient = new QueryClient();

const renderInProvider = (preferences: ILegacyCategoryPreferences[] | ISavedDefaultCategory[]) => {
    return render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider
                state={{
                    config: {
                        configsByLookupKey: {
                            [stableObjectHash(["preferences.categoryFollowed.defaults"])]: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    "preferences.categoryFollowed.defaults": JSON.stringify(preferences),
                                },
                            },
                        },
                    },
                }}
            >
                <DefaultCategoriesModal isVisible={true} onCancel={() => null} />
            </TestReduxProvider>
        </QueryClientProvider>,
    );
};

const mockAdapter = mockAPI();
describe("DefaultCategoriesModal", () => {
    beforeEach(() => {
        mockAdapter.reset();
        mockAdapter.onGet(/categories/).reply(200, CategoryPreferencesFixture.getMockCategoryResponse());
    });
    it("renders empty table state", async () => {
        mockAdapter.onGet(/categories.outputFormat.+/).reply(200, []);
        renderInProvider([]);
        await waitFor(() => expect(screen.getByText("No categories selected.")).toBeInTheDocument());
    });
    it("saved categories render in the table", async () => {
        renderInProvider(CategoryPreferencesFixture.mockPreferenceConfig);
        await waitFor(() => expect(screen.getByText("Mock Category 1")).toBeInTheDocument());
    });
    it("legacy saved categories render in the table", async () => {
        renderInProvider(CategoryPreferencesFixture.mockLegacyConfigs);
        await waitFor(() => expect(screen.getByText("Mock Category 1")).toBeInTheDocument());
        await waitFor(() => expect(screen.getByText("Mock Category 2")).toBeInTheDocument());
        await waitFor(() => expect(screen.getByText("Mock Category 3")).toBeInTheDocument());
    });
    it("category configurations are saved", async () => {
        mockAdapter.onPatch(/config/).reply(200, []);

        const config = {
            categoryID: 2,
            preferences: {
                ...DEFAULT_NOTIFICATION_PREFERENCES,
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };

        /**
         * Our current combobox is _very_ difficult to interact with within tests
         * Preloading a category and updating it instead
         */
        renderInProvider([config]);
        // Ensure everything is loaded
        await waitFor(() => expect(screen.getByText("Mock Category 2")).toBeInTheDocument());
        // Find the posts popup checkbox
        const checkbox = screen.getByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });
        // Click it, will mutate state
        await act(async () => {
            fireEvent.click(checkbox);
        });
        // Save the changed configuration
        fireEvent.click(screen.getByText("Save"));
        // Expect the config endpoint to be patched
        expect(mockAdapter.history.patch.length).toBe(1);
        // Ensure the request body has the updated preferences
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data)["preferences.categoryFollowed.defaults"];
        const expected = [
            {
                ...config,
                preferences: {
                    ...config.preferences,
                    "preferences.email.digest": false,
                    "preferences.popup.posts": true,
                },
            },
        ];
        expect(JSON.parse(requestBody)).toEqual(expected);
    });
    it("category can be removed from configuration", async () => {
        mockAdapter.onPatch(/config/).reply(200, []);

        const config = {
            categoryID: 2,
            preferences: {
                ...DEFAULT_NOTIFICATION_PREFERENCES,
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };
        renderInProvider([config]);
        // Ensure everything is loaded
        await waitFor(() => expect(screen.getByText("Mock Category 2")).toBeInTheDocument());
        // Find the posts popup checkbox
        const removeButton = await screen.findByTitle("Remove Category");
        // Click it, will mutate state
        await act(async () => {
            fireEvent.click(removeButton);
        });
        // Save the changed configuration
        fireEvent.click(screen.getByText("Save"));
        // Expect the config endpoint to be patched
        expect(mockAdapter.history.patch.length).toBe(1);
        // Ensure the request body has the updated preferences
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data)["preferences.categoryFollowed.defaults"];
        expect(JSON.parse(requestBody)).toHaveLength(0);
    });
    it("confirmation modal is displayed if changes are made", async () => {
        // Get categories
        mockAdapter.onGet(/categories.outputFormat.+/).reply(200, CategoryPreferencesFixture.getMockCategoryResponse());

        const config = {
            categoryID: 2,
            preferences: {
                ...DEFAULT_NOTIFICATION_PREFERENCES,
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };

        renderInProvider([config]);
        // Ensure everything is loaded
        await waitFor(() => expect(screen.getByText("Mock Category 2")).toBeInTheDocument());
        // Find the posts popup checkbox
        const checkbox = screen.getByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });
        // Click it, will mutate state
        await act(async () => {
            fireEvent.click(checkbox);
        });
        // Save the changed configuration
        fireEvent.click(screen.getByText("Cancel"));
        // Expect the config endpoint to be patched
        expect(screen.getByText("Unsaved Changes")).toBeInTheDocument();
    });
});

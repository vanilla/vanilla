/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, fireEvent, screen, RenderResult, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import DefaultCategoriesModal, {
    ILegacyCategoryPreferences,
    IFollowedCategory,
} from "@dashboard/userPreferences/DefaultFollowedCategories/DefaultCategoriesModal";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter/types";
import { getDefaultCategoryNotificationPreferences } from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { convertOldConfig } from "./DefaultCategories.utils";
import { vitest } from "vitest";

let mockAdapter: MockAdapter;
const queryClient = new QueryClient();
const mockOnSubmit = vitest.fn();

beforeEach(() => {
    queryClient.clear();
    mockAdapter = mockAPI();
    vitest.resetAllMocks();
});

let result: RenderResult;

const renderInProvider = async (preferences: ILegacyCategoryPreferences[] | IFollowedCategory[]) => {
    await act(async () => {
        result = render(
            <QueryClientProvider client={queryClient}>
                <DefaultCategoriesModal
                    initialValues={convertOldConfig(preferences)}
                    onCancel={() => null}
                    onSubmit={mockOnSubmit}
                    isVisible
                />
            </QueryClientProvider>,
        );

        await vitest.dynamicImportSettled();
    });
};

describe("DefaultCategoriesModal", () => {
    beforeEach(() => {
        mockAdapter.onGet(/categories/).reply(200, CategoryPreferencesFixture.getMockCategoryResponse());
    });
    it("renders empty table state", async () => {
        mockAdapter.onGet(/categories.outputFormat.+/).reply(200, []);
        await renderInProvider([]);
        expect(result.getByText("No categories selected.")).toBeInTheDocument();
    });
    it("saved categories render in the table", async () => {
        await renderInProvider(CategoryPreferencesFixture.mockPreferenceConfig);
        expect(result.getByText("Mock Category 1")).toBeInTheDocument();
    });
    it("legacy saved categories render in the table", async () => {
        await renderInProvider(CategoryPreferencesFixture.mockLegacyConfigs);
        expect(result.getByText("Mock Category 1")).toBeInTheDocument();
        expect(result.getByText("Mock Category 2")).toBeInTheDocument();
        expect(result.getByText("Mock Category 3")).toBeInTheDocument();
    });
    it("category configurations are saved", async () => {
        const singleFollowedCategory = {
            categoryID: 2,
            preferences: {
                ...getDefaultCategoryNotificationPreferences(),
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };

        /**
         * Our current combobox is _very_ difficult to interact with within tests
         * Preloading a category and updating it instead
         */
        await renderInProvider([singleFollowedCategory]);

        // Ensure everything is loaded
        await result.findByText("Mock Category 2");

        const form = await result.findByRole("form");

        // Find the posts popup checkbox
        const checkbox = await within(form).findByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });

        await act(async () => {
            // Click it, will mutate state
            await userEvent.click(checkbox);
        });

        // Save the changed configuration
        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockOnSubmit).toHaveBeenCalledWith(
            expect.arrayContaining([
                expect.objectContaining({
                    ...singleFollowedCategory,
                    preferences: {
                        ...singleFollowedCategory.preferences,
                        "preferences.email.digest": false,
                        "preferences.popup.posts": true,
                    },
                }),
            ]),
        );
    });
    it("category can be removed from configuration", async () => {
        const singleFollowedCategory = {
            categoryID: 2,
            preferences: {
                ...getDefaultCategoryNotificationPreferences(),
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };
        await renderInProvider([singleFollowedCategory]);
        // Ensure everything is loaded
        await result.findByText("Mock Category 2");

        const form = await result.findByRole("form");
        // Find the posts popup checkbox
        const removeButton = await within(form).findByTitle("Remove Category");
        await act(async () => {
            // Click it, will mutate state
            await userEvent.click(removeButton);
        });
        // Save the changed configuration
        await act(async () => {
            fireEvent.submit(form);
        });

        expect(mockOnSubmit).toHaveBeenCalledWith(expect.arrayContaining([]));
    });
    it("confirmation modal is displayed if changes are made", async () => {
        // Get categories
        mockAdapter.onGet(/categories.outputFormat.+/).reply(200, CategoryPreferencesFixture.getMockCategoryResponse());

        const singleFollowedCategory = {
            categoryID: 2,
            preferences: {
                ...getDefaultCategoryNotificationPreferences(),
                "preferences.followed": true,
                "preferences.email.posts": true,
            },
        };

        await renderInProvider([singleFollowedCategory]);
        // Ensure everything is loaded
        await result.findByText("Mock Category 2");
        // Find the posts popup checkbox
        const checkbox = await screen.findByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });
        await act(async () => {
            // Click it, will mutate state
            await userEvent.click(checkbox);
        });

        await act(async () => {
            // Save the changed configuration
            await userEvent.click(screen.getByText("Cancel"));
        });
        expect(screen.getByText("Unsaved Changes")).toBeInTheDocument();
    });
});

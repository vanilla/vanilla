/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, fireEvent, screen, RenderResult, within } from "@testing-library/react";
import DefaultCategoriesModal, {
    ILegacyCategoryPreferences,
    IFollowedCategory,
} from "@dashboard/userPreferences/DefaultCategoriesModal";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";
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

const renderInProvider = (preferences: ILegacyCategoryPreferences[] | IFollowedCategory[]) => {
    return render(
        <QueryClientProvider client={queryClient}>
            <DefaultCategoriesModal
                initialValues={convertOldConfig(preferences)}
                onCancel={() => null}
                onSubmit={mockOnSubmit}
                isVisible
            />
        </QueryClientProvider>,
    );
};

describe("DefaultCategoriesModal", () => {
    let result: RenderResult;

    beforeEach(() => {
        mockAdapter.onGet(/categories/).reply(200, CategoryPreferencesFixture.getMockCategoryResponse());
    });
    it("renders empty table state", async () => {
        mockAdapter.onGet(/categories.outputFormat.+/).reply(200, []);
        await act(async () => {
            result = renderInProvider([]);
        });
        expect(await result.findByText("No categories selected.")).toBeInTheDocument();
    });
    it("saved categories render in the table", async () => {
        await act(async () => {
            result = renderInProvider(CategoryPreferencesFixture.mockPreferenceConfig);
        });
        expect(await result.findByText("Mock Category 1")).toBeInTheDocument();
    });
    it("legacy saved categories render in the table", async () => {
        await act(async () => {
            result = renderInProvider(CategoryPreferencesFixture.mockLegacyConfigs);
        });
        expect(await result.findByText("Mock Category 1")).toBeInTheDocument();
        expect(await result.findByText("Mock Category 2")).toBeInTheDocument();
        expect(await result.findByText("Mock Category 3")).toBeInTheDocument();
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
        await act(async () => {
            result = renderInProvider([singleFollowedCategory]);
        });

        // Ensure everything is loaded
        vitest.waitFor(async () => expect(await result.findByText("Mock Category 2")).toBeInTheDocument());

        const form = await result.findByRole("form");

        // Find the posts popup checkbox
        const checkbox = await within(form).findByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });

        // Click it, will mutate state
        fireEvent.click(checkbox);

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
        await act(async () => {
            result = renderInProvider([singleFollowedCategory]);
        });
        // Ensure everything is loaded
        vitest.waitFor(async () => expect(await result.findByText("Mock Category 2")).toBeInTheDocument());

        const form = await result.findByRole("form");
        // Find the posts popup checkbox
        const removeButton = await within(form).findByTitle("Remove Category");
        // Click it, will mutate state
        fireEvent.click(removeButton);

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

        await act(async () => {
            result = renderInProvider([singleFollowedCategory]);
        });
        // Ensure everything is loaded
        vitest.waitFor(async () => expect(await result.findByText("Mock Category 2")).toBeInTheDocument());
        // Find the posts popup checkbox
        const checkbox = await screen.findByRole("checkbox", {
            name: "Notification popup",
            description: "Notify of new posts",
        });
        // Click it, will mutate state
        fireEvent.click(checkbox);
        // Save the changed configuration
        fireEvent.click(screen.getByText("Cancel"));
        expect(screen.getByText("Unsaved Changes")).toBeInTheDocument();
    });
});

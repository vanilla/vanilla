/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILegacyCategoryPreferences, ISavedDefaultCategory } from "@dashboard/userPreferences/DefaultCategoriesModal";
import {
    CategoryDisplayAs,
    CategoryPostNotificationType,
    DEFAULT_NOTIFICATION_PREFERENCES,
    ICategory,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";

export class CategoryPreferencesFixture {
    public static mockLegacyConfigs: ILegacyCategoryPreferences[] = [
        {
            categoryID: 1,
            name: "General",
            iconUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
            useEmailNotifications: false,
            postNotifications: CategoryPostNotificationType.FOLLOW,
        },
        {
            categoryID: 2,
            name: "Category Two",
            iconUrl: "https://us.v-cdn.net/5022541/uploads/userpics/446/n2RXLCE65F21T.jpg",
            useEmailNotifications: true,
            postNotifications: CategoryPostNotificationType.ALL,
        },
        {
            categoryID: 3,
            name: "Category Three",
            useEmailNotifications: true,
            postNotifications: CategoryPostNotificationType.DISCUSSIONS,
        },
    ];

    public static mockPreferenceConfig: ISavedDefaultCategory[] = [
        {
            categoryID: 1,
            preferences: {
                ...DEFAULT_NOTIFICATION_PREFERENCES,
            },
        },
    ];

    public static createMockCategory = (overrides: Partial<ICategory>): ICategory => {
        return {
            categoryID: 1,
            name: "Mock Category",
            url: "/mock-category",
            description: "mock category description",
            parentCategoryID: null,
            customPermissions: false,
            isArchived: false,
            urlcode: "/",
            displayAs: CategoryDisplayAs.DEFAULT,
            countCategories: 1,
            countDiscussions: 10,
            countComments: 10,
            countAllDiscussions: 10,
            countAllComments: 10,
            followed: false,
            depth: 0,
            children: [],
            dateInserted: new Date("2023-06-16").toUTCString(),
            iconUrl: "/some-icon.url",
            ...overrides,
        };
    };

    public static getMockCategoryResponse = (amount: number = 10) => {
        return new Array(amount).fill(null).map((_, index) =>
            this.createMockCategory({
                categoryID: index,
                name: `Mock Category ${index}`,
                url: `/mock-category-${index}`,
            }),
        );
    };
}

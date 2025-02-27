/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { STORY_USER } from "@library/storybook/storyData";
import { CategoryDisplayAs } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

export const mockedCategories = [
    {
        categoryID: 1,
        name: "Mocked Category",
        url: "https://dev.vanilla.local/categories/mocked-category",
        description: "",
        parentCategoryID: null,
        customPermissions: false,
        isArchived: false,
        urlcode: "mocked-category",
        displayAs: CategoryDisplayAs.DISCUSSIONS,
        countCategories: 0,
        countDiscussions: 10,
        countComments: 20,
        countAllDiscussions: 10,
        countAllComments: 20,
        followed: true,
        depth: 1,
        children: [],
        dateInserted: "2023-04-03T17:12:22+00:00",
        iconUrl: "https://dev.vanilla.local/uploads/Z4UGG1KKALH8/hl-background-002b.jpg",
        dateFollowed: "2023-04-18T23:59:59+00:00",
        preferences: {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        },
        lastPost: {
            ...DiscussionFixture.fakeDiscussions[0],
            insertUser: STORY_USER,
        },
    },
];

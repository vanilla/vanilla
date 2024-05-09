/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { FollowedContentContext } from "@library/followedContent/FollowedContentContext";
import { CategoryDisplayAs, CategoryPostNotificationType } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { STORY_USER } from "@library/storybook/storyData";
import { CategorySortOption } from "@dashboard/@types/api/category";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";

export const mockedCategories = [
    {
        categoryID: 1,
        name: "Mocked Category",
        url: "https://dev.vanilla.localhost/categories/mocked-category",
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
        iconUrl: "https://dev.vanilla.localhost/uploads/Z4UGG1KKALH8/hl-background-002b.jpg",
        dateFollowed: "2023-04-18T23:59:59+00:00",
        preferences: {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        },
        lastPost: {
            ...fakeDiscussions[0],
            lastUser: STORY_USER,
        },
    },
];

function HasFollowedCategories({ children }: { children: ReactNode }) {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    usersByID: {
                        2: {
                            status: LoadStatus.SUCCESS,
                            data: UserFixture.createMockUser({ userID: 2 }),
                        },
                    },
                },
            }}
        >
            <FollowedContentContext.Provider
                value={{
                    userID: 1,
                    followedCategories: mockedCategories,
                    sortBy: CategorySortOption.ALPHABETICAL,
                    setSortBy: () => {},
                    error: null,
                }}
            >
                {children}
            </FollowedContentContext.Provider>
        </TestReduxProvider>
    );
}

function NoFollowedCategories({ children }: { children: ReactNode }) {
    return (
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        ...UserFixture.adminAsCurrent,
                    },
                    usersByID: {
                        2: {
                            status: LoadStatus.SUCCESS,
                            data: UserFixture.createMockUser({ userID: 2 }),
                        },
                    },
                },
            }}
        >
            <FollowedContentContext.Provider
                value={{
                    userID: 1,
                    followedCategories: [],
                    sortBy: CategorySortOption.ALPHABETICAL,
                    setSortBy: () => {},
                    error: null,
                }}
            >
                {children}
            </FollowedContentContext.Provider>
        </TestReduxProvider>
    );
}

export const FollowedContentFixtures = {
    HasFollowedCategories,
    NoFollowedCategories,
};

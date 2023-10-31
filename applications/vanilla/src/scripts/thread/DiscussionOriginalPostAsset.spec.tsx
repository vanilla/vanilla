/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { CategoryDisplayAs, ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { IMe } from "@library/@types/api/users";

const MOCK_CATEGORY: ICategory = {
    categoryID: 1,
    name: "Mock category",
    url: "/mock-category",
    description: "Mock category description",
    parentCategoryID: null,
    customPermissions: false,
    isArchived: false,
    urlcode: "mockCategory",
    displayAs: CategoryDisplayAs.DEFAULT,
    countCategories: 0,
    countDiscussions: 1,
    countComments: 1,
    countAllDiscussions: 1,
    countAllComments: 1,
    followed: false,
    depth: 0,
    children: [],
    dateInserted: "2023-07-28",
};

const MOCK_DISCUSSION: IDiscussion = {
    discussionID: 1,
    type: "discussion",
    name: "Mock Discussion",
    url: "/mock-discussion",
    canonicalUrl: "/mock-discussion",
    dateInserted: "2023-07-28",
    insertUser: UserFixture.createMockUser({ userID: 1, name: "Mock User" }),
    insertUserID: 1,
    pinned: false,
    closed: false,
    score: 1,
    countViews: 10,
    countComments: 10,
    categoryID: 1,
    category: MOCK_CATEGORY,
    body: "Mock discussion content",
};

const renderInProvider = (currentUser?: Partial<IMe>) => {
    render(
        <TestReduxProvider
            state={{
                users: {
                    current: {
                        status: LoadStatus.SUCCESS,
                        data: {
                            ...MOCK_DISCUSSION.insertUser,
                            isAdmin: false,
                            countUnreadNotifications: 0,
                            countUnreadConversations: 0,
                            ...currentUser,
                        } as IMe,
                    },
                },
            }}
        >
            <DiscussionOriginalPostAsset discussion={MOCK_DISCUSSION} category={MOCK_DISCUSSION.category!} />
        </TestReduxProvider>,
    );
};

describe("DiscussionOriginalPostAsset", () => {
    it("Discussions do not bookmark to guests", () => {
        renderInProvider({ userID: 0 });
        expect(screen.queryByRole("button", { name: "Discussion Options" })).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Bookmark")).not.toBeInTheDocument();
        expect(screen.queryByText("Mock discussion content")).toBeInTheDocument();
    });
    it("Discussions do not display options to user that have not posted them", () => {
        renderInProvider({ userID: -1 });
        expect(screen.queryByRole("button", { name: "Discussion Options" })).not.toBeInTheDocument();
        expect(screen.queryByLabelText("Bookmark")).toBeInTheDocument();
        expect(screen.queryByText("Mock discussion content")).toBeInTheDocument();
    });
});

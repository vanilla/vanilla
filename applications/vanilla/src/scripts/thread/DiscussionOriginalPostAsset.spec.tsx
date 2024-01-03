/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { act, render, screen, within } from "@testing-library/react";
import DiscussionOriginalPostAsset from "@vanilla/addon-vanilla/thread/DiscussionOriginalPostAsset";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { DiscussionFixture } from "./__fixtures__/Discussion.Fixture";
import { GUEST_USER_ID } from "@library/features/users/userModel";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const MOCK_CATEGORY_FRAGMENT: ICategoryFragment = {
    categoryID: DiscussionFixture.mockDiscussion.categoryID,
    name: "Mock category",
    url: "/mock-category",
};

// afterEach(() => {
//     queryClient.clear();
// });

async function renderInProvider(children: ReactNode, currentUser?: Partial<IMe>) {
    const queryClient = new QueryClient();

    await act(async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <TestReduxProvider
                    state={{
                        users: {
                            current: {
                                status: LoadStatus.SUCCESS,
                                data: {
                                    isAdmin: false,
                                    countUnreadNotifications: 0,
                                    countUnreadConversations: 0,
                                    ...currentUser,
                                } as IMe,
                            },
                        },
                    }}
                >
                    {children}
                </TestReduxProvider>
            </QueryClientProvider>,
        );
    });
}

describe("DiscussionOriginalPostAsset", () => {
    describe("Viewing discussion as guest", () => {
        beforeEach(async () => {
            await renderInProvider(
                <DiscussionOriginalPostAsset
                    discussion={DiscussionFixture.mockDiscussion}
                    category={MOCK_CATEGORY_FRAGMENT}
                />,
                { userID: GUEST_USER_ID },
            );
        });

        it("No Options menu is rendered", () => {
            expect(screen.queryByRole("button", { name: "Discussion Options" })).not.toBeInTheDocument();
        });
        it("No bookmark button is rendered", () => {
            expect(screen.queryByLabelText("Bookmark")).not.toBeInTheDocument();
        });
        it("Discussion content is rendered", () => {
            expect(screen.queryByText("Mock discussion content")).toBeInTheDocument();
        });
    });

    describe("Viewing discussion as user (not the author)", () => {
        beforeEach(async () => {
            await renderInProvider(
                <DiscussionOriginalPostAsset
                    discussion={DiscussionFixture.mockDiscussion}
                    category={MOCK_CATEGORY_FRAGMENT}
                />,
                { userID: 123 },
            );
        });

        it("No Options menu is rendered", () => {
            expect(screen.queryByRole("button", { name: "Discussion Options" })).not.toBeInTheDocument();
        });

        it("Bookmark button is rendered", () => {
            expect(screen.queryByLabelText("Bookmark")).toBeInTheDocument();
        });

        it("Discussion content is rendered", () => {
            expect(screen.queryByText("Mock discussion content")).toBeInTheDocument();
        });
    });

    describe("Closed discussion", () => {
        beforeEach(async () => {
            await renderInProvider(
                <DiscussionOriginalPostAsset
                    discussion={{ ...DiscussionFixture.mockDiscussion, closed: true }}
                    category={MOCK_CATEGORY_FRAGMENT}
                />,
            );
        });
        it("A 'closed' tag is rendered within the heading", async () => {
            const heading = await screen.findByRole("heading");
            const closedTag = await within(heading).findByText("Closed");
            expect(closedTag).toBeInTheDocument();
        });
    });
});

/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { act, fireEvent, render, screen } from "@testing-library/react";
import { FollowedContentImpl } from "@library/followedContent/FollowedContent";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { FollowedContentContext } from "@library/followedContent/FollowedContentContext";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import { mockedCategories } from "@library/followedContent/__fixtures__/FollowedContent.fixture";

const queryClient = new QueryClient();

const renderFollowedContent = (withAdditionalFollowedContent?: boolean) => {
    const additionalFollowedContent = withAdditionalFollowedContent
        ? [{ contentName: "Test-Content", contentRenderer: () => <div>{"Something"}</div> }]
        : [];
    return render(
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <FollowedContentContext.Provider
                    value={{
                        userID: 1,
                        additionalFollowedContent: additionalFollowedContent,
                    }}
                >
                    <FollowedContentImpl userID={1} />
                </FollowedContentContext.Provider>
            </CurrentUserContextProvider>
        </QueryClientProvider>,
    );
};

describe("FollowedContent", () => {
    let mockAdapter: MockAdapter;
    beforeAll(() => {
        mockAdapter = mockAPI();
        mockAdapter.onGet("/categories").reply(200, mockedCategories);
    });
    it("Displays the page header", () => {
        renderFollowedContent();
        const header = screen.getByRole("heading", { name: "Followed Content" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h1");
    });

    it("Displays the sub header", () => {
        renderFollowedContent();
        const header = screen.getByRole("heading", { name: "Manage Categories" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h2");
    });

    it("Displays the mocked category", async () => {
        renderFollowedContent();
        const categoryTitle = await screen.getByRole("heading", { name: "Mocked Category" });
        expect(categoryTitle).toBeInTheDocument();
        expect(categoryTitle.tagName.toLowerCase()).toEqual("h3");

        expect(screen.getByText("10 discussions")).toBeInTheDocument();

        const mostRecentDiscussionLink = await screen.findByRole("link", { name: "Unresolved Discussion" });
        expect(mostRecentDiscussionLink).toBeInTheDocument();

        const mostRecentDiscussionUser = await screen.findByRole("link", { name: "Joe Walsh" });
        expect(mostRecentDiscussionUser).toBeInTheDocument();
    });

    it("Displays the CategoryFollowDropDown", async () => {
        renderFollowedContent();

        const followButton = await screen.getByRole("button", { name: "Following" });
        expect(followButton).toBeInTheDocument();

        expect(screen.queryByText("Follow")).not.toBeInTheDocument();

        await act(async () => {
            fireEvent.click(followButton);
        });

        expect(screen.queryByText("Unfollow Category")).toBeInTheDocument();
    });

    it("We have followed content other than categories, tabs should be rendered with relevant info instead of sub header", async () => {
        renderFollowedContent(true);
        const header = await screen.queryByRole("heading", { name: "Manage Categories" });
        expect(header).not.toBeInTheDocument();

        const tabs = await screen.getByRole("tablist");
        expect(tabs).toBeInTheDocument();

        const additionalFollowedContent = await screen.getByText("Manage Test-Content");
        expect(additionalFollowedContent).toBeInTheDocument();
    });
});

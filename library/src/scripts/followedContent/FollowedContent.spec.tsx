/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { act, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { FollowedContentFixtures } from "@library/followedContent/__fixtures__/FollowedContent.fixture";
import { FollowedContentImpl } from "@library/followedContent/FollowedContent";
import { FollowedContentContext } from "@library/followedContent/FollowedContentContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";

const queryClient = new QueryClient();

const renderFollowedContent = () => {
    return render(
        <QueryClientProvider client={queryClient}>
            <FollowedContentFixtures.HasFollowedCategories>
                <FollowedContentImpl />
            </FollowedContentFixtures.HasFollowedCategories>
        </QueryClientProvider>,
    );
};

const renderEmptyContent = () => {
    return render(
        <FollowedContentFixtures.NoFollowedCategories>
            <FollowedContentImpl />
        </FollowedContentFixtures.NoFollowedCategories>,
    );
};

describe("FollowedContent", () => {
    it("Displays the page header", () => {
        renderFollowedContent();
        const header = screen.getByRole("heading", { name: "Followed Content" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h1");
    });

    it("Displays the sub header", () => {
        renderFollowedContent();
        const header = screen.getByRole("heading", { name: "Manage Followed Categories" });
        expect(header).toBeInTheDocument();
        expect(header.tagName.toLowerCase()).toEqual("h2");
    });

    it("Displays the mocked category", async () => {
        renderFollowedContent();
        const categoryTitle = screen.getByRole("heading", { name: "Mocked Category" });
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
        const followButton = screen.getByTitle("Following");
        expect(followButton).toBeInTheDocument();

        expect(screen.queryByText("Follow")).not.toBeInTheDocument();

        await act(async () => {
            fireEvent.click(followButton);
        });

        expect(screen.queryByText("Unfollow Category")).toBeInTheDocument();
    });

    it("Displays the empty state", async () => {
        renderEmptyContent();
        expect(screen.getByText("No categories followed")).toBeInTheDocument();
    });
});

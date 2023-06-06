/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient();

const renderInProvider = () => {
    return render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider>
                <CategoryFollowDropDown userID={1} categoryID={1} categoryName="Test Category" />
            </TestReduxProvider>
        </QueryClientProvider>,
    );
};

describe("CategoryFollowDropDown", () => {
    it("Clicks the button and opens the dropdown", async () => {
        const { findByRole } = renderInProvider();
        const triggerButton = await findByRole("button", { name: "Follow" });
        expect(triggerButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(triggerButton);
        });

        expect(screen.queryByText(/Notification Preferences/)).toBeInTheDocument();
    });

    it("Clicks the follow button and goes to the next panel, checks and unchecks all the preferences", async () => {
        const { findByRole } = renderInProvider();
        const triggerButton = await findByRole("button", { name: "Follow" });
        expect(triggerButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(triggerButton);
        });

        const followButton = await findByRole("button", { name: "Follow Category" });
        expect(followButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(followButton);
        });

        expect(screen.queryByText(/Test Category/)).toBeInTheDocument();

        const postsCheckbox = await findByRole("checkbox", { name: "Notify of new posts" });
        expect(postsCheckbox).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(postsCheckbox);
        });
        expect(postsCheckbox).toBeChecked();

        const commentsCheckbox = await findByRole("checkbox", { name: "Notify of new comments" });
        expect(commentsCheckbox).toBeInTheDocument();

        const emailCheckbox = await findByRole("checkbox", { name: "Send notifications as emails" });
        expect(emailCheckbox).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(commentsCheckbox);
        });
        expect(commentsCheckbox).toBeChecked();

        await act(async () => {
            fireEvent.click(emailCheckbox);
        });
        expect(emailCheckbox).toBeChecked();
    });

    it("Clicks the unfollows the Category and goes back to the first panel ", async () => {
        const { findByRole } = renderInProvider();
        const triggerButton = await findByRole("button", { name: "Follow" });
        expect(triggerButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(triggerButton);
        });

        const followButton = await findByRole("button", { name: "Follow Category" });
        expect(followButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(followButton);
        });

        expect(followButton).not.toBeInTheDocument();

        const unfollowButton = await findByRole("button", { name: "Unfollow Category" });
        expect(unfollowButton).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(unfollowButton);
        });

        expect(unfollowButton).not.toBeInTheDocument();
    });
});

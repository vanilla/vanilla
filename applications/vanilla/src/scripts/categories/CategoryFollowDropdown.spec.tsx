/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent, waitFor } from "@testing-library/react";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { setMeta } from "@library/utility/appUtils";

const queryClient = new QueryClient();

const MOCK_USER_ID = 1;
const MOCK_CATEGORY_ID = 1;

const renderInProvider = (props?: Partial<React.ComponentProps<typeof CategoryFollowDropDown>>) => {
    return render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider>
                <CategoryFollowDropDown
                    userID={MOCK_USER_ID}
                    categoryID={MOCK_CATEGORY_ID}
                    categoryName="Test Category"
                    emailDigestEnabled
                    emailEnabled
                    isOpen
                    {...props}
                />
            </TestReduxProvider>
        </QueryClientProvider>,
    );
};

const mockAdapter = mockAPI();

describe("CategoryFollowDropDown", () => {
    beforeEach(() => {
        mockAdapter.reset();
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).reply(200, {
            NewDiscussion: {
                email: true,
                popup: false,
            },
            NewComment: {
                email: false,
                popup: true,
            },
        });
        jest.useFakeTimers();
    });

    it("unfollowed category displays the follow category button", async () => {
        mockAdapter.onGet(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, []);
        renderInProvider();
        expect(await screen.findByRole("button", { name: "Follow Category" })).toBeInTheDocument();
    });

    it("follow button saves category as followed", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        renderInProvider({ emailDigestEnabled: false });
        const followCategory = await screen.findByRole("button", { name: "Follow Category" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            jest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument());
        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody["preferences.followed"]).toBeTruthy();
        expect(requestBody["preferences.email.digest"]).toBeFalsy();
    });

    it("follow button saves category as followed and subscribes to digest", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        renderInProvider();
        const followCategory = await screen.findByRole("button", { name: "Follow Category" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            jest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument());
        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody["preferences.followed"]).toBeTruthy();
        expect(requestBody["preferences.email.digest"]).toBeTruthy();
    });

    it("follow button saves category as followed and default preferences", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, {
            preferences: {
                ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
                "preferences.followed": true,
            },
        });
        renderInProvider({ emailDigestEnabled: false });
        const followCategory = await screen.findByRole("button", { name: "Follow Category" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            jest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument());

        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);

        expect(requestBody).toEqual({
            "preferences.email.posts": true,
            "preferences.email.comments": false,
            "preferences.followed": true,
            "preferences.popup.posts": false,
            "preferences.popup.comments": true,
        });
    });

    it("displays existing selection", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        renderInProvider({
            notificationPreferences: {
                "preferences.followed": true,
                "preferences.email.digest": true,
                "preferences.email.posts": true,
                "preferences.email.comments": false,
                "preferences.popup.posts": true,
                "preferences.popup.comments": false,
            },
            emailDigestEnabled: true,
        });
        const digestCheckbox = screen.getByRole("checkbox", {
            name: "Include in email digest",
        });
        const emailPost = screen.getByRole("checkbox", {
            name: "Notification Email",
            description: "Notify me of new posts",
        });
        const emailComments = screen.getByRole("checkbox", {
            name: "Notification Email",
            description: "Notify me of new comments",
        });
        const popupPost = screen.getByRole("checkbox", {
            name: "Notification Popup",
            description: "Notify me of new posts",
        });
        const popupComments = screen.getByRole("checkbox", {
            name: "Notification Popup",
            description: "Notify me of new comments",
        });

        expect(digestCheckbox).toBeChecked();
        expect(emailPost).toBeChecked();
        expect(emailComments).not.toBeChecked();
        expect(popupPost).toBeChecked();
        expect(popupComments).not.toBeChecked();
    });

    it("unfollow button removes all existing notifications", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).reply(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        renderInProvider({
            notificationPreferences: {
                "preferences.followed": true,
                "preferences.email.digest": true,
                "preferences.email.posts": true,
                "preferences.email.comments": true,
                "preferences.popup.posts": true,
                "preferences.popup.comments": true,
            },
            emailDigestEnabled: true,
        });
        const followCategory = await screen.findByRole("button", { name: "Unfollow Category" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            jest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Follow Category" })).toBeInTheDocument());
        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody).toEqual({
            "preferences.followed": false,
            "preferences.email.digest": false,
            "preferences.email.posts": false,
            "preferences.email.comments": false,
            "preferences.popup.posts": false,
            "preferences.popup.comments": false,
        });
    });
});

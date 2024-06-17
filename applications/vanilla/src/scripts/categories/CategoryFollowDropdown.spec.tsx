/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, act, fireEvent, waitFor } from "@testing-library/react";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { setMeta } from "@library/utility/appUtils";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";

const queryClient = new QueryClient();

const MOCK_USER_ID = 1;
const MOCK_CATEGORY_ID = 1;

async function renderInProvider(props?: Partial<React.ComponentProps<typeof CategoryFollowDropDown>>) {
    return render(
        <QueryClientProvider client={queryClient}>
            <CategoryFollowDropDown
                userID={MOCK_USER_ID}
                categoryID={MOCK_CATEGORY_ID}
                categoryName="Test Category"
                emailDigestEnabled
                emailEnabled
                isOpen
                {...props}
            />
        </QueryClientProvider>,
    );
}

let mockAdapter: MockAdapter;

describe("CategoryFollowDropDown", () => {
    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.reset();
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).replyOnce(200, {
            NewDiscussion: {
                email: true,
                popup: false,
            },
            NewComment: {
                email: false,
                popup: true,
            },
            DigestEnabled: {
                email: true,
            },
        });
        vitest.useFakeTimers();
    });

    it("unfollowed category displays the follow category button", async () => {
        mockAdapter.onGet(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, []);
        const result = await renderInProvider({ isOpen: false });
        expect(await result.findByRole("button", { name: "Follow" })).toBeInTheDocument();
    });

    it("follow button saves category as followed", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        await renderInProvider({ emailDigestEnabled: false, isOpen: false });
        const followCategory = await screen.findByRole("button", { name: "Follow" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            vitest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument());
        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody["preferences.followed"]).toBeTruthy();
        expect(requestBody["preferences.email.digest"]).toBeFalsy();
    });

    it("follow button saves category as followed and subscribes to digest", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        await renderInProvider({ isOpen: false });
        const followCategory = await screen.findByRole("button", { name: "Follow" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            vitest.runAllTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument());
        expect(mockAdapter.history.patch.length).toBe(1);
        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody["preferences.followed"]).toBeTruthy();
        expect(requestBody["preferences.email.digest"]).toBeTruthy();
    });

    it("follow button saves category as followed and default preferences", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            preferences: {
                ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
                "preferences.followed": true,
            },
        });
        await renderInProvider({ emailDigestEnabled: false, isOpen: false });
        const followCategory = await screen.findByRole("button", { name: "Follow" });
        act(() => {
            fireEvent.click(followCategory);
            // Fast forward timers since network request functions are debounced
            vitest.runAllTimers();
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
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        await renderInProvider({
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
            name: "Notification popup",
            description: "Notify me of new posts",
        });
        const popupComments = screen.getByRole("checkbox", {
            name: "Notification popup",
            description: "Notify me of new comments",
        });

        expect(digestCheckbox).toBeChecked();
        expect(emailPost).toBeChecked();
        expect(emailComments).not.toBeChecked();
        expect(popupPost).toBeChecked();
        expect(popupComments).not.toBeChecked();
    });

    it("unfollow button removes all existing notifications", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        await renderInProvider({
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
            vitest.runOnlyPendingTimers();
        });
        await waitFor(() => expect(screen.getByRole("button", { name: "Follow" })).toBeInTheDocument());
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

describe("Conditionally displays the digest checkbox", () => {
    beforeEach(() => {
        mockAdapter.reset();
    });

    const notificationPreferences = {
        "preferences.followed": true,
        "preferences.email.digest": true,
        "preferences.email.posts": true,
        "preferences.email.comments": false,
        "preferences.popup.posts": true,
        "preferences.popup.comments": false,
    };

    it("Displays digest when user is subscribed", async () => {
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).replyOnce(200, {
            DigestEnabled: {
                email: true,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        await renderInProvider({
            notificationPreferences,
            emailDigestEnabled: true,
        });
        const digestControl = await screen.findByText("Include in email digest");
        expect(digestControl).toBeInTheDocument();
    });

    it("Does not display digest when user is not subscribed", async () => {
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).replyOnce(200, {
            DigestEnabled: {
                email: false,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        await renderInProvider({
            notificationPreferences,
            emailDigestEnabled: false,
        });

        expect(screen.queryByText("Include in email digest")).not.toBeInTheDocument();
    });

    it("Does not display digest when user is subscribed, but no meta", async () => {
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).replyOnce(200, {
            DigestEnabled: {
                email: true,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        await renderInProvider({
            notificationPreferences,
            emailDigestEnabled: true,
        });

        expect(screen.queryByText("Include in email digest")).not.toBeInTheDocument();
    });

    it("Does not display digest when user is subscribed, but emailDigest disabled", async () => {
        mockAdapter.onGet(`/notification-preferences/${MOCK_USER_ID}`).replyOnce(200, {
            DigestEnabled: {
                email: true,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig,
            "preferences.followed": true,
        });

        setMeta("emails.enabled", true);
        setMeta("emails.digest", true);

        await renderInProvider({
            notificationPreferences,
            emailDigestEnabled: false,
        });

        expect(screen.queryByText("Include in email digest")).not.toBeInTheDocument();
    });
});

/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, act, fireEvent, RenderResult, within } from "@testing-library/react";
import { CategoryFollowDropDownWithCategoryNotificationsContext } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { mockAPI } from "@library/__tests__/utility";
import { CategoryPreferencesFixture } from "@dashboard/userPreferences/__fixtures__/CategoryNotificationPreferences.Fixture";
import { setMeta } from "@library/utility/appUtils";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";
import { INotificationPreferencesApi, NotificationPreferencesContextProvider } from "@library/notificationPreferences";
import { createMockApi as createMockNotificationPreferencesApi } from "@library/notificationPreferences/fixtures/NotificationPreferences.fixtures";
import { registerCategoryNotificationType } from "./CategoryNotificationPreferences.hooks";

const queryClient = new QueryClient();

const MOCK_USER_ID = 1;
const MOCK_CATEGORY_ID = 1;
let mockNotificationPreferencesApi: INotificationPreferencesApi | undefined;
let mockAdapter: MockAdapter;

beforeEach(() => {
    vitest.resetAllMocks();
    mockAdapter?.reset();
    queryClient.clear();

    mockAdapter = mockAPI();

    vitest.useFakeTimers();

    setMeta("emails.enabled", true);
    setMeta("emails.digest", true);

    mockNotificationPreferencesApi = createMockNotificationPreferencesApi({
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
});

function renderInProvider(
    props?: Partial<React.ComponentProps<typeof CategoryFollowDropDownWithCategoryNotificationsContext>>,
) {
    return render(
        <QueryClientProvider client={queryClient}>
            <NotificationPreferencesContextProvider userID={MOCK_USER_ID} api={mockNotificationPreferencesApi!}>
                <CategoryFollowDropDownWithCategoryNotificationsContext
                    userID={MOCK_USER_ID}
                    categoryID={MOCK_CATEGORY_ID}
                    categoryName="Test Category"
                    emailDigestEnabled
                    isOpen
                    {...props}
                />
            </NotificationPreferencesContextProvider>
        </QueryClientProvider>,
    );
}

describe("CategoryFollowDropDown", () => {
    let result: ReturnType<typeof renderInProvider>;

    it("unfollowed category displays the follow category button", async () => {
        await act(async () => {
            result = renderInProvider({ isOpen: false });
        });
        expect(await result.findByRole("button", { name: "Follow" })).toBeInTheDocument();
    });

    it("follow button saves category as followed", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({ emailDigestEnabled: false, isOpen: false });
        });
        const followCategory = await result.findByRole("button", { name: "Follow" });
        await act(async () => {
            fireEvent.click(followCategory);
            await vitest.waitFor(() => expect(mockAdapter.history.patch.length).toEqual(1));
        });
        expect(result.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument();

        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody["preferences.followed"]).toBeTruthy();
        expect(requestBody["preferences.email.digest"]).toBeFalsy();
    });

    it("follow button saves category as followed and subscribes to digest", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({ isOpen: false });
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });

        const followCategory = await result.findByRole("button", { name: "Follow" });

        await act(async () => {
            fireEvent.click(followCategory);

            await vitest.waitFor(() => expect(mockAdapter.history.patch.length).toEqual(1));
        });

        expect(result.getByRole("button", { name: "Unfollow Category" })).toBeInTheDocument();
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
        await act(async () => {
            result = renderInProvider({ emailDigestEnabled: false, isOpen: false });
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });
        const followCategory = await result.findByRole("button", { name: "Follow" });
        await act(async () => {
            fireEvent.click(followCategory);
            await vitest.waitFor(() => expect(mockAdapter.history.patch.length).toEqual(1));
        });

        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);

        expect(requestBody).toMatchObject({
            "preferences.email.posts": true,
            "preferences.email.comments": false,
            "preferences.followed": true,
            "preferences.popup.posts": false,
            "preferences.popup.comments": true,
        });
    });

    it("displays existing selection", async () => {
        await act(async () => {
            result = renderInProvider({
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
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });

        const form = await result.findByRole("form");

        const digestCheckbox = within(form).getByRole("checkbox", {
            name: "Include in email digest",
        });
        const emailPost = within(form).getByRole("checkbox", {
            name: "Notification Email",
            description: "Notify me of new posts",
        });
        const emailComments = within(form).getByRole("checkbox", {
            name: "Notification Email",
            description: "Notify me of new comments",
        });
        const popupPost = within(form).getByRole("checkbox", {
            name: "Notification popup",
            description: "Notify me of new posts",
        });
        const popupComments = within(form).getByRole("checkbox", {
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
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": false,
        });

        await act(async () => {
            result = renderInProvider({
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
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });

        const unfollowCategory = await result.findByRole("button", { name: "Unfollow Category" });
        await act(async () => {
            fireEvent.click(unfollowCategory);

            await vitest.waitFor(() => expect(mockAdapter.history.patch.length).toEqual(1));
        });

        expect(result.getByRole("button", { name: "Follow" })).toBeInTheDocument();

        const requestBody = JSON.parse(mockAdapter.history.patch[0].data);
        expect(requestBody).toMatchObject({
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
    let result: ReturnType<typeof renderInProvider>;

    const notificationPreferences = {
        "preferences.followed": true,
        "preferences.email.digest": true,
        "preferences.email.posts": true,
        "preferences.email.comments": false,
        "preferences.popup.posts": true,
        "preferences.popup.comments": false,
    };

    it("Displays digest when user is subscribed", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({
                notificationPreferences,
                emailDigestEnabled: true,
            });
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });

        const digestControl = await result.findByText("Include in email digest");
        expect(digestControl).toBeInTheDocument();
    });

    it("Does not display digest when user is not subscribed", async () => {
        mockNotificationPreferencesApi = createMockNotificationPreferencesApi({
            DigestEnabled: {
                email: false,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({
                notificationPreferences,
                emailDigestEnabled: false,
            });
        });
        expect(result.queryByText("Include in email digest")).not.toBeInTheDocument();
    });

    it("Does not display digest when user is subscribed, but no meta", async () => {
        mockNotificationPreferencesApi = createMockNotificationPreferencesApi({
            DigestEnabled: {
                email: true,
            },
        });

        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({
                notificationPreferences,
                emailDigestEnabled: true,
            });
        });

        expect(result.queryByText("Include in email digest")).not.toBeInTheDocument();
    });

    it("Does not display digest when user is subscribed, but emailDigest disabled", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({
                notificationPreferences,
                emailDigestEnabled: false,
            });
        });

        expect(result.queryByText("Include in email digest")).not.toBeInTheDocument();
    });
});

describe("registerCategoryNotificationType", () => {
    let result: RenderResult;

    let emailCheckbox: HTMLElement;
    let popupCheckbox: HTMLElement;

    const MOCK_CATEGORY_NOTFIICATION_TYPE = "mockCategoryNotificationType";
    const MOCK_DESCRIPTION = "mock description";

    beforeAll(async () => {
        registerCategoryNotificationType(MOCK_CATEGORY_NOTFIICATION_TYPE, {
            getDescription: () => MOCK_DESCRIPTION,
            getDefaultPreferences: (userPreferences) => {
                const { NewDiscussion } = userPreferences ?? {};
                return {
                    [`preferences.popup.${MOCK_CATEGORY_NOTFIICATION_TYPE}`]: NewDiscussion?.popup ?? false,
                    [`preferences.email.${MOCK_CATEGORY_NOTFIICATION_TYPE}`]: NewDiscussion?.email ?? false,
                };
            },
        });
    });

    function locateCheckboxes() {
        const form = result.getByRole("form");
        const row = within(form).getByText(MOCK_DESCRIPTION, { exact: false }).closest("tr")!;
        emailCheckbox = within(row).getByRole("checkbox", {
            name: "Notification Email",
        });
        popupCheckbox = within(row).getByRole("checkbox", {
            name: "Notification popup",
        });
    }

    it("Adds a row of checkboxes to the form", async () => {
        await act(async () => {
            result = renderInProvider({ isOpen: true, emailDigestEnabled: false });
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });
        locateCheckboxes();
        expect(emailCheckbox).toBeInTheDocument();
        expect(popupCheckbox).toBeInTheDocument();
    });

    it("Follow button applies default values (as mapped from the user preferences) to the form", async () => {
        mockAdapter.onPatch(`/categories/${MOCK_CATEGORY_ID}/preferences/${MOCK_USER_ID}`).replyOnce(200, {
            ...CategoryPreferencesFixture.mockPreferenceConfig[0].preferences,
            "preferences.followed": true,
        });

        await act(async () => {
            result = renderInProvider({ isOpen: false, emailDigestEnabled: false });
            await vitest.waitFor(() => expect(mockNotificationPreferencesApi?.getUserPreferences).toHaveReturned());
        });

        const followCategory = await result.findByRole("button", { name: "Follow" });

        await act(async () => {
            fireEvent.click(followCategory);
            await vitest.waitFor(() => expect(mockAdapter.history.patch.length).toEqual(1));
        });

        locateCheckboxes();
        expect(emailCheckbox).toBeChecked();
        expect(popupCheckbox).not.toBeChecked();
    });
});

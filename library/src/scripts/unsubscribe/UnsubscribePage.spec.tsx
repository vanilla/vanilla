/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { mockAPI } from "@library/__tests__/utility";
import Translate from "@library/content/Translate";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import SmartLink from "@library/routing/links/SmartLink";
import { UnsubscribePageImpl } from "@library/unsubscribe/UnsubscribePage";
import { IUnsubscribeToken } from "@library/unsubscribe/unsubscribePage.types";
import {
    useGetUnsubscribe,
    useSaveUnsubscribe,
    useUndoUnsubscribe,
    usePreferenceLink,
} from "@library/unsubscribe/unsubscribePageHooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { act, render, screen, waitFor } from "@testing-library/react";
import { renderHook } from "@testing-library/react-hooks";
import React from "react";
import { MemoryRouter } from "react-router";

const mockAdapter = mockAPI();

// Base result data to be overwritten per test scenario
const TOKEN_RESULT_TEMPLATE = {
    activityID: 1,
    activityTypes: [],
    activityData: [],
    user: {
        userID: 2,
        name: "Test User",
        email: "test@email.com",
        photoUrl: "https://user-images.githubusercontent.com/1770056/74098133-6f625100-4ae2-11ea-8a9d-908d70030647.png",
    },
};

const FETCH_RESULT_TEMPLATE = {
    preferences: [],
    followedCategory: undefined,
    hasMultiple: false,
    isAllProcessed: false,
    isEmailDigest: false,
    isUnfollowCategory: false,
};

// Notification unsubscribe has already been processed
const MOCK_PROCESSED_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIk1lbnRpb24iXSwiQWN0aXZpdHlEYXRhIjpbXSwiVXNlcklEIjoyLCJOYW1lIjoiVGVzdCBVc2VyIiwiRW1haWwiOiJ0ZXN0QGVtYWlsLmNvbSIsIlBob3RvVXJsIjoiaHR0cHM6Ly91c2VyLWltYWdlcy5naXRodWJ1c2VyY29udGVudC5jb20vMTc3MDA1Ni83NDA5ODEzMy02ZjYyNTEwMC00YWUyLTExZWEtOGE5ZC05MDhkNzAwMzA2NDcucG5nIn0.rAUe47FZ9bEk71w8F399qBMjTREZmcWob9q5bcwoqao" as IUnsubscribeToken;

const MOCK_PROCESSED_API_RESULT = {
    preferences: [],
    followCategory: [],
};

const MOCK_PROCESSED_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["Mention"],
    isAllProcessed: true,
};

mockAdapter.onPost(`/unsubscribe/${MOCK_PROCESSED_TOKEN}`).reply(201, MOCK_PROCESSED_API_RESULT);

// Notification that the user earned a new badge
const MOCK_BADGE_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIkJhZGdlIl0sIkFjdGl2aXR5RGF0YSI6W10sIlVzZXJJRCI6MiwiTmFtZSI6IlRlc3QgVXNlciIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyJ9.2Hv5Q-fPoJdXuqV5-kIAte1xRk238ieCnnvxRWUzhCM" as IUnsubscribeToken;

const MOCK_BADGE_API_RESULT = {
    preferences: [{ preference: "Email.Badge", enabled: "0" }],
    followCategory: [],
};

const MOCK_BADGE_RESUBSCRIBE_RESULT = {
    preferences: [{ preference: "Email.Badge", enabled: "1" }],
    followCategory: [],
};

const MOCK_BADGE_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["Badge"],
    preferences: [
        {
            preferenceRaw: "Email.Badge",
            preferenceName: "Badge",
            enabled: false,
            label: <p key="Email.Badge">New badges</p>,
            optionID: "Email||Badge",
        },
    ],
};

const MOCK_BADGE_RESUBSCRIBE_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["Badge"],
    isAllProcessed: true,
    preferences: [
        {
            ...MOCK_BADGE_DATA.preferences[0],
            enabled: true,
        },
    ],
};

mockAdapter.onPost(`/unsubscribe/${MOCK_BADGE_TOKEN}`).reply(201, MOCK_BADGE_API_RESULT);
mockAdapter.onPost(`/unsubscribe/resubscribe/${MOCK_BADGE_TOKEN}`).reply(201, MOCK_BADGE_RESUBSCRIBE_RESULT);

// Notification that someone commented on a post they are participating in and is also in a category they are following
const MOCK_MULTI_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIlBhcnRpY2lwYXRlQ29tbWVudCJdLCJBY3Rpdml0eURhdGEiOnsiY2F0ZWdvcnkiOiJUZXN0IENhdGVnb3J5IiwicmVhc29ucyI6WyJwYXJ0aWNpcGF0ZWQiXX0sIlVzZXJJRCI6MiwiTmFtZSI6IlRlc3QgVXNlciIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyJ9.zrOLcYUXWEG9EM2xJBTuR5i4jWknQlKzMge20k1fDE8" as IUnsubscribeToken;

const MOCK_MULTI_API_RESULT = {
    followCategory: {
        categoryID: 1,
        enabled: "1",
        name: "Test Category",
        preference: "Preferences.Email.NewComment.1",
    },
    preferences: [{ preference: "Email.ParticipateComment", enabled: "1" }],
};

const MOCK_SAVE_API_RESULT = {
    ...MOCK_MULTI_API_RESULT,
    preferences: [{ preference: "Email.ParticipateComment", enabled: "0" }],
};

const MOCK_MULTI_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    hasMultiple: true,
    activityTypes: ["ParticipateComment"],
    activityData: {
        category: "Test Category",
        reasons: ["participated"],
    },
    followedCategory: {
        categoryID: 1,
        categoryName: "Test Category",
        enabled: true,
        label: (
            <p key="Preferences.Email.NewComment.1">
                <SmartLink to="/categories/test-category">Test Category</SmartLink> | New comments on posts
            </p>
        ),
        preferenceName: "NewComment",
        preferenceRaw: "Preferences.Email.NewComment.1",
        optionID: "Preferences||Email||NewComment||1",
    },
    preferences: [
        {
            enabled: true,
            label: <p key="Email.ParticipateComment">New comments on posts I&apos;ve participated in</p>,
            preferenceName: "ParticipateComment",
            preferenceRaw: "Email.ParticipateComment",
            optionID: "Email||ParticipateComment",
        },
    ],
};

const MOCK_SAVE_DATA = {
    ...MOCK_MULTI_DATA,
    preferences: [
        {
            ...MOCK_MULTI_DATA.preferences[0],
            enabled: false,
        },
    ],
};

mockAdapter.onPost(`/unsubscribe/${MOCK_MULTI_TOKEN}`).reply(201, MOCK_MULTI_API_RESULT);
mockAdapter.onPatch(`/unsubscribe/${MOCK_MULTI_TOKEN}`).reply(204, MOCK_SAVE_API_RESULT);

// Landing page to unfollow a category
const MOCK_UNFOLLOW_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIlVuZm9sbG93Q2F0ZWdvcnkiXSwiQWN0aXZpdHlEYXRhIjp7ImNhdGVnb3J5IjoiVGVzdCBDYXRlZ29yeSJ9LCJVc2VySUQiOjIsIk5hbWUiOiJUZXN0IFVzZXIiLCJFbWFpbCI6InRlc3RAZW1haWwuY29tIiwiUGhvdG9VcmwiOiJodHRwczovL3VzZXItaW1hZ2VzLmdpdGh1YnVzZXJjb250ZW50LmNvbS8xNzcwMDU2Lzc0MDk4MTMzLTZmNjI1MTAwLTRhZTItMTFlYS04YTlkLTkwOGQ3MDAzMDY0Ny5wbmcifQ.HkyTPxvo1kbKaJxWrDE4SEPWtPK6HIuxplD9i5oTmds" as IUnsubscribeToken;

const MOCK_UNFOLLOW_API_RESULT = {
    preferences: [],
    followCategory: {
        categoryID: 1,
        enabled: "0",
        name: "Test Category",
        preference: "Preferences.follow.1",
    },
};

const MOCK_UNFOLLOW_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["UnfollowCategory"],
    activityData: {
        category: "Test Category",
    },
    isUnfollowCategory: true,
    followedCategory: {
        categoryID: 1,
        categoryName: "Test Category",
        enabled: false,
        preferenceName: "follow",
        preferenceRaw: "Preferences.follow.1",
        label: (
            <p>
                <Translate
                    source="You are no longer following <0/>"
                    c0={<SmartLink to="/categories/test-category">Test Category</SmartLink>}
                />
            </p>
        ),
        optionID: "Preferences||follow||1",
    },
};

mockAdapter.onPost(`/unsubscribe/${MOCK_UNFOLLOW_TOKEN}`).reply(201, MOCK_UNFOLLOW_API_RESULT);

// Landing page to unsubscribe from email digest
const MOCK_DIGEST_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIkRpZ2VzdEVuYWJsZWQiXSwiQWN0aXZpdHlEYXRhIjpbXSwiVXNlcklEIjoyLCJOYW1lIjoiVGVzdCBVc2VyIiwiRW1haWwiOiJ0ZXN0QGVtYWlsLmNvbSIsIlBob3RvVXJsIjoiaHR0cHM6Ly91c2VyLWltYWdlcy5naXRodWJ1c2VyY29udGVudC5jb20vMTc3MDA1Ni83NDA5ODEzMy02ZjYyNTEwMC00YWUyLTExZWEtOGE5ZC05MDhkNzAwMzA2NDcucG5nIn0.oLjnqaTHTCs6Zf6LIMFoTQAB6KIDQzeKobzzAg54S7k" as IUnsubscribeToken;

const MOCK_DIGEST_API_RESULT = {
    preferences: [
        {
            preference: "Email.DigestEnabled",
            enabled: "0",
        },
    ],
    followCategory: [],
};

const MOCK_DIGEST_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["DigestEnabled"],
    isEmailDigest: true,
    preferences: [
        {
            preferenceRaw: "Email.DigestEnabled",
            preferenceName: "DigestEnabled",
            enabled: false,
            label: <></>,
            optionID: "Email||DigestEnabled",
        },
    ],
};

mockAdapter.onPost(`/unsubscribe/${MOCK_DIGEST_TOKEN}`).reply(202, MOCK_DIGEST_API_RESULT);

const MOCK_DIGEST_HIDE_CATEGORY_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjowLCJBY3Rpdml0eVR5cGVzIjpbIkZvbGxvd2VkQ2F0ZWdvcnkiXSwiQWN0aXZpdHlEYXRhIjp7ImNhdGVnb3J5IjoiVGVzdCBDYXRlZ29yeSJ9LCJVc2VySUQiOjIsIk5hbWUiOiJUZXN0IFVzZXIiLCJFbWFpbCI6InRlc3RAZW1haWwuY29tIiwiUGhvdG9VcmwiOiJodHRwczovL3VzZXItaW1hZ2VzLmdpdGh1YnVzZXJjb250ZW50LmNvbS8xNzcwMDU2Lzc0MDk4MTMzLTZmNjI1MTAwLTRhZTItMTFlYS04YTlkLTkwOGQ3MDAzMDY0Ny5wbmcifQ.M3niNjuqgJdaJaqbltyMk6mMXNwyzJtY-HTfprkU0Nc" as IUnsubscribeToken;

const MOCK_DIGEST_HIDE_CATEGORY_API_RESULT = {
    preferences: [
        {
            preference: "Email.Digest",
            enabled: "1",
            userID: 2,
        },
    ],
    followCategory: {
        categoryID: 1,
        preference: "Preferences.Email.Digest.1",
        name: "Test Category",
        enabled: "1",
        userID: 2,
    },
};

const MOCK_DIGEST_HIDE_CATEGORY_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["FollowedCategory"],
    activityData: {
        category: "Test Category",
    },
    activityID: 0,
    isEmailDigest: false,
    isUnfollowCategory: false,
    hasMultiple: true,
    preferences: [
        {
            preferenceRaw: "Email.Digest",
            preferenceName: "Digest",
            enabled: true,
            label: <></>,
            optionID: "Email||Digest",
        },
    ],
    followedCategory: {
        categoryID: 1,
        categoryName: "Test Category",
        enabled: true,
        label: <SmartLink to="/categories/test-category">Test Category</SmartLink>,
        preferenceName: "Digest",
        preferenceRaw: "Preferences.Email.Digest.1",
        optionID: "Preferences||Email||Digest||1",
    },
};

mockAdapter.onPost(`/unsubscribe/${MOCK_DIGEST_HIDE_CATEGORY_TOKEN}`).reply(202, MOCK_DIGEST_HIDE_CATEGORY_API_RESULT);

// Wrapper with QueryClientProvider for testing hooks
function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            ...UserFixture.adminAsCurrent,
                        },
                    },
                }}
            >
                {children}
            </TestReduxProvider>
        </QueryClientProvider>
    );
    return Wrapper;
}

// Render page with proper provider wrappers
function renderInProviders(token: IUnsubscribeToken) {
    const queryClient = new QueryClient();

    return render(
        <QueryClientProvider client={queryClient}>
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            ...UserFixture.adminAsCurrent,
                        },
                    },
                }}
            >
                <MemoryRouter>
                    <UnsubscribePageImpl token={token} />
                </MemoryRouter>
            </TestReduxProvider>
        </QueryClientProvider>,
    );
}

describe("UnsubscribePage Hooks", () => {
    describe("useGetUnsubscribe", () => {
        it("Token returns no notifications as they have been processed", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_PROCESSED_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_PROCESSED_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_PROCESSED_DATA);
        });

        it("Token returns reason for notification is new badge", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_BADGE_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_BADGE_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_BADGE_DATA);
        });

        it("Token returns multiple reasons for notification", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_MULTI_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_MULTI_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_MULTI_DATA);
        });

        it("Token returns unfollow category data", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_UNFOLLOW_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_UNFOLLOW_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_UNFOLLOW_DATA);
        });

        it("Token returns email digest unsubscribe data", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_DIGEST_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_DIGEST_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_DIGEST_DATA);
        });

        it("Token returns email digest hide category data", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(MOCK_DIGEST_HIDE_CATEGORY_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutateAsync(MOCK_DIGEST_HIDE_CATEGORY_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(MOCK_DIGEST_HIDE_CATEGORY_DATA);
        });
    });

    it("Undo unsubscribe", async () => {
        const { result, waitFor } = renderHook(() => useUndoUnsubscribe(MOCK_BADGE_TOKEN), {
            wrapper: queryClientWrapper(),
        });

        act(() => {
            result.current.mutateAsync(MOCK_BADGE_TOKEN);
        });

        await waitFor(() => {
            return result.current.isSuccess;
        });

        expect(result.current.data).toStrictEqual(MOCK_BADGE_RESUBSCRIBE_DATA);
    });

    it("Unsubscribe from only one notification in a list of choices", async () => {
        const postData = {
            ...MOCK_MULTI_DATA,
            preferences: [
                {
                    ...MOCK_MULTI_DATA.preferences[0],
                    enabled: false,
                },
            ],
        };

        const { result, waitFor } = renderHook(() => useSaveUnsubscribe(MOCK_MULTI_TOKEN), {
            wrapper: queryClientWrapper(),
        });

        act(() => {
            result.current.mutateAsync(postData);
        });

        await waitFor(() => {
            return result.current.isSuccess;
        });

        expect(result.current.data).toStrictEqual(MOCK_SAVE_DATA);
    });

    it("Create link to notification preferences page for current user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const link = result.current(TOKEN_RESULT_TEMPLATE.user);
        await waitFor(() => {
            expect(link).toBe("/profile/preferences");
        });
    });

    it("Create link to followed content page for current user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const link = result.current(TOKEN_RESULT_TEMPLATE.user, true);
        await waitFor(() => {
            expect(link).toBe("/profile/followed-content");
        });
    });

    it("Create link to notification preferences page for another user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const tmpUser = {
            ...TOKEN_RESULT_TEMPLATE.user,
            userID: 100,
        };
        const link = result.current(tmpUser);
        await waitFor(() => {
            expect(link).toBe("/profile/preferences?accountConflict=true");
        });
    });

    it("Create link to followed content page for another user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const tmpUser = {
            ...TOKEN_RESULT_TEMPLATE.user,
            userID: 100,
        };
        const link = result.current(tmpUser, true);
        await waitFor(() => {
            expect(link).toBe("/profile/followed-content?accountConflict=true");
        });
    });
});

describe("UnsubscribePage Rendering", () => {
    it("Renders landing page with notification that unsubscribe has been processed", async () => {
        renderInProviders(MOCK_PROCESSED_TOKEN);

        await waitFor(() => {
            expect(screen.getByText(/Request Processed/)).toBeInTheDocument();
            expect(screen.getByText(/Your request to unsubscribe has already been processed./)).toBeInTheDocument();
        });
    });

    it("Renders a simple unsubscribe landing page with a single notification reason", async () => {
        renderInProviders(MOCK_BADGE_TOKEN);

        await waitFor(() => {
            expect(screen.getByText(/Unsubscribe Successful/)).toBeInTheDocument();
            expect(screen.getByText(/You will no longer receive email notifications for/)).toBeInTheDocument();
            expect(screen.getByText(/New badges/)).toBeInTheDocument();
            expect(screen.getByText(/Change your mind?/)).toBeInTheDocument();
            expect(screen.getByRole("button", { name: "Undo" })).toBeInTheDocument();

            // Manage button links to notification preference page for currently logged in user
            const manageButton = screen.getByRole("button", { name: "Manage All Notifications" });
            expect(manageButton).toBeInTheDocument();
            expect(manageButton.attributes.getNamedItem("href")?.value).toBe(
                `${window.location.origin}/profile/preferences`,
            );

            // verify user information being displayed
            const userLink = screen.getByRole("link", { name: MOCK_BADGE_DATA.user.name });
            expect(userLink).toBeInTheDocument();
            expect(userLink.attributes.getNamedItem("href")?.value).toBe(
                encodeURI(`${window.location.origin}/profile/2/Test User`),
            );
            expect(screen.getByText(MOCK_BADGE_DATA.user.email)).toBeInTheDocument();
            const userImage = screen.getByRole("img", { name: `User: "${MOCK_BADGE_DATA.user.name}"` });
            expect(userImage).toBeInTheDocument();
            expect(userImage.attributes.getNamedItem("src")?.value).toBe(MOCK_BADGE_DATA.user.photoUrl);
        });
    });

    it("Renders landing page to unfollow a category", async () => {
        renderInProviders(MOCK_UNFOLLOW_TOKEN);

        await waitFor(() => {
            expect(screen.getByText(/Unfollow Successful/)).toBeInTheDocument();

            const categoryLink = screen.getByRole("link", { name: MOCK_UNFOLLOW_DATA.followedCategory.categoryName });
            const expectedLink = [
                window.location.origin,
                "categories",
                MOCK_UNFOLLOW_DATA.followedCategory.categoryName.replace(/\s/g, "-").toLowerCase(),
            ].join("/");
            expect(categoryLink).toBeInTheDocument();
            expect(categoryLink.attributes.getNamedItem("href")?.value).toBe(encodeURI(expectedLink));

            expect(screen.getByText(/You are no longer following/)).toBeInTheDocument();

            expect(screen.getByText(/Change your mind?/)).toBeInTheDocument();
            expect(screen.getByRole("button", { name: "Undo" })).toBeInTheDocument();

            // Manage button links to notification preference page for currently logged in user
            const manageButton = screen.getByRole("button", { name: "Manage Followed Categories" });
            expect(manageButton).toBeInTheDocument();
            expect(manageButton.attributes.getNamedItem("href")?.value).toBe(
                `${window.location.origin}/profile/followed-content`,
            );
        });
    });

    it("Renders landing page to unsubscribe from email", async () => {
        renderInProviders(MOCK_DIGEST_TOKEN);

        await waitFor(() => {
            expect(screen.getByText(/Unsubscribe Successful/)).toBeInTheDocument();

            expect(screen.getByText(/You will no longer receive the email digest./)).toBeInTheDocument();

            expect(screen.getByText(/Change your mind?/)).toBeInTheDocument();
            expect(screen.getByRole("button", { name: "Undo" })).toBeInTheDocument();
        });
    });

    it("Renders landing page to with multiple reasons for receiving the email", async () => {
        renderInProviders(MOCK_MULTI_TOKEN);

        await waitFor(() => {
            expect(screen.getByText(/Unsubscribe/)).toBeInTheDocument();
            expect(
                screen.getByText(
                    /You are subscribed to the following email notifications, which are related to the notification you received./,
                ),
            ).toBeInTheDocument();
            expect(screen.getByText(/Uncheck the notifications you no longer want to recieve./)).toBeInTheDocument();

            const checkbox1 = screen.getByRole("checkbox", {
                name: "New comments on posts I've participated in",
                checked: true,
            });
            expect(checkbox1).toBeInTheDocument();

            const checkbox2 = screen.getByRole("checkbox", {
                name: `${MOCK_MULTI_DATA.followedCategory.categoryName} | New comments on posts`,
                checked: true,
            });
            expect(checkbox2).toBeInTheDocument();

            expect(screen.getByRole("button", { name: "Save Changes" })).toBeInTheDocument();
            expect(screen.getByText(/Further customize all notification settings on the/)).toBeInTheDocument();

            const preferencePage = screen.getByRole("link", { name: "notification preferences page" });
            expect(preferencePage).toBeInTheDocument();
            expect(preferencePage.attributes.getNamedItem("href")?.value).toBe(
                `${window.location.origin}/profile/preferences`,
            );
        });
    });
});

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
import { IUnsubscribeToken } from "@library/unsubscribe/unsubscribePage.types";
import { useGetUnsubscribe, useSaveUnsubscribe, useUndoUnsubscribe } from "@library/unsubscribe/unsubscribePageHooks";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { act, render, waitFor, screen } from "@testing-library/react";
import { renderHook } from "@testing-library/react-hooks";
import React from "react";
import { MemoryRouter } from "react-router";
import { UnsubscribePageImpl } from "@library/unsubscribe/UnsubscribePage";

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
            preferenceRaw: "Email.Badge",
            preferenceName: "Badge",
            enabled: true,
            label: <p key="Email.Badge">New badges</p>,
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
    },
    preferences: [
        {
            enabled: true,
            label: <p key="Email.ParticipateComment">New comments on posts I&apos;ve participated in</p>,
            preferenceName: "ParticipateComment",
            preferenceRaw: "Email.ParticipateComment",
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
    },
};

mockAdapter.onPost(`/unsubscribe/${MOCK_UNFOLLOW_TOKEN}`).reply(201, MOCK_UNFOLLOW_API_RESULT);

// Landing page to unsubscribe from email digest
const MOCK_DIGEST_TOKEN =
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJBY3Rpdml0eUlEIjoxLCJBY3Rpdml0eVR5cGVzIjpbIkVtYWlsRGlnZXN0Il0sIkFjdGl2aXR5RGF0YSI6W10sIlVzZXJJRCI6MiwiTmFtZSI6IlRlc3QgVXNlciIsIkVtYWlsIjoidGVzdEBlbWFpbC5jb20iLCJQaG90b1VybCI6Imh0dHBzOi8vdXNlci1pbWFnZXMuZ2l0aHVidXNlcmNvbnRlbnQuY29tLzE3NzAwNTYvNzQwOTgxMzMtNmY2MjUxMDAtNGFlMi0xMWVhLThhOWQtOTA4ZDcwMDMwNjQ3LnBuZyJ9.jkM81rOkDhJw6zbo3XJqYaTwFIJaU3DA5PFqcMzeFdQ" as IUnsubscribeToken;

const MOCK_DIGEST_API_RESULT = {
    preferences: [
        {
            preference: "Email.EmailDigest",
            enabled: "0",
        },
    ],
    followCategory: [],
};

const MOCK_DIGEST_DATA = {
    ...TOKEN_RESULT_TEMPLATE,
    ...FETCH_RESULT_TEMPLATE,
    activityTypes: ["EmailDigest"],
    isEmailDigest: true,
    preferences: [
        {
            preferenceRaw: "Email.EmailDigest",
            preferenceName: "EmailDigest",
            enabled: false,
            label: <></>,
        },
    ],
};

mockAdapter.onPost(`/unsubscribe/${MOCK_DIGEST_TOKEN}`).reply(202, MOCK_DIGEST_API_RESULT);

// Wrapper with QueryClientProvider for testing hooks
function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
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
});

describe("UnsubscribePage Rendering", () => {
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
                encodeURI(`${window.location.origin}/profile/preferences/admin`),
            );

            // verify user information being displayed
            const userLink = screen.getByRole("link", { name: MOCK_BADGE_DATA.user.name });
            expect(userLink).toBeInTheDocument();
            expect(userLink.attributes.getNamedItem("href")?.value).toBe(
                encodeURI(`${window.location.origin}/profile/Test User`),
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
                encodeURI(`${window.location.origin}/profile/followed-content/admin`),
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
                encodeURI(`${window.location.origin}/profile/preferences/admin`),
            );
        });
    });
});

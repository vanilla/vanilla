/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { UnsubscribePageImpl } from "@library/unsubscribe/UnsubscribePage";
import { UnsubscribeFixture } from "@library/unsubscribe/__fixtures__/Unsubscribe.Fixture";
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
import { MemoryRouter } from "react-router";

// Wrapper with QueryClientProvider for testing hooks
function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                {children}
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );
    return Wrapper;
}

// Render page with proper provider wrappers
function renderInProviders(token: IUnsubscribeToken) {
    const queryClient = new QueryClient();

    return render(
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <MemoryRouter>
                    <UnsubscribePageImpl token={token} />
                </MemoryRouter>
            </CurrentUserContextProvider>
        </QueryClientProvider>,
    );
}

describe("UnsubscribePage", () => {
    beforeAll(() => {
        const mockAdapter = mockAPI();

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_PROCESSED_TOKEN}`)
            .reply(201, UnsubscribeFixture.MOCK_PROCESSED_API_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_BADGE_TOKEN}`)
            .reply(201, UnsubscribeFixture.MOCK_BADGE_RESUBSCRIBE_RESULT);
        mockAdapter
            .onPost(`/unsubscribe/resubscribe/${UnsubscribeFixture.MOCK_BADGE_TOKEN}`)
            .reply(201, UnsubscribeFixture.MOCK_BADGE_RESUBSCRIBE_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_MULTI_TOKEN}`)
            .reply(201, UnsubscribeFixture.MOCK_MULTI_API_RESULT);
        mockAdapter
            .onPatch(`/unsubscribe/${UnsubscribeFixture.MOCK_MULTI_TOKEN}`)
            .reply(204, UnsubscribeFixture.MOCK_SAVE_API_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_UNFOLLOW_TOKEN}`)
            .reply(201, UnsubscribeFixture.MOCK_UNFOLLOW_API_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_DIGEST_TOKEN}`)
            .reply(202, UnsubscribeFixture.MOCK_DIGEST_API_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_DIGEST_HIDE_CATEGORY_TOKEN}`)
            .reply(202, UnsubscribeFixture.MOCK_DIGEST_HIDE_CATEGORY_API_RESULT);

        mockAdapter
            .onPost(`/unsubscribe/${UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_TOKEN}`)
            .reply(202, UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_API_RESULT);
    });

    describe("useGetUnsubscribe", () => {
        it("Token returns no notifications as they have been processed", async () => {
            const { result } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_PROCESSED_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            await act(async () => {
                await result.current.mutateAsync(UnsubscribeFixture.MOCK_PROCESSED_TOKEN);
            });

            await vi.waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_PROCESSED_DATA);
        });

        it("Token returns reason for notification is new badge", async () => {
            const { result } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_BADGE_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            await act(async () => {
                await result.current.mutateAsync(UnsubscribeFixture.MOCK_BADGE_TOKEN);
            });

            await vi.waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_BADGE_DATA);
        });

        it("Token returns multiple reasons for notification", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_MULTI_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutate(UnsubscribeFixture.MOCK_MULTI_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_MULTI_DATA);
        });

        it("Token returns unfollow category data", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_UNFOLLOW_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutate(UnsubscribeFixture.MOCK_UNFOLLOW_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_UNFOLLOW_DATA);
        });

        it("Token returns email digest unsubscribe data", async () => {
            const { result, waitFor } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_DIGEST_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            act(() => {
                result.current.mutate(UnsubscribeFixture.MOCK_DIGEST_TOKEN);
            });

            await waitFor(() => {
                return result.current.isSuccess;
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_DIGEST_DATA);
        });

        it("Token returns email digest hide category data", async () => {
            const { result } = renderHook(() => useGetUnsubscribe(UnsubscribeFixture.MOCK_DIGEST_HIDE_CATEGORY_TOKEN), {
                wrapper: queryClientWrapper(),
            });

            await act(async () => {
                await result.current.mutateAsync(UnsubscribeFixture.MOCK_DIGEST_HIDE_CATEGORY_TOKEN);
            });

            await vi.waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_DIGEST_HIDE_CATEGORY_DATA);
        });

        it("Token returns email digest hide other content data", async () => {
            const { result } = renderHook(
                () => useGetUnsubscribe(UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_TOKEN),
                {
                    wrapper: queryClientWrapper(),
                },
            );

            await act(async () => {
                await result.current.mutateAsync(UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_TOKEN);
            });

            await vi.waitFor(() => {
                expect(result.current.isSuccess).toBe(true);
            });

            expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_DATA);
        });
    });

    it("Undo unsubscribe", async () => {
        const { result, waitFor } = renderHook(() => useUndoUnsubscribe(UnsubscribeFixture.MOCK_BADGE_TOKEN), {
            wrapper: queryClientWrapper(),
        });

        act(() => {
            result.current.mutate(UnsubscribeFixture.MOCK_BADGE_TOKEN);
        });

        await waitFor(() => {
            return result.current.isSuccess;
        });

        expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_BADGE_RESUBSCRIBE_DATA);
    });

    it("Unsubscribe from only one notification in a list of choices", async () => {
        const postData = {
            ...UnsubscribeFixture.MOCK_MULTI_DATA,
            preferences: [
                {
                    ...UnsubscribeFixture.MOCK_MULTI_DATA.preferences[0],
                    enabled: false,
                },
            ],
        };

        const { result, waitFor } = renderHook(() => useSaveUnsubscribe(UnsubscribeFixture.MOCK_MULTI_TOKEN), {
            wrapper: queryClientWrapper(),
        });

        act(() => {
            result.current.mutate(postData);
        });

        await waitFor(() => {
            return result.current.isSuccess;
        });

        expect(result.current.data).toStrictEqual(UnsubscribeFixture.MOCK_SAVE_DATA);
    });

    it("Create link to notification preferences page for current user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const link = result.current(UnsubscribeFixture.TOKEN_RESULT_TEMPLATE.user);
        await waitFor(() => {
            expect(link).toBe("/profile/preferences");
        });
    });

    it("Create link to followed content page for current user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const link = result.current(UnsubscribeFixture.TOKEN_RESULT_TEMPLATE.user, true);
        await waitFor(() => {
            expect(link).toBe("/profile/followed-content");
        });
    });

    it("Create link to notification preferences page for another user", async () => {
        const { result, waitFor } = renderHook(() => usePreferenceLink(), { wrapper: queryClientWrapper() });
        const tmpUser = {
            ...UnsubscribeFixture.TOKEN_RESULT_TEMPLATE.user,
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
            ...UnsubscribeFixture.TOKEN_RESULT_TEMPLATE.user,
            userID: 100,
        };
        const link = result.current(tmpUser, true);
        await waitFor(() => {
            expect(link).toBe("/profile/followed-content?accountConflict=true");
        });
    });

    describe("UnsubscribePage Rendering", () => {
        it("Renders landing page with notification that unsubscribe has been processed", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_PROCESSED_TOKEN);

            await waitFor(() => {
                expect(screen.getByText(/Request Processed/)).toBeInTheDocument();
                expect(screen.getByText(/Your request to unsubscribe has already been processed./)).toBeInTheDocument();
            });
        });

        it.skip("Renders a simple unsubscribe landing page with a single notification reason", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_BADGE_TOKEN);

            await vi.waitFor(async () => {
                expect(await screen.findByText(/Unsubscribe Successful/)).toBeInTheDocument();
                expect(
                    await screen.findByText(/You will no longer receive email notifications for/),
                ).toBeInTheDocument();
                expect(await screen.findByText(/New badges/)).toBeInTheDocument();
                expect(await screen.findByText(/Change your mind?/)).toBeInTheDocument();
                expect(await screen.findByRole("button", { name: "Undo" })).toBeInTheDocument();
            }, 5000);

            // Manage button links to notification preference page for currently logged in user
            const manageButton = await screen.findByRole("button", { name: "Manage All Notifications" });
            expect(manageButton).toBeInTheDocument();
            expect(manageButton.attributes.getNamedItem("href")?.value).toBe(
                `${window.location.origin}/profile/preferences`,
            );

            // verify user information being displayed
            const userLink = await screen.findByRole("link", { name: UnsubscribeFixture.MOCK_BADGE_DATA.user.name });
            expect(userLink).toBeInTheDocument();
            expect(userLink.attributes.getNamedItem("href")?.value).toBe(
                encodeURI(`${window.location.origin}/profile/2/Test User`),
            );
            expect(await screen.findByText(UnsubscribeFixture.MOCK_BADGE_DATA.user.email)).toBeInTheDocument();
            const userImage = await screen.findByRole("img", {
                name: `User: "${UnsubscribeFixture.MOCK_BADGE_DATA.user.name}"`,
            });
            expect(userImage).toBeInTheDocument();
            expect(userImage.attributes.getNamedItem("src")?.value).toBe(
                UnsubscribeFixture.MOCK_BADGE_DATA.user.photoUrl,
            );
        });

        it("Renders landing page to unfollow a category", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_UNFOLLOW_TOKEN);

            await waitFor(() => {
                expect(screen.getByText(/Unfollow Successful/)).toBeInTheDocument();

                const categoryLink = screen.getByRole("link", {
                    name: UnsubscribeFixture.MOCK_UNFOLLOW_DATA.followedContent.contentName,
                });
                const expectedLink = [
                    window.location.origin,
                    "categories",
                    UnsubscribeFixture.MOCK_UNFOLLOW_DATA.followedContent.contentName.replace(/\s/g, "-").toLowerCase(),
                ].join("/");
                expect(categoryLink).toBeInTheDocument();
                expect(categoryLink.attributes.getNamedItem("href")?.value).toBe(encodeURI(expectedLink));

                expect(screen.getByText(/You are no longer following/)).toBeInTheDocument();

                expect(screen.getByText(/Change your mind?/)).toBeInTheDocument();
                expect(screen.getByRole("button", { name: "Undo" })).toBeInTheDocument();

                // Manage button links to notification preference page for currently logged in user
                const manageButton = screen.getByRole("button", { name: "Manage Followed Content" });
                expect(manageButton).toBeInTheDocument();
                expect(manageButton.attributes.getNamedItem("href")?.value).toBe(
                    `${window.location.origin}/profile/followed-content`,
                );
            });
        });

        it("Renders landing page to unsubscribe from email", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_DIGEST_TOKEN);

            await waitFor(async () => {
                expect(await screen.findByText(/Unsubscribe Successful/)).toBeInTheDocument();

                expect(await screen.findByText(/You will no longer receive the email digest./)).toBeInTheDocument();

                expect(await screen.findByText(/Change your mind?/)).toBeInTheDocument();
                expect(await screen.findByRole("button", { name: "Undo" })).toBeInTheDocument();
            });
        });

        it("Renders landing page to with multiple reasons for receiving the email", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_MULTI_TOKEN);

            await waitFor(() => {
                expect(screen.getByText(/Unsubscribe/)).toBeInTheDocument();
                expect(
                    screen.getByText(
                        /You are subscribed to the following email notifications, which are related to the notification you received./,
                    ),
                ).toBeInTheDocument();
                expect(
                    screen.getByText(/Uncheck the notifications you no longer want to recieve./),
                ).toBeInTheDocument();

                const checkbox1 = screen.getByRole("checkbox", {
                    name: "New comments on posts I've participated in",
                    checked: true,
                });
                expect(checkbox1).toBeInTheDocument();

                const checkbox2 = screen.getByRole("checkbox", {
                    name: `${UnsubscribeFixture.MOCK_MULTI_DATA.followedContent.contentName} | New comments on posts`,
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

        it("Renders landing page to unsubscribe from digest for followed content (e.g. group)", async () => {
            renderInProviders(UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_TOKEN);

            await waitFor(() => {
                expect(screen.getByText(/This content will no longer appear in your email digest/)).toBeInTheDocument();

                const contentNameAsLink = screen.getByRole("link", {
                    name: UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_DATA.followedContent.contentName,
                });

                expect(contentNameAsLink.attributes.getNamedItem("href")?.value).toBe(
                    `${window.location.origin}${UnsubscribeFixture.MOCK_DIGEST_HIDE_OTHER_CONTENT_DATA.followedContent.contentUrl}`,
                );

                expect(screen.getByText(/Change your mind?/)).toBeInTheDocument();
                expect(screen.getByRole("button", { name: "Undo" })).toBeInTheDocument();
            });
        });
    });
});

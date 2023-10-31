/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentProps } from "react";
import { fireEvent, render, screen, act, waitFor } from "@testing-library/react";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { LoadStatus } from "@library/@types/api/core";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { LiveAnnouncer } from "react-aria-live";

const MOCK_PAGING = {
    nextURL: "#",
    prevURL: "#",
    total: 100,
    currentPage: 1,
    limit: 10,
};

jest.setTimeout(100000);

const renderInProvider = (props?: Partial<ComponentProps<typeof DiscussionCommentsAsset>>) => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
            },
        },
    });

    render(
        <LiveAnnouncer>
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                ...UserFixture.createMockUser({ userID: 1 }),
                                countUnreadNotifications: 0,
                                countUnreadConversations: 0,
                            },
                        },
                    },
                }}
            >
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionCommentsAsset
                            categoryID={1}
                            commentsPreload={{ data: LayoutEditorPreviewData.comments(5), paging: MOCK_PAGING }}
                            apiParams={{ discussionID: "fake", limit: 5, page: 1 }}
                            discussion={{
                                ...fakeDiscussions[0],
                                url: "https://vanilla.test/mockPath",
                                name: "Mock Discussion",
                            }}
                            {...props}
                        />
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </TestReduxProvider>
        </LiveAnnouncer>,
    );
};

describe("DiscussionCommentsAsset - Pagination", () => {
    it("Does not render a pager when all comments are displayed", () => {
        renderInProvider({
            commentsPreload: {
                data: LayoutEditorPreviewData.comments(5),
                paging: {
                    ...MOCK_PAGING,
                    total: 3,
                    limit: 5,
                },
            },
        });
        expect(screen.queryByText(/Next/)).not.toBeInTheDocument();
    });
    it("Renders pager when there are more comments than those displayed", () => {
        renderInProvider();
        expect(screen.queryByText(/Next/)).toBeInTheDocument();
    });
    it("Navigator is updated when going to a new comment list page", () => {
        window.history.replaceState = jest.fn();
        renderInProvider();
        act(() => {
            const nextButton = screen.queryByText(/Next/);
            nextButton && fireEvent.click(nextButton);
            expect(window.history.replaceState).toHaveBeenCalled();
        });
    });
});

const renderWithAPI = async (props?: Partial<ComponentProps<typeof DiscussionCommentsAsset>>): Promise<HTMLElement> => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    const { container } = await render(
        <LiveAnnouncer>
            <TestReduxProvider
                state={{
                    users: {
                        current: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                ...UserFixture.createMockUser({ userID: 1 }),
                                countUnreadNotifications: 0,
                                countUnreadConversations: 0,
                            },
                        },
                    },
                }}
            >
                <QueryClientProvider client={queryClient}>
                    <PermissionsFixtures.AllPermissions>
                        <DiscussionCommentsAsset
                            categoryID={1}
                            commentsPreload={{ data: LayoutEditorPreviewData.comments(3), paging: MOCK_PAGING }}
                            apiParams={{ discussionID: "fake", limit: 5, page: 1 }}
                            discussion={{
                                ...fakeDiscussions[0],
                                url: "https://vanilla.test/mockPath",
                                name: "Mock Discussion",
                            }}
                            {...props}
                        />
                    </PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </TestReduxProvider>
        </LiveAnnouncer>,
    );

    return container;
};

describe("DiscussionCommentsAsset - Edit", () => {
    let mockAdapter: MockAdapter;

    beforeAll(() => {
        mockAdapter = mockAPI();
        window.scrollTo = jest.fn();
    });

    it("Editing a comment loads comment in a vanilla editor instance", async () => {
        mockAdapter.onGet(/(.+)/).reply(200, LayoutEditorPreviewData.comments(3));

        let rendered: HTMLElement;

        await act(async () => {
            rendered = await renderWithAPI();
        });

        await waitFor(() => expect(screen.queryAllByRole("button", { expanded: false })[0]).toBeInTheDocument());

        await act(async () => {
            const contextMenu = screen.queryAllByRole("button", { expanded: false })[0];
            contextMenu && fireEvent.click(contextMenu);
        });

        await waitFor(() => expect(screen.queryByText(/Edit/)).toBeInTheDocument());

        await act(async () => {
            const editButton = screen.queryByText(/Edit/);
            editButton && fireEvent.click(editButton);
        });

        await waitFor(() => expect(rendered.querySelectorAll("#vanilla-editor-root").length).toBe(1));
    });

    // FIXME: The context menu selectors need work but I have bigger fish to fry right now
    // it("Editing a two comments loads each comment in a vanilla editor instance", async () => {
    //     mockAdapter.onGet(/(.+)/).reply(200, LayoutEditorPreviewData.comments(3));

    //     let rendered: HTMLElement;

    //     await act(async () => {
    //         rendered = await renderWithAPI();
    //     });

    //     await waitFor(() => expect(screen.queryAllByRole("button", { expanded: false })[0]).toBeInTheDocument());

    //     await act(async () => {
    //         const contextMenu = screen.queryAllByRole("button", { expanded: false })[0];
    //         contextMenu && fireEvent.click(contextMenu);
    //     });

    //     await waitFor(() => expect(screen.queryByText(/Edit/)).toBeInTheDocument());

    //     await act(async () => {
    //         const editButton = screen.queryByText(/Edit/);
    //         editButton && fireEvent.click(editButton);
    //     });

    //     await act(async () => {
    //         const contextMenuTwo = screen.queryAllByRole("button", { expanded: false })[1];
    //         contextMenuTwo && fireEvent.click(contextMenuTwo);
    //     });

    //     await waitFor(() => {
    //         expect(screen.queryByText(/Edit/)).toBeInTheDocument()
    //     });

    //     await act(async () => {
    //         const editButtonTwo = screen.queryByText(/Edit/);
    //         editButtonTwo && fireEvent.click(editButtonTwo);
    //     });

    //     await waitFor(() => expect(rendered.querySelectorAll("#vanilla-editor-root").length).toBe(2));
    // });
});

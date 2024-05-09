/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { fireEvent, render, act, waitFor, within, RenderResult } from "@testing-library/react";
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
import { ICommentEdit } from "@dashboard/@types/api/comment";

const MOCK_DISCUSSION: React.ComponentProps<typeof DiscussionCommentsAsset>["discussion"] = {
    ...fakeDiscussions[0],
    url: "https://vanilla.test/mockPath",
    name: "Mock Discussion",
};

const MOCK_API_PARAMS: React.ComponentProps<typeof DiscussionCommentsAsset>["apiParams"] = {
    discussionID: MOCK_DISCUSSION.discussionID,
    limit: 5,
    page: 1,
};

const MOCK_PAGING: NonNullable<React.ComponentProps<typeof DiscussionCommentsAsset>["comments"]>["paging"] = {
    nextURL: "#",
    prevURL: "#",
    total: 100,
    currentPage: 1,
    limit: 10,
};

beforeEach(() => {
    window.scrollTo = jest.fn();
});

jest.setTimeout(100000);

async function renderInProvider(children: ReactNode) {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
            },
        },
    });

    return render(
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
                    <PermissionsFixtures.AllPermissions>{children}</PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </TestReduxProvider>
        </LiveAnnouncer>,
    );
}

describe("DiscussionCommentsAsset", () => {
    let result: RenderResult;
    describe("Pagination", () => {
        describe("Single page", () => {
            beforeEach(async () => {
                result = await renderInProvider(
                    <DiscussionCommentsAsset
                        comments={{
                            data: LayoutEditorPreviewData.comments(5),
                            paging: {
                                ...MOCK_PAGING,
                                total: 3,
                                limit: 5,
                            },
                        }}
                        apiParams={MOCK_API_PARAMS}
                        discussion={MOCK_DISCUSSION}
                    />,
                );
            });
            it("Does not render a pager when all comments are displayed", async () => {
                expect(result.queryByText(/Next/)).not.toBeInTheDocument();
            });
        });

        describe("Multiple pages", () => {
            beforeEach(async () => {
                result = await renderInProvider(
                    <DiscussionCommentsAsset
                        discussion={MOCK_DISCUSSION}
                        comments={{ data: LayoutEditorPreviewData.comments(5), paging: MOCK_PAGING }}
                        apiParams={MOCK_API_PARAMS}
                    />,
                );
            });

            it("Renders pager when there are more comments than those displayed", async () => {
                expect(await result.findByText(/Next/)).toBeInTheDocument();
            });
            it("Navigator is updated when going to a new comment list page", async () => {
                window.history.replaceState = jest.fn();

                const nextButton = await result.findByText(/Next/);
                await act(async () => {
                    nextButton && fireEvent.click(nextButton);
                });
                expect(window.history.replaceState).toHaveBeenCalled();
            });
        });
    });

    describe("Closed discussion", () => {
        beforeEach(async () => {
            result = await renderInProvider(
                <DiscussionCommentsAsset
                    comments={{ data: LayoutEditorPreviewData.comments(5), paging: MOCK_PAGING }}
                    apiParams={MOCK_API_PARAMS}
                    discussion={{ ...MOCK_DISCUSSION, closed: true }}
                />,
            );
        });

        it("A 'closed' tag is rendered within the heading", async () => {
            const heading = await result.findByRole("heading");
            const closedTag = await within(heading).findByText("Closed");
            expect(closedTag).toBeInTheDocument();
        });
    });
});

async function renderWithAPI(children: ReactNode) {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                retry: false,
            },
        },
    });

    return render(
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
                    <PermissionsFixtures.AllPermissions>{children}</PermissionsFixtures.AllPermissions>
                </QueryClientProvider>
            </TestReduxProvider>
        </LiveAnnouncer>,
    );
}

describe("DiscussionCommentsAsset - Edit", () => {
    let mockAdapter: MockAdapter;
    let result: RenderResult;
    const mockComments = LayoutEditorPreviewData.comments(3);

    beforeEach(async () => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(`/discussions/${MOCK_DISCUSSION.discussionID}`).replyOnce(200, MOCK_DISCUSSION);
        mockAdapter.onGet("/comments").replyOnce(200, mockComments);
        mockAdapter.onGet(`/comments/${mockComments[0].commentID}/edit`).replyOnce<ICommentEdit>(() => {
            return [200, { ...LayoutEditorPreviewData.comments(1)[0], format: "rich2" }];
        });

        result = await renderWithAPI(
            <DiscussionCommentsAsset
                comments={{ data: mockComments, paging: MOCK_PAGING }}
                apiParams={MOCK_API_PARAMS}
                discussion={MOCK_DISCUSSION}
            />,
        );
    });

    it("Editing a comment loads comment in a vanilla editor instance", async () => {
        const contextMenu = result.queryAllByRole("button", { expanded: false })[0];
        expect(contextMenu).toBeInTheDocument();

        act(() => {
            fireEvent.click(contextMenu);
        });

        const editButton = (await within(result.container).findByText(/Edit/)).closest("button");
        expect(editButton).toBeInTheDocument();

        await act(async () => {
            fireEvent.click(editButton!);
        });

        await waitFor(async () => {
            expect(result.container.querySelectorAll("#vanilla-editor-root").length).toBe(1);
        });
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

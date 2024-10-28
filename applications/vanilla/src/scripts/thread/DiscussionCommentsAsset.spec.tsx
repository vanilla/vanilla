/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { fireEvent, render, act, waitFor, within, RenderResult, screen } from "@testing-library/react";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { mockAPI } from "@library/__tests__/utility";
import MockAdapter from "axios-mock-adapter";
import { LiveAnnouncer } from "react-aria-live";
import { ICommentEdit } from "@dashboard/@types/api/comment";
import { vitest } from "vitest";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import DiscussionCommentsAssetFlat from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.flat";
import { MemoryRouter } from "react-router";

const MOCK_DISCUSSION: React.ComponentProps<typeof DiscussionCommentsAsset>["discussion"] = {
    ...DiscussionFixture[0],
    url: "https://vanilla.test/mockPath",
    name: "Mock Discussion",
};

const MOCK_API_PARAMS: React.ComponentProps<typeof DiscussionCommentsAsset>["apiParams"] = {
    discussionID: MOCK_DISCUSSION.discussionID,
    limit: 5,
    page: 1,
};

const MOCK_PAGING: NonNullable<React.ComponentProps<typeof DiscussionCommentsAssetFlat>["comments"]>["paging"] = {
    nextURL: "#",
    prevURL: "#",
    total: 100,
    currentPage: 1,
    limit: 10,
};

beforeEach(() => {
    (window as any).scrollTo = vitest.fn();
});

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
        <TestReduxProvider>
            <LiveAnnouncer>
                <MemoryRouter>
                    <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                        <QueryClientProvider client={queryClient}>
                            <PermissionsFixtures.AllPermissions>{children}</PermissionsFixtures.AllPermissions>
                        </QueryClientProvider>
                    </CurrentUserContextProvider>
                </MemoryRouter>
            </LiveAnnouncer>
        </TestReduxProvider>,
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
                        threadStyle="flat"
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
                        threadStyle="flat"
                    />,
                );
            });

            it("Renders pager when there are more comments than those displayed", async () => {
                expect(await result.findByText(/Next/)).toBeInTheDocument();
            });
            it("Navigator is updated when going to a new comment list page", async () => {
                window.history.pushState = vitest.fn();

                const nextButton = await result.findByText(/Next/);
                await act(async () => {
                    nextButton && fireEvent.click(nextButton);
                });
                expect(window.history.pushState).toHaveBeenCalled();
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
                    threadStyle="flat"
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
        <TestReduxProvider>
            <LiveAnnouncer>
                <MemoryRouter>
                    <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                        <QueryClientProvider client={queryClient}>
                            <PermissionsFixtures.AllPermissions>{children}</PermissionsFixtures.AllPermissions>
                        </QueryClientProvider>
                    </CurrentUserContextProvider>
                </MemoryRouter>
            </LiveAnnouncer>
        </TestReduxProvider>,
    );
}

describe("DiscussionCommentsAsset - Edit", () => {
    let mockAdapter: MockAdapter;
    let result: RenderResult;
    const mockComments = LayoutEditorPreviewData.comments(3);

    beforeEach(async () => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(`/discussions/${MOCK_DISCUSSION.discussionID}`).reply(200, MOCK_DISCUSSION);
        mockAdapter.onGet("/comments").reply(200, mockComments);
        mockAdapter.onGet(`/comments/${mockComments[0].commentID}/edit`).reply<ICommentEdit>(() => {
            return [200, { ...LayoutEditorPreviewData.comments(1)[0], format: "rich2" }];
        });
        mockAdapter.onGet(/comments\/.*\/reactions/).reply(200, {});

        result = await renderWithAPI(
            <DiscussionCommentsAsset
                comments={{ data: mockComments, paging: MOCK_PAGING }}
                apiParams={MOCK_API_PARAMS}
                discussion={MOCK_DISCUSSION}
                threadStyle="flat"
                hasComments={true}
            />,
        );
    });

    it("Editing a comment loads comment in a vanilla editor instance", async () => {
        const contextMenu = screen.queryAllByRole("button", { expanded: false, name: "Comment Options" })[0];
        expect(contextMenu).toBeInTheDocument();
        fireEvent.click(contextMenu);

        const editButton = await screen.findByText(/Edit/);
        expect(editButton).toBeInTheDocument();
        fireEvent.click(editButton!);

        await vitest.dynamicImportSettled();
        vi.waitFor(async () => {
            expect(await screen.findByTestId("vanilla-editor")).toBeInTheDocument();
        }, 5000);
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

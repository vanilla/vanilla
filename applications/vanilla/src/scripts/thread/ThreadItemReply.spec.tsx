/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen, act } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { mockAPI } from "@library/__tests__/utility";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { DRAFT_CONTENT_KEY, DRAFT_PARENT_ID_KEY, ThreadItemReply } from "@vanilla/addon-vanilla/thread/ThreadItemReply";
import { EMPTY_DRAFT } from "@vanilla/addon-vanilla/thread/DiscussionCommentEditorAsset";
import { CommentThreadContext, ICommentThreadContext } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
        },
    },
});

const renderInProvider = async (
    props?: Partial<React.ComponentProps<typeof ThreadItemReply>>,
    contextValues?: Partial<ICommentThreadContext>,
) => {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse();
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                <QueryClientProvider client={queryClient}>
                    <CommentThreadContext.Provider
                        value={{
                            discussion: DiscussionFixture.mockDiscussion,
                            threadDepthLimit: 2,
                            threadStructure,
                            commentsByID,
                            getComment: () => undefined,
                            addToThread: () => Promise.resolve(),
                            collapseThreadAtPath: () => Promise.resolve(),
                            updateComment: () => undefined,
                            lastChildRefsByID: {},
                            addLastChildRefID: () => undefined,
                            currentReplyFormPath: "1.2",
                            showReplyForm: () => undefined,
                            switchReplyForm: () => undefined,
                            addReplyToThread: () => undefined,
                            removeReplyFromThread: () => undefined,
                            draft: undefined,
                            ...contextValues,
                        }}
                    >
                        <ThreadItemReply
                            threadItem={{
                                type: "reply",
                                parentCommentID: 1,
                                depth: 2,
                                replyID: "1-1",
                                draftID: 1,
                                path: "1.2",
                                replyingTo: "Bob",
                            }}
                            {...props}
                        />
                    </CommentThreadContext.Provider>
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
    await vi.dynamicImportSettled();
};

let mockAdapter;

describe("ThreadItemReply", () => {
    beforeEach(() => {
        mockAdapter = mockAPI();
        mockAdapter.onPost(/drafts.*/).reply(200, {
            draftID: 1,
            attributes: {
                body: "",
                format: "rich",
            },
            dateUpdated: "1990-08-20T00:00:00Z",
        });
        mockAdapter.onPatch(/drafts.*/).reply(200, {
            draftID: 1,
            attributes: {
                body: "",
                format: "rich",
            },
            dateUpdated: "1990-08-20T00:00:00Z",
        });
        mockAdapter.onPost(/comments.*/).reply(200, {});
    });

    afterEach(() => {
        localStorage.clear();
    });

    it("Renders reply comment box", async () => {
        await renderInProvider();
        expect(screen.getByText("Replying to Bob")).toBeInTheDocument();
        expect(screen.getByText("Post Comment Reply")).toBeInTheDocument();
        expect(screen.getByText("Discard Reply")).toBeInTheDocument();
    });
    it("Local storage cache is set up", async () => {
        await renderInProvider();

        expect(localStorage.length).toBe(2);
        expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_CONTENT_KEY}-10`) ?? "")).toBe(
            JSON.stringify(EMPTY_DRAFT),
        );
        expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_PARENT_ID_KEY}-10`) ?? "")).toBe(1);
    });
    it("Can load draft from local store", async () => {
        localStorage.setItem(
            `vanilla//${DRAFT_CONTENT_KEY}-10`,
            JSON.stringify([{ type: "p", children: [{ text: "Test stored value" }] }]),
        );

        await renderInProvider();

        await waitFor(() => {
            expect(screen.getByText("Test stored value")).toBeInTheDocument();
        });

        expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_CONTENT_KEY}-10`) ?? "")).not.toBe(
            JSON.stringify(EMPTY_DRAFT),
        );
    });
    it("Can load draft from server", async () => {
        await renderInProvider(
            {},
            {
                draft: {
                    attributes: {
                        body: JSON.stringify([{ type: "p", children: [{ text: "Test server stored value" }] }]),
                        format: "rich2",
                    },
                    body: JSON.stringify([{ type: "p", children: [{ text: "Test server stored value" }] }]),
                    draftID: 1,
                    dateUpdated: "1990-08-20T00:00:00Z",
                    format: "rich2",
                } as any,
            },
        );

        await waitFor(() => {
            expect(screen.getByText("Test server stored value")).toBeInTheDocument();
        });

        expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_CONTENT_KEY}-10`) ?? "")).toBe(
            JSON.stringify([{ type: "p", children: [{ text: "Test server stored value" }] }]),
        );
    });
    it("Clears cache state after publish", async () => {
        localStorage.setItem(
            `vanilla//${DRAFT_CONTENT_KEY}-10`,
            JSON.stringify([{ type: "p", children: [{ text: "Test stored value" }] }]),
        );

        await renderInProvider();

        await waitFor(() => {
            expect(screen.getByText("Test stored value")).toBeInTheDocument();
        });

        expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_CONTENT_KEY}-10`) ?? "")).not.toBe(
            JSON.stringify(EMPTY_DRAFT),
        );

        const postButton = screen.getByText("Post Comment Reply");
        await act(async () => {
            fireEvent.click(postButton);
        });

        await waitFor(() => {
            expect(JSON.parse(localStorage.getItem(`vanilla//${DRAFT_CONTENT_KEY}-10`) ?? "")).toBe(
                JSON.stringify(EMPTY_DRAFT),
            );
        });
    });
});

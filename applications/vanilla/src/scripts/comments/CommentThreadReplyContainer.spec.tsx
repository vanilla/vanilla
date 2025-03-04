/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { mockAPI } from "@library/__tests__/utility";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { NestedCommentContext, INestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import { IThreadItemReply } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { CommentThreadReplyContainer } from "@vanilla/addon-vanilla/comments/CommentThreadReplyContainer";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
        },
    },
});

const renderInProvider = async (
    props?: Partial<React.ComponentProps<typeof CommentThreadReplyContainer>>,
    contextValues?: Partial<INestedCommentContext>,
) => {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse();
    render(
        <TestReduxProvider>
            <CurrentUserContextProvider currentUser={UserFixture.createMockUser({ userID: 1 })}>
                <QueryClientProvider client={queryClient}>
                    <NestedCommentContext.Provider
                        value={{
                            threadDepthLimit: 2,
                            threadStructure,
                            commentsByID,
                            getComment: () => undefined,
                            addToThread: () => Promise.resolve(),
                            collapseThreadAtPath: () => Promise.resolve(),
                            updateComment: () => undefined,
                            updateCommentList: () => undefined,
                            selectableCommentIDs: [],
                            lastChildRefsByID: {},
                            addLastChildRefID: () => undefined,
                            currentReplyFormRef: {
                                current: {
                                    depth: 5,
                                    parentCommentID: 1,
                                    path: "1.2",
                                    replyID: "reply-1",
                                    replyingTo: "admin",
                                    type: "reply",
                                },
                            },
                            showReplyForm: () => undefined,
                            switchReplyForm: () => undefined,
                            addReplyToThread: () => undefined,
                            removeReplyFromThread: () => undefined,
                            constructReplyFromComment: () => ({} as IThreadItemReply),
                            ...contextValues,
                        }}
                    >
                        <CommentThreadReplyContainer
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
                    </NestedCommentContext.Provider>
                </QueryClientProvider>
            </CurrentUserContextProvider>
        </TestReduxProvider>,
    );
    await vi.dynamicImportSettled();
};

let mockAdapter;

describe("CommentThreadReply", () => {
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
});

/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PropsWithChildren } from "react";
import { act } from "@testing-library/react";
import {
    CommentThreadProvider,
    CommentThreadProviderProps,
    useCommentThread,
} from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import { renderHook } from "@testing-library/react-hooks";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { isThreadComment } from "@vanilla/addon-vanilla/thread/threadUtils";
import { IThreadItem, IThreadItemComment } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

function TestWrapper(props: PropsWithChildren<CommentThreadProviderProps>) {
    const { children, ...rest } = props;
    return (
        <QueryClientProvider client={queryClient}>
            <QueryClientProvider client={queryClient}>
                <CommentThreadProvider {...rest}>{children}</CommentThreadProvider>
            </QueryClientProvider>
        </QueryClientProvider>
    );
}

describe("CommentThreadContext", () => {
    let mockAdapter: MockAdapter;
    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    it("Set initial state", () => {
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 3,
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Thread is the correct size
        expect(result.current.threadStructure.length).toEqual(mockThreadResponse.threadStructure.length);
        // Comments are loaded
        expect(result.current.commentsByID).toEqual(mockThreadResponse.commentsByID);
    });

    it("Adds hole meta", () => {
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 3,
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Path is only added to holes
        expect(result.current.threadStructure[0].hasOwnProperty("path")).toBe(true);
        expect(result.current.threadStructure[1].hasOwnProperty("path")).toBe(true);
        expect(result.current.threadStructure[1]?.["children"]?.[2].hasOwnProperty("path")).toBe(true);
    });

    it("Fills holes", async () => {
        // Create a thread with 2 root comment, each with 2 children and a hole
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 2,
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Thread is the correct initial size
        const firstComment = result.current.threadStructure[0];
        if (isThreadComment(firstComment)) {
            expect(firstComment?.children?.length).toBe(3);
            expect(firstComment?.children?.[0].type).toBe("comment");
            expect(firstComment?.children?.[1].type).toBe("comment");
            expect(firstComment?.children?.[2].type).toBe("hole");
        }

        // Get the first comments ID (We want to fill the hole in this comments children)
        const parentID = mockThreadResponse.threadStructure[0]?.["commentID"];

        // Listen for the request to fill the hole with a parentID
        mockAdapter.onGet(/comments\/thread/).reply(
            200,
            CommentFixture.createMockThreadStructureResponse({
                maxDepth: 2,
                minCommentsPerDepth: 2,
                includeHoles: true,
                randomizeCommentContent: false,
                parentCommentID: parentID,
            }),
        );

        // Fill the hole
        await act(async () => {
            result.current.addToThread("/comments/thread", parentID);
        });

        // Thread is the correct updated size
        const updatedFirstComment = result.current.threadStructure[0];
        if (isThreadComment(updatedFirstComment)) {
            expect(updatedFirstComment?.children?.length).toBe(4);
            expect(updatedFirstComment?.children?.[0].type).toBe("comment");
            expect(updatedFirstComment?.children?.[1].type).toBe("comment");
            expect(updatedFirstComment?.children?.[2].type).toBe("comment");
            expect(updatedFirstComment?.children?.[3].type).toBe("comment");
        }
    });

    it("Collapses thread", async () => {
        expect.assertions(6);
        // Create a thread with 2 root comment, each with 2 children
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 4,
            minCommentsPerDepth: 2,
            collapseChildDepth: 4,
            includeHoles: false,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Get the first comment path
        const rootPath = result.current.threadStructure[0]?.["path"];
        // Get nested comment path
        const nestedPath = result.current.threadStructure[1]?.["children"]?.[0]?.["path"];

        // Assert root thread children
        if (isThreadComment(result.current.threadStructure[0])) {
            expect(result.current.threadStructure[0]?.children?.length).toBe(2);
        }

        // Assert nested thread children
        if (isThreadComment(result.current.threadStructure[1])) {
            expect(result.current.threadStructure[1]?.children?.length).toBe(2);
        }

        // Collapse the threads
        await act(async () => {
            await result.current.collapseThreadAtPath(rootPath);
            await result.current.collapseThreadAtPath(nestedPath);
        });

        // Assert root thread children are replaced with a hole
        if (isThreadComment(result.current.threadStructure[0])) {
            expect(result.current.threadStructure[0]?.children?.length).toBe(1);
            expect(result.current.threadStructure[0]?.children?.[0]?.type).toBe("hole");
        }

        // Assert nested thread children are replaced with a hole
        if (isThreadComment(result.current.threadStructure[1])) {
            expect(result.current.threadStructure[1]?.children?.[0]?.["children"].length).toBe(1);
            expect(result.current.threadStructure[1]?.children?.[0]?.["children"]?.[0]?.type).toBe("hole");
        }
    });

    it("Adds a reply form", async () => {
        // Create a thread with 2 root comment, each with 2 children
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 2,
            minCommentsPerDepth: 2,
            includeHoles: false,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Get the first comment path
        const firstComment = result.current.threadStructure[0] as IThreadItemComment;

        act(() => {
            result.current.showReplyForm(firstComment);
        });

        expect(result.current.currentReplyFormRef?.current?.path).toBe(firstComment.path);
        const firstThreadStructureComment = result.current.threadStructure?.[0] as IThreadItemComment;
        expect(firstThreadStructureComment?.children?.[0].type).toBe("reply");
    });

    it("Switches reply form to a new ID", async () => {
        // Create a thread with 2 root comment, each with 2 children
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 2,
            minCommentsPerDepth: 2,
            includeHoles: false,
            randomizeCommentContent: false,
        });

        const { result } = renderHook(() => useCommentThread(), {
            wrapper: TestWrapper,
            initialProps: {
                threadStructure: mockThreadResponse.threadStructure,
                commentsByID: mockThreadResponse.commentsByID,
                discussion: DiscussionFixture.mockDiscussion,
            },
        });

        // Get the first comment path
        const firstComment = result.current.threadStructure[0] as IThreadItemComment;

        act(() => {
            result.current.showReplyForm(firstComment);
        });

        expect(result.current.currentReplyFormRef?.current?.path).toBe(firstComment.path);
        const firstThreadStructureComment = result.current.threadStructure?.[0] as IThreadItemComment;
        expect(firstThreadStructureComment?.children?.[0].type).toBe("reply");

        // Get the first comment path
        const secondComment = result.current.threadStructure[1] as IThreadItemComment;

        act(() => {
            result.current.switchReplyForm(secondComment);
        });

        expect(result.current.currentReplyFormRef?.current?.path).not.toBe(firstComment.path);
        expect(result.current.currentReplyFormRef?.current?.path).toBe(secondComment.path);
        expect(firstThreadStructureComment?.children?.[0].type).not.toBe("reply");
        const secondThreadStructureComment = result.current.threadStructure?.[1] as IThreadItemComment;
        expect(firstThreadStructureComment?.children?.[0].type).not.toBe("reply");
        expect(secondThreadStructureComment?.children?.[0].type).toBe("reply");
    });
});

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
        expect(result.current.threadStructure[0].hasOwnProperty("path")).toBe(false);
        expect(result.current.threadStructure[1].hasOwnProperty("path")).toBe(false);
        expect(result.current.threadStructure[1]?.["children"]?.[2].hasOwnProperty("path")).toBe(true);
    });

    it("Fills holes", async () => {
        // Create a thread with 2 root comment, each with 2 children and a hole
        const mockThreadResponse = CommentFixture.createMockThreadStructureResponse({
            maxDepth: 1,
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
            result.current.updateThread("/comments/thread", parentID);
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
});

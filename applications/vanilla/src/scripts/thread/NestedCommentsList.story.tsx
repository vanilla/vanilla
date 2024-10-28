/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { NestedCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";

export default {
    title: "Threaded Comments/CommentsList",
};

const queryClient = new QueryClient();

function WrappedList(props: { threadStructure: IThreadItem[]; commentsByID: Record<IComment["commentID"], IComment> }) {
    return (
        <QueryClientProvider client={queryClient}>
            <NestedCommentsList
                threadStructure={props.threadStructure}
                commentsByID={props.commentsByID}
                discussion={DiscussionFixture.mockDiscussion}
            />
        </QueryClientProvider>
    );
}

export function SingleLevelList() {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse({
        maxDepth: 0,
        minCommentsPerDepth: 5,
        randomizeCommentContent: false,
    });
    return <WrappedList threadStructure={threadStructure} commentsByID={commentsByID} />;
}

export function NestedList() {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse({
        maxDepth: 3,
        includeHoles: true,
        randomizeCommentContent: false,
    });
    return <WrappedList threadStructure={threadStructure} commentsByID={commentsByID} />;
}

export function DeeplyNestedList() {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse({
        maxDepth: 4,
        includeHoles: true,
        randomizeCommentContent: false,
    });
    return <WrappedList threadStructure={threadStructure} commentsByID={commentsByID} />;
}

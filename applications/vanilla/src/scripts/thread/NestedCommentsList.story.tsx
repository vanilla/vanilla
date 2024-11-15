/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
    CommentDraftParentIDAndPath,
    IThreadItem,
    IThreadItemComment,
} from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";
import { NestedCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";
import { CommentThreadProvider } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { useLocalStorage } from "@vanilla/react-utils";
import { ToastProvider } from "@library/features/toaster/ToastContext";
import { IDraftProps } from "@vanilla/addon-vanilla/thread/components/NewCommentEditor";
import { STORY_DATE_STARTS } from "@library/storybook/storyData";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { useEffect } from "react";
import {
    DRAFT_CONTENT_KEY,
    DRAFT_PARENT_ID_AND_PATH_KEY,
} from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";

export default {
    title: "Threaded Comments/CommentsList",
};

const queryClient = new QueryClient();

function WrappedList(props: {
    threadStructure: IThreadItem[];
    commentsByID: Record<IComment["commentID"], IComment>;
    draft?: IDraftProps;
    commentDraftParentIDAndPath?: CommentDraftParentIDAndPath;
    forceShowDraftToast?: boolean;
}) {
    const [cacheDraftParentIDAndPath, setCacheDraftParentIDAndPath] = useLocalStorage<CommentDraftParentIDAndPath>(
        `${DRAFT_PARENT_ID_AND_PATH_KEY}-${DiscussionFixture.mockDiscussion.discussionID}`,
        props.commentDraftParentIDAndPath ?? null,
    );

    const discussion = {
        ...DiscussionFixture.mockDiscussion,
        discussionID: !props.commentDraftParentIDAndPath ? 11 : DiscussionFixture.mockDiscussion.discussionID,
    };

    useEffect(() => {
        setCacheDraftParentIDAndPath(props.commentDraftParentIDAndPath ?? null);
    }, [discussion.discussionID, props.commentDraftParentIDAndPath]);

    return (
        <QueryClientProvider client={queryClient}>
            <ToastProvider>
                <CommentThreadProvider
                    discussion={discussion}
                    threadStructure={props.threadStructure}
                    commentsByID={props.commentsByID}
                    draft={props.draft}
                >
                    <NestedCommentsList discussion={discussion} forceShowDraftToast={props.forceShowDraftToast} />
                </CommentThreadProvider>
            </ToastProvider>
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

export function WithDraftAvailableToast() {
    const { threadStructure, commentsByID } = CommentFixture.createMockThreadStructureResponse({
        maxDepth: 1,
        includeHoles: true,
        randomizeCommentContent: false,
    });

    // keep it static
    const shortenedThreadStructure = threadStructure[0] as IThreadItemComment;
    commentsByID["1"] = { ...commentsByID[shortenedThreadStructure.commentID], commentID: 1 };
    shortenedThreadStructure.commentID = 1;
    shortenedThreadStructure.path = "1";

    useLocalStorage(
        `${DRAFT_CONTENT_KEY}-${DiscussionFixture.mockDiscussion.discussionID}`,
        JSON.stringify([
            {
                type: "p",
                children: [{ text: "Hello my friend." }],
            },
        ]),
    );

    return (
        <>
            <StoryHeading depth={1}>
                Click on -Keep Editing- from the toast to open the draft, discarding the draft will bring back the toast
            </StoryHeading>
            <WrappedList
                threadStructure={[shortenedThreadStructure]}
                commentsByID={commentsByID}
                draft={{
                    draftID: "someID",
                    body: JSON.stringify([
                        {
                            type: "p",
                            children: [{ text: "Hello my friend." }],
                        },
                    ]),
                    format: "rich",
                    dateUpdated: STORY_DATE_STARTS,
                }}
                commentDraftParentIDAndPath={{
                    parentCommentID: shortenedThreadStructure.commentID,
                    path: shortenedThreadStructure.path,
                }}
                forceShowDraftToast
            />
        </>
    );
}

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import React from "react";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { getMeta } from "@library/utility/appUtils";
import { IThreadItemComment } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { CommentListSortOption } from "@dashboard/@types/api/comment";

interface IProps extends Omit<React.ComponentProps<typeof DiscussionCommentsAsset>, "comments" | "discussion"> {}

const discussion = LayoutEditorPreviewData.discussion();

const comments = LayoutEditorPreviewData.comments(5);
const commentsThreadFixture = CommentFixture.createMockThreadStructureResponse({
    maxDepth: 3,
    minCommentsPerDepth: 1,
    includeHoles: true,
    randomizeCommentContent: false,
});
const threadItem = commentsThreadFixture.threadStructure[0] as IThreadItemComment;
threadItem.children?.splice(1, 1);
((threadItem.children || [])[0] as IThreadItemComment).children?.splice(1, 1);
const commentsThread = {
    ...commentsThreadFixture,
    threadStructure: [threadItem],
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

export function DiscussionCommentsAssetPreview(props: IProps) {
    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <DiscussionCommentsAsset
                    {...props}
                    threadStyle={getMeta("threadStyle", "flat")}
                    comments={{
                        data: comments,
                        paging: LayoutEditorPreviewData.paging(props.apiParams?.limit ?? 30),
                    }}
                    commentsThread={{
                        data: commentsThread,
                        paging: LayoutEditorPreviewData.paging(props.apiParams?.limit ?? 30),
                    }}
                    apiParams={{
                        parentRecordType: "discussion",
                        parentRecordID: discussion.discussionID,
                        limit: 30,
                        page: 1,
                    }}
                    discussion={discussion}
                    defaultSort={props.apiParams.sort ?? CommentListSortOption.OLDEST}
                    isPreview
                />
            </QueryClientProvider>
        </Widget>
    );
}

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { Widget } from "@library/layout/Widget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import DiscussionCommentsAsset from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset";
import React, { useMemo } from "react";
import { CommentFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Comment.Fixture";
import { CommentListSortOption } from "@dashboard/@types/api/comment";
import toInteger from "lodash-es/toInteger";

interface IProps extends Omit<React.ComponentProps<typeof DiscussionCommentsAsset>, "comments" | "discussion"> {}

const discussion = LayoutEditorPreviewData.discussion();

const comments = LayoutEditorPreviewData.comments(5);

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
            cacheTime: 0,
        },
    },
});

export function DiscussionCommentsAssetPreview(props: IProps) {
    const { maxDepth, collapseChildDepth } = props.apiParams;
    const commentsThread = useMemo(() => {
        const commentsThreadFixture = CommentFixture.createMockThreadStructureResponse({
            maxDepth: toInteger(maxDepth ?? 5),
            collapseChildDepth: toInteger(collapseChildDepth ?? 3),
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });
        return commentsThreadFixture;
    }, [maxDepth, collapseChildDepth]);

    const key = `${maxDepth}-${collapseChildDepth}`;

    return (
        <Widget>
            <QueryClientProvider client={queryClient}>
                <DiscussionCommentsAsset
                    key={key}
                    {...(props as any)}
                    threadStyle={maxDepth == 1 ? "flat" : "nested"}
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
                        discussionID: discussion.discussionID,
                        expand: key, // Used to break the cache on this.
                    }}
                    discussion={discussion}
                    defaultSort={props.apiParams?.sort ?? CommentListSortOption.OLDEST}
                    isPreview
                />
            </QueryClientProvider>
        </Widget>
    );
}

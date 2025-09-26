/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import CommentThreadAsset from "@vanilla/addon-vanilla/comments/CommentThreadAsset";
import React, { useMemo } from "react";
import { CommentFixture } from "@vanilla/addon-vanilla/comments/__fixtures__/Comment.Fixture";
import { CommentThreadSortOption } from "@dashboard/@types/api/comment";
import toInteger from "lodash-es/toInteger";
import set from "lodash-es/set";
import WidgetPreviewNoPointerEventsWrapper from "@library/layout/WidgetPreviewNoPointerEventsWrapper";

interface IProps extends Omit<React.ComponentProps<typeof CommentThreadAsset>, "comments" | "discussion"> {}

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

export function CommentThreadAssetPreview(props: IProps) {
    const { maxDepth, collapseChildDepth } = props.apiParams;
    const discussion = LayoutEditorPreviewData.discussion();
    const authorBadges = useMemo(() => {
        if (!props.authorBadges == undefined) {
            return undefined;
        }

        return {
            display: props.authorBadges?.display,
            limit: props.authorBadges?.limit,
        };
    }, [props.authorBadges?.display, props.authorBadges?.limit]);

    // For the preview it's very important that we don't recreate this on every single render or the whole thread has re-render potentially multiple times per second.
    const commentsThread = useMemo(() => {
        const commentsThread = CommentFixture.createMockThreadStructureResponse({
            maxDepth: toInteger(maxDepth ?? 5),
            collapseChildDepth: toInteger(collapseChildDepth ?? 3),
            minCommentsPerDepth: 2,
            includeHoles: true,
            randomizeCommentContent: false,
        });

        return commentsThread;
    }, [maxDepth, collapseChildDepth, LayoutEditorPreviewData.externallyRegisteredData?.externalData]);

    const comments = Object.values(commentsThread.commentsByID);

    const key = `${maxDepth}-${collapseChildDepth}`;

    return (
        <LayoutWidget>
            <QueryClientProvider client={queryClient}>
                <WidgetPreviewNoPointerEventsWrapper>
                    <CommentThreadAsset
                        key={key}
                        {...(props as any)}
                        authorBadges={authorBadges}
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
                        defaultSort={props.apiParams?.sort ?? CommentThreadSortOption.OLDEST}
                        isPreview
                    />
                </WidgetPreviewNoPointerEventsWrapper>
            </QueryClientProvider>
        </LayoutWidget>
    );
}

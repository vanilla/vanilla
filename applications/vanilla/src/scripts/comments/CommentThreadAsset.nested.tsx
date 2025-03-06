/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IThreadResponse } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { useCommentThreadQuery } from "@vanilla/addon-vanilla/comments/Comments.hooks";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { PartialCommentsList } from "@vanilla/addon-vanilla/comments/NestedCommentsList";
import isEqual from "lodash-es/isEqual";
import React, { memo, useState } from "react";
import { NestedCommentContextProvider } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { CommentsBulkActionsProvider } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { CommentThreadSortOption } from "@dashboard/@types/api/comment";
import { CommentThreadAssetCommon } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.common";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { useCommentThreadPager } from "@vanilla/addon-vanilla/comments/CommentThread.hooks";
import type { CommentActionsComponentType } from "@vanilla/addon-vanilla/comments/CommentActionsComponentType";

interface IProps extends React.ComponentProps<typeof CommentThreadAssetCommon> {
    commentsThread?: IWithPaging<IThreadResponse>;
    apiParams: CommentsApi.IndexThreadParams;
    isPreview?: boolean;
    CommentActionsComponent?: CommentActionsComponentType;
}

export function CommentThreadAssetNested(props: IProps) {
    const { showOPTag, authorBadges } = props;
    const commentParent = useCommentThreadParentContext();

    // we'll update this inside the component when we do pagination and sorting
    const [apiParams, setApiParams] = useState<CommentsApi.IndexThreadParams>(props.apiParams);

    const threadQuery = useCommentThreadQuery(
        apiParams,
        isEqual(apiParams, props.apiParams) ? props.commentsThread : undefined,
    );
    const { threadStructure, commentsByID } = threadQuery.data?.data ?? {};
    const isLoading = threadQuery.isLoading;

    // bulk actions purposes
    const [selectAllCommentsCheckbox, setSelectAllCommentsCheckbox] = useState<React.ReactNode>();

    const { topPager, bottomPager } = useCommentThreadPager(
        threadQuery.data,
        commentParent.url,
        props.defaultSort ?? CommentThreadSortOption.NEWEST,
        apiParams.sort,
        apiParams.page,
        (sort) => {
            setApiParams((prev) => ({ ...prev, sort }));
        },
        (page) => {
            setApiParams((prev) => ({ ...prev, page }));
            commentParent.setCurrentPage(page);
        },
    );

    const hasComments = Object.keys(commentsByID ?? {}).length > 0;

    return (
        <CommentThreadAssetCommon
            {...props}
            hasComments={hasComments}
            topPager={topPager}
            bottomPager={bottomPager}
            selectAllCommentsCheckbox={selectAllCommentsCheckbox}
        >
            {isLoading && (
                <div style={{ display: "flex", width: "100%", padding: 16 }}>
                    <ButtonLoader className={css({ transform: "scale(2)" })} />
                </div>
            )}
            {!isLoading && threadStructure && commentsByID && (
                <LoadedThread
                    apiParams={apiParams}
                    threadStructure={threadStructure}
                    commentsByID={commentsByID}
                    showOPTag={showOPTag}
                    displayBadges={authorBadges?.display}
                    badgeLimit={authorBadges?.limit}
                    CommentActionsComponent={props.CommentActionsComponent}
                    setSelectAllCommentsCheckbox={setSelectAllCommentsCheckbox}
                    isPreview={props.isPreview}
                />
            )}
        </CommentThreadAssetCommon>
    );
}

/**
 * Pulled out into a memo for performance optimization in layout editor previews.
 *
 * Be very careful not to add object/array props with unstable identities.
 */
const LoadedThread = memo(function LoadedThread(props: {
    apiParams: IProps["apiParams"];
    threadStructure: IThreadResponse["threadStructure"];
    commentsByID: IThreadResponse["commentsByID"];
    showOPTag: IProps["showOPTag"];
    displayBadges?: boolean;
    badgeLimit?: number;
    CommentActionsComponent?: IProps["CommentActionsComponent"];
    isPreview?: boolean;
    setSelectAllCommentsCheckbox: any;
}) {
    return (
        <NestedCommentContextProvider
            threadDepthLimit={(props.apiParams.maxDepth ?? 5) - 1}
            threadStructure={props.threadStructure}
            commentsByID={props.commentsByID}
            commentApiParams={props.apiParams}
            showOPTag={props.showOPTag}
            authorBadges={{
                display: props.displayBadges ?? true,
                limit: props.badgeLimit ?? 3,
            }}
            CommentActionsComponent={props.CommentActionsComponent}
        >
            <CommentsBulkActionsProvider setSelectAllCommentsCheckbox={props.setSelectAllCommentsCheckbox}>
                <div style={{ container: "nestedRootContainer / inline-size" }}>
                    <PartialCommentsList isPreview={props.isPreview} />
                </div>
            </CommentsBulkActionsProvider>
        </NestedCommentContextProvider>
    );
});

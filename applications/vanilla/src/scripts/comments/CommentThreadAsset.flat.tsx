/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CommentThreadSortOption, IComment } from "@dashboard/@types/api/comment";
import { css } from "@emotion/css";
import ErrorMessages from "@library/forms/ErrorMessages";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { useCommentListQuery } from "@vanilla/addon-vanilla/comments/Comments.hooks";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import isEqual from "lodash-es/isEqual";
import React, { useState } from "react";
import { CommentsBulkActionsProvider } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { CommentThreadAssetCommon } from "@vanilla/addon-vanilla/comments/CommentThreadAsset.common";
import { CommentItem } from "@vanilla/addon-vanilla/comments/CommentItem";
import { useCommentThreadPager } from "@vanilla/addon-vanilla/comments/CommentThread.hooks";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import type { CommentActionsComponentType } from "@vanilla/addon-vanilla/comments/CommentActionsComponentType";

interface IProps extends React.ComponentProps<typeof CommentThreadAssetCommon> {
    comments?: IWithPaging<IComment[]>;
    apiParams: CommentsApi.IndexParams;
    CommentActionsComponent?: CommentActionsComponentType;
}

export function CommentThreadAssetFlat(props: IProps) {
    const { comments: commentsPreload, CommentActionsComponent } = props;

    const [apiParams, _setApiParams] = useState<CommentsApi.IndexParams>(props.apiParams);

    // bulk actions purposes
    const [selectAllCommentsCheckbox, setSelectAllCommentsCheckbox] = useState<React.ReactNode>();

    const commentParent = useCommentThreadParentContext();
    const { setCurrentPage } = commentParent;

    const { query: commentListQuery, invalidate: invalidateCommentListQuery } = useCommentListQuery(
        apiParams,
        isEqual(apiParams, props.apiParams) ? commentsPreload : undefined,
    );

    async function invalidateQueries() {
        await invalidateCommentListQuery();
    }

    const { topPager, bottomPager } = useCommentThreadPager(
        commentListQuery.data,
        commentParent.url,
        props.defaultSort ?? CommentThreadSortOption.OLDEST,
        apiParams.sort,
        apiParams.page,
        (newSort) => {
            _setApiParams((currentParams) => ({ ...currentParams, sort: newSort }));
        },
        (newPage) => {
            _setApiParams((currentParams) => ({ ...currentParams, page: newPage }));
            setCurrentPage(newPage);
        },
    );

    const hasComments = (commentListQuery?.data?.data?.length ?? 0) > 0;

    return (
        <CommentThreadAssetCommon
            {...props}
            topPager={topPager}
            bottomPager={bottomPager}
            hasComments={hasComments}
            selectAllCommentsCheckbox={selectAllCommentsCheckbox}
        >
            {commentListQuery.isLoading && (
                <div style={{ display: "flex", width: "100%", padding: 16 }}>
                    <ButtonLoader className={css({ transform: "scale(2)" })} />
                </div>
            )}
            {commentListQuery.error && <ErrorMessages errors={[commentListQuery.error]} />}
            <CommentsBulkActionsProvider
                setSelectAllCommentsCheckbox={setSelectAllCommentsCheckbox}
                onBulkMutateSuccess={invalidateQueries}
                selectableCommentIDs={commentListQuery.data?.data.map((comment) => comment.commentID)}
            >
                {commentListQuery.data?.data &&
                    commentListQuery.data.data.map((comment) => {
                        return (
                            <CommentItem
                                key={comment.commentID}
                                comment={comment}
                                onMutateSuccess={invalidateQueries}
                                actions={
                                    CommentActionsComponent ? (
                                        <CommentActionsComponent
                                            comment={comment}
                                            onMutateSuccess={invalidateQueries}
                                        />
                                    ) : undefined
                                }
                                showOPTag={props.showOPTag}
                                isPreview={props.isPreview}
                                authorBadges={props.authorBadges}
                            />
                        );
                    })}
            </CommentsBulkActionsProvider>
        </CommentThreadAssetCommon>
    );
}

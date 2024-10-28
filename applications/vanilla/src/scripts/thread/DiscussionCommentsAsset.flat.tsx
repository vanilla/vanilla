/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CommentListSortOption, IComment } from "@dashboard/@types/api/comment";
import { css } from "@emotion/css";
import ErrorMessages from "@library/forms/ErrorMessages";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { useCommentListQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { DiscussionCommentsAssetCommon } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.common";
import { useDiscussionQuery, useDiscussionThreadPager } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { DiscussionThreadContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { useDiscussionThreadPaginationContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadPaginationContext";
import isEqual from "lodash/isEqual";
import React, { useState } from "react";

interface IProps extends React.ComponentProps<typeof DiscussionCommentsAssetCommon> {
    comments?: IWithPaging<IComment[]>;
    apiParams: CommentsApi.IndexParams;
    threadStyle: "flat";
}

export function DiscussionCommentsAssetFlat(props: IProps) {
    const {
        discussion: discussionPreload,
        discussionApiParams,
        comments: commentsPreload,
        ThreadItemActionsComponent,
    } = props;

    const { discussionID } = discussionPreload;

    const [apiParams, _setApiParams] = useState<CommentsApi.IndexParams>({
        ...props.apiParams,
        discussionID,
    });

    const { query: discussionListQuery, invalidate: invalidateDiscussionQuery } = useDiscussionQuery(
        discussionID,
        discussionApiParams ?? {},
        discussionPreload,
    );

    const discussion = discussionListQuery.data!;

    const { setPage } = useDiscussionThreadPaginationContext();

    const { query: commentListQuery, invalidate: invalidateCommentListQuery } = useCommentListQuery(
        apiParams,
        isEqual(apiParams, props.apiParams) ? commentsPreload : undefined,
    );

    async function invalidateQueries() {
        await invalidateDiscussionQuery();
        await invalidateCommentListQuery();
    }

    const { topPager, bottomPager } = useDiscussionThreadPager(
        commentListQuery.data,
        discussion.url,
        props.defaultSort ?? CommentListSortOption.OLDEST,
        apiParams.sort,
        apiParams.page,
        (newSort) => {
            _setApiParams((currentParams) => ({ ...currentParams, sort: newSort }));
        },
        (newPage) => {
            _setApiParams((currentParams) => ({ ...currentParams, page: newPage }));
            setPage(newPage);
        },
    );

    return (
        <DiscussionThreadContextProvider discussion={discussion}>
            <DiscussionCommentsAssetCommon {...props} topPager={topPager} bottomPager={bottomPager}>
                {commentListQuery.isLoading && (
                    <div style={{ display: "flex", width: "100%", padding: 16 }}>
                        <ButtonLoader className={css({ transform: "scale(2)" })} />
                    </div>
                )}
                {commentListQuery.error && <ErrorMessages errors={[commentListQuery.error]} />}
                {commentListQuery.data?.data &&
                    commentListQuery.data.data.map((comment) => {
                        return (
                            <CommentThreadItem
                                key={comment.commentID}
                                comment={comment}
                                discussion={discussion}
                                onMutateSuccess={invalidateQueries}
                                actions={
                                    ThreadItemActionsComponent ? (
                                        <ThreadItemActionsComponent
                                            comment={comment}
                                            discussion={discussion}
                                            onMutateSuccess={invalidateQueries}
                                        />
                                    ) : undefined
                                }
                                showOPTag={props.showOPTag}
                                isPreview={props.isPreview}
                            />
                        );
                    })}
            </DiscussionCommentsAssetCommon>
        </DiscussionThreadContextProvider>
    );
}

export default DiscussionCommentsAssetFlat;

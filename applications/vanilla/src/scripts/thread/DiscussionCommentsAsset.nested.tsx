/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CommentListSortOption, IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IThreadResponse } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { useCommentThreadQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import { DiscussionCommentsAssetCommon } from "@vanilla/addon-vanilla/thread/DiscussionCommentsAsset.common";
import { useDiscussionThreadPager } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { NestedCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";
import isEqual from "lodash-es/isEqual";
import React, { useState } from "react";

interface IProps extends React.ComponentProps<typeof DiscussionCommentsAssetCommon> {
    commentsThread?: IWithPaging<IThreadResponse>;
    apiParams: CommentsApi.IndexThreadParams;
    isPreview?: boolean;
    threadStyle: "nested";
}

export function DiscussionCommentsAssetNested(props: IProps) {
    const { showOPTag, discussion } = props;

    // we'll update this inside the component when we do pagination and sorting
    const [apiParams, setApiParams] = useState<CommentsApi.IndexThreadParams>(props.apiParams);

    const threadQuery = useCommentThreadQuery(
        apiParams,
        isEqual(apiParams, props.apiParams) ? props.commentsThread : undefined,
    );
    const { threadStructure, commentsByID } = threadQuery.data?.data ?? {};
    const isLoading = threadQuery.isLoading;

    const { topPager, bottomPager } = useDiscussionThreadPager(
        threadQuery.data,
        discussion.url,
        props.defaultSort ?? CommentListSortOption.NEWEST,
        apiParams.sort,
        apiParams.page,
        (sort) => {
            setApiParams((prev) => ({ ...prev, sort }));
        },
        (page) => {
            setApiParams((prev) => ({ ...prev, page }));
        },
    );

    return (
        <DiscussionCommentsAssetCommon {...props} topPager={topPager} bottomPager={bottomPager}>
            {isLoading && (
                <div style={{ display: "flex", width: "100%", padding: 16 }}>
                    <ButtonLoader className={css({ transform: "scale(2)" })} />
                </div>
            )}
            {!isLoading && threadStructure && commentsByID && (
                <NestedCommentsList
                    threadStructure={threadStructure}
                    commentsByID={commentsByID}
                    discussion={props.discussion}
                    showOPTag={showOPTag}
                    isPreview={props.isPreview}
                    draft={props.draft}
                />
            )}
        </DiscussionCommentsAssetCommon>
    );
}

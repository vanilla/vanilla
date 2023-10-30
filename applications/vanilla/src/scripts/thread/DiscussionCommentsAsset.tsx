/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import { IApiError } from "@library/@types/api/core";
import { scrollToElement } from "@library/content/hashScrolling";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import ErrorMessages from "@library/forms/ErrorMessages";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Widget } from "@library/layout/Widget";
import Loader from "@library/loaders/Loader";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useQuery } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { useDiscussionThreadPageContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { t } from "@vanilla/i18n";
import React, { useRef, useState } from "react";

interface IProps {
    categoryID: number;
    apiParams: CommentsApi.IndexParams;
    discussion: IDiscussion;
    commentsPreload: IWithPaging<IComment[]>;
}

export function DiscussionCommentsAsset(props: IProps) {
    const [apiParams, _setApiParams] = useState(props.apiParams);
    const { setPage } = useDiscussionThreadPageContext();
    const commentQuery = useQuery<IWithPaging<IComment[]>, IApiError>({
        queryFn: () => CommentsApi.index(apiParams),
        keepPreviousData: true,
        queryKey: ["commentsList", apiParams],
        initialData: apiParams === props.apiParams ? props.commentsPreload : undefined,
    });
    const commentTopRef = useRef<HTMLSpanElement>(null);

    if (commentQuery.isLoading) {
        // Replace with something better.
        return <Loader />;
    }

    if (commentQuery.error) {
        return <ErrorMessages errors={[commentQuery.error]} />;
    }

    const { paging, data: comments } = commentQuery.data;

    const hasComments = apiParams.page > 1 || comments.length > 0 || (paging.total ?? 0) > 0;

    const hasPager = paging.total! > paging.limit!;

    const pagerProps: INumberedPagerProps = {
        totalResults: paging.total,
        pageLimit: paging.limit,
        currentPage: apiParams.page,
        onChange: (newPage) => {
            _setApiParams((currentParams) => {
                return {
                    ...currentParams,
                    page: newPage,
                };
            });
            if (newPage !== apiParams.page) {
                setPage(newPage);
                const discUrl = new URL(props.discussion.url);
                let newPath = discUrl.pathname;
                if (newPage > 1) {
                    newPath = newPath + "/p" + newPage;
                }
                // Don't use react router. We don't want to actually trigger a real re-render.
                window.history.replaceState(null, "", newPath);
            }
            if (commentTopRef.current) {
                scrollToElement(commentTopRef.current);
            }
        },
    };

    return (
        <Widget>
            {hasComments && (
                <PageBox
                    options={{
                        borderType: BorderType.NONE,
                    }}
                    className={css({
                        marginTop: 24,
                    })}
                >
                    <span ref={commentTopRef}></span>
                    <PageHeadingBox
                        title={t("Comments")}
                        actions={hasPager && <NumberedPager {...pagerProps} rangeOnly />}
                    />

                    {comments.map((comment) => {
                        return (
                            <CommentThreadItem
                                key={comment.commentID}
                                comment={comment}
                                discussion={props.discussion}
                            />
                        );
                    })}
                    {hasPager && <NumberedPager {...pagerProps} />}
                </PageBox>
            )}
        </Widget>
    );
}

export default DiscussionCommentsAsset;

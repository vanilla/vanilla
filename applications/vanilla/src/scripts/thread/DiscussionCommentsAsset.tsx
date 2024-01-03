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
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import ErrorMessages from "@library/forms/ErrorMessages";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import Loader from "@library/loaders/Loader";
import { Tag } from "@library/metas/Tags";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { useDiscussionThreadPageContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { t } from "@vanilla/i18n";
import React, { useRef, useState } from "react";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread//DiscussionThread.hooks";

interface IProps {
    apiParams: CommentsApi.IndexParams;
    discussion: IDiscussion;
    commentsPreload: IWithPaging<IComment[]>;
}

export function DiscussionCommentsAsset(props: IProps) {
    const [apiParams, _setApiParams] = useState(props.apiParams);
    const {
        discussion: { discussionID },
    } = props;

    const { query } = useDiscussionQuery(discussionID, props.discussion);

    const discussion = query.data!;

    const { setPage } = useDiscussionThreadPageContext();
    const commentListQuery = useQuery<IWithPaging<IComment[]>, IApiError>({
        queryFn: () => CommentsApi.index(apiParams),
        keepPreviousData: true,
        queryKey: ["commentList", apiParams],
        initialData: apiParams === props.apiParams ? props.commentsPreload : undefined,
    });

    const queryClient = useQueryClient();

    async function invalidateCommentListQuery() {
        await queryClient.invalidateQueries(["commentList", apiParams]);
    }

    const commentTopRef = useRef<HTMLSpanElement>(null);

    if (commentListQuery.isLoading) {
        // Replace with something better.
        return <Loader />;
    }

    if (commentListQuery.error) {
        return <ErrorMessages errors={[commentListQuery.error]} />;
    }

    const { paging, data: comments } = commentListQuery.data;

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
                const discUrl = new URL(discussion.url);
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

    // FIXME: when there are no comments and discussion isn't closed, empty widget's padding takes up vertical space.
    return (
        <>
            {(hasComments || discussion.closed) && (
                <PageBox
                    options={{
                        borderType: BorderType.SEPARATOR,
                    }}
                >
                    {hasComments && (
                        <>
                            <span ref={commentTopRef}></span>
                            <PageHeadingBox
                                title={
                                    <div
                                        className={css({
                                            marginTop: 16,
                                        })}
                                    >
                                        <span>{t("Comments")}</span>
                                        {discussion.closed && (
                                            <Tag
                                                className={discussionThreadClasses().closedTag}
                                                preset={discussionListVariables().labels.tagPreset}
                                            >
                                                {t("Closed")}
                                            </Tag>
                                        )}
                                    </div>
                                }
                                actions={hasPager && <NumberedPager {...pagerProps} rangeOnly />}
                            />

                            {comments.map((comment) => {
                                return (
                                    <CommentThreadItem
                                        key={comment.commentID}
                                        comment={comment}
                                        discussion={discussion}
                                        onMutateSuccess={invalidateCommentListQuery}
                                    />
                                );
                            })}
                        </>
                    )}
                    {discussion.closed && (
                        <PageBox options={{ borderType: BorderType.SEPARATOR_BETWEEN }}>
                            <div
                                className={css({
                                    marginTop: 8,
                                    marginBottom: 8,
                                })}
                            >
                                {t("This discussion has been closed.")}
                            </div>
                        </PageBox>
                    )}
                    {hasPager && <NumberedPager {...pagerProps} />}
                </PageBox>
            )}
        </>
    );
}

export default DiscussionCommentsAsset;

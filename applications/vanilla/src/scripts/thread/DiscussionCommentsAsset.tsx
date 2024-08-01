/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
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
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { useDiscussionThreadPaginationContext } from "@vanilla/addon-vanilla/thread/DiscussionThreadPaginationContext";
import { t } from "@vanilla/i18n";
import React, { useCallback, useEffect, useRef, useState } from "react";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { useCommentListQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import isEqual from "lodash/isEqual";
import { DiscussionThreadContextProvider } from "@vanilla/addon-vanilla/thread/DiscussionThreadContext";
import { useRefreshStaleAttachments } from "@library/features/discussions/integrations/Integrations.context";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments?: IWithPaging<IComment[]>;
    apiParams: CommentsApi.IndexParams;
    renderTitle?: boolean;
    ThreadItemActionsComponent?: React.ComponentType<{
        comment: IComment;
        discussion: IDiscussion;
        onMutateSuccess?: () => Promise<void>;
    }>;
}

export function DiscussionCommentsAsset(props: IProps) {
    const {
        discussion: discussionPreload,
        discussionApiParams,
        comments: commentsPreload,
        renderTitle = true,
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

    const refreshStaleAttachments = useRefreshStaleAttachments();

    const refreshStaleCommentAttachments = useCallback(async () => {
        if (!commentListQuery.isPreviousData && commentListQuery.isSuccess) {
            const attachments = (commentListQuery.data?.data ?? []).map((comment) => comment.attachments ?? []).flat();
            if (attachments.length > 0) {
                await refreshStaleAttachments(attachments);
                await invalidateCommentListQuery();
            }
        }
    }, [commentListQuery.isPreviousData, commentListQuery.isSuccess]);

    useEffect(() => {
        refreshStaleCommentAttachments();
    }, [refreshStaleCommentAttachments]);

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

    return (
        <DiscussionThreadContextProvider discussion={discussion}>
            {(hasComments || discussion.closed) && (
                <PageBox
                    options={{
                        borderType: BorderType.SEPARATOR,
                    }}
                >
                    {hasComments && (
                        <>
                            <span ref={commentTopRef}></span>
                            {renderTitle && (
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
                            )}

                            {comments.map((comment) => {
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
        </DiscussionThreadContextProvider>
    );
}

export default DiscussionCommentsAsset;

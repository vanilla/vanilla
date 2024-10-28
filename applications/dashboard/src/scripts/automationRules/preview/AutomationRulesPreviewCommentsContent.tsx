/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { DISCUSSIONS_MAX_PAGE_COUNT } from "@library/features/discussions/discussionHooks";
import { MetaItem } from "@library/metas/Metas";
import DateTime from "@library/content/DateTime";
import { AutomationRulesPreviewContentHeader } from "@dashboard/automationRules/preview/AutomationRulesPreviewContentHeader";
import { useCommentListQuery } from "@vanilla/addon-vanilla/thread/Comments.hooks";
import SmartLink from "@library/routing/links/SmartLink";
import { cx } from "@emotion/css";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";

interface IProps {
    query: CommentsApi.IndexParams;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
    className?: string;
    titlesAsLinks?: boolean;
    contentTypeAsComments?: boolean;
}

export function AutomationRulesPreviewCommentsContent(props: IProps) {
    const classes = automationRulesClasses();
    const [currentQuery, setCurrentQuery] = useState<CommentsApi.IndexParams>(props.query);

    const { query: result } = useCommentListQuery(currentQuery);

    const commentList = result.data?.data;
    const pagination = result.data?.paging;
    const hasComments = Boolean(commentList?.length);

    useEffect(() => {
        if (commentList && commentList.length === 0) {
            props.onPreviewContentLoad?.(true);
        }
    }, [commentList]);

    return (
        <div className={props.className}>
            <AutomationRulesPreviewContentHeader
                contentType={props.contentTypeAsComments ? "Comments" : "Posts"}
                totalResults={pagination?.total}
                emptyResults={commentList?.length === 0}
                fromStatusToggle={props.fromStatusToggle}
            />
            {hasComments && pagination?.total && (
                <>
                    <div>
                        <NumberedPager
                            {...{
                                totalResults: pagination?.total,
                                currentPage: pagination?.currentPage ?? 1,
                                pageLimit: 30,
                                className: automationRulesClasses().previewPager,
                                hasMorePages: pagination?.total
                                    ? pagination?.total >= DISCUSSIONS_MAX_PAGE_COUNT
                                    : false,
                                showNextButton: false,
                            }}
                            onChange={(page: number) => setCurrentQuery({ ...currentQuery, page: page })}
                            isMobile={false}
                        />
                    </div>
                    <ul>
                        {commentList?.map((comment, index) => (
                            <li
                                key={index}
                                className={cx(
                                    classes.previewDiscussionItem,
                                    classes.verticalGap,
                                    classes.previewDiscussionBorder,
                                )}
                            >
                                {props.titlesAsLinks ? (
                                    <SmartLink to={comment.url} className={classes.clickableTitle}>
                                        {comment.name}
                                    </SmartLink>
                                ) : (
                                    <div>{comment.name}</div>
                                )}
                                {comment.insertUser && (
                                    <div>
                                        <span className={classes.previewDiscussionMeta}>{`${t("Commented by")} `}</span>
                                        <span className={cx(classes.smallFont, classes.bold)}>
                                            {comment.insertUser.name}
                                        </span>
                                        <MetaItem>
                                            <DateTime timestamp={comment.dateInserted} />
                                        </MetaItem>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </div>
    );
}

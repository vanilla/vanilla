/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { useRef, useEffect, useState, useMemo, memo } from "react";
import { getNestedItemByMatchingPathOrID, getNestedItemID } from "@vanilla/addon-vanilla/comments/NestedCommentUtils";
import { IWithPaging } from "@library/navigation/SimplePagerModel";
import { IComment } from "@dashboard/@types/api/comment";
import { PageBox } from "@library/layout/PageBox";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import type { IThreadItem, IThreadResponse } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import type { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { NestedCommentItem } from "@vanilla/addon-vanilla/comments/NestedCommentItem";
import { NestedCommentHole } from "@vanilla/addon-vanilla/comments/NestedCommentHole";
import { CommentThreadReplyContainer } from "@vanilla/addon-vanilla/comments/CommentThreadReplyContainer";
import { IDraftProps } from "@vanilla/addon-vanilla/drafts/types";

interface IProps extends IThreadResponse {
    rootClassName?: string;
    discussionApiParams?: DiscussionsApi.GetParams;
    comments?: IWithPaging<IComment[]>;
    commentApiParams?: CommentsApi.IndexThreadParams;
    renderTitle?: boolean;
    draft?: IDraftProps;
    isPreview?: boolean;
}

/**
 * Renders a list of comments, holes and replies. Will recurse if there are any child comments
 */
export function PartialCommentsList(props: Partial<IProps>) {
    const { threadStructure, addLastChildRefID, showOPTag, authorBadges } = useNestedCommentContext();
    const thread = props.threadStructure ?? threadStructure;

    const lastChildRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (lastChildRef && lastChildRef.current) {
            const id = lastChildRef.current.getAttribute("data-id");
            if (id) {
                addLastChildRefID(id, lastChildRef);
            }
        }
    }, [thread, lastChildRef]);

    return (
        <>
            {thread.map((threadItem: IThreadItem, index) => {
                const id = getNestedItemID(threadItem);
                const key = `${threadItem.parentCommentID}${id}`;
                const isLast = index === thread.length - 1;
                const refProps = {
                    ...(isLast && { ref: lastChildRef }),
                };

                return (
                    <PageBox
                        key={key}
                        options={{ borderType: threadItem.depth <= 1 ? BorderType.SEPARATOR : BorderType.NONE }}
                    >
                        <div
                            className={cx(props.rootClassName, threadItem.type)}
                            data-depth={threadItem.depth}
                            data-id={threadItem.parentCommentID}
                            {...refProps}
                        >
                            {threadItem.type === "comment" && (
                                <NestedCommentItem
                                    threadItem={threadItem}
                                    showOPTag={showOPTag}
                                    isPreview={props.isPreview}
                                    authorBadges={authorBadges}
                                />
                            )}
                            {threadItem.type === "hole" && <NestedCommentHole threadItem={threadItem} />}
                            {threadItem.type === "reply" && <CommentThreadReplyContainer threadItem={threadItem} />}
                        </div>
                    </PageBox>
                );
            })}
        </>
    );
}

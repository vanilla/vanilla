/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentEditor } from "@vanilla/addon-vanilla/thread/CommentEditor";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/thread/CommentOptionsMenu";
import { ReportCountMeta } from "@vanilla/addon-vanilla/thread/ReportCountMeta";
import { ThreadItem } from "@vanilla/addon-vanilla/thread/ThreadItem";
import { ThreadItemContextProvider } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import React, { useEffect, useState } from "react";
import { DiscussionAttachment } from "./DiscussionAttachmentsAsset";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { t } from "@vanilla/i18n";
import { removedUserFragment } from "@library/features/users/constants/userFragment";
import { useLocation } from "react-router";
import { MetaItem } from "@library/metas/Metas";
import { debug } from "@vanilla/utils";
import { ToolTip } from "@library/toolTip/ToolTip";
import { discussionThreadClasses } from "@vanilla/addon-vanilla/thread/DiscussionThread.classes";
import { cx } from "@emotion/css";
import { CommentsApi } from "@vanilla/addon-vanilla/thread/CommentsApi";
import { getMeta } from "@library/utility/appUtils";

interface IProps {
    comment: IComment;
    discussion: IDiscussion;
    /** called after a successful PATCH or DELETE.*/
    onMutateSuccess?: () => Promise<void>;
    actions?: React.ComponentProps<typeof ThreadItem>["actions"];
    boxOptions?: Partial<IBoxOptions>;
    userPhotoLocation?: "header" | "left";
    isInternal?: boolean;
    onReply?: () => void;
    showOPTag?: boolean;
    isPreview?: boolean;
    readOnly?: boolean;
    hasActiveDraft?: boolean;
    threadStyle: "flat" | "nested";
}

const CONTENT_REMOVED_STRING = getMeta("removedString", "This content has been removed.");

export function CommentThreadItem(props: IProps) {
    const { comment, onMutateSuccess, actions, discussion, readOnly, hasActiveDraft } = props;
    const [isEditing, setIsEditing] = useState(false);
    const { updateComment } = useCommentThread();
    const showOPTag = props.showOPTag && (props.isPreview ?? discussion?.insertUser?.userID === comment.insertUserID);
    const isPermalinked = useLocation().hash?.toLowerCase() === `#comment_${comment?.commentID}`;
    const [trollContentVisible, setTrollContentVisible] = useState(false);
    const [insertUser, setInsertUser] = useState(comment?.isTroll ? removedUserFragment() : comment.insertUser);
    const [body, setBody] = useState(comment?.isTroll ? t(CONTENT_REMOVED_STRING) : comment.body);

    const editCommentQuery = useQuery({
        enabled: isEditing,
        queryFn: async () => {
            return await CommentsApi.getEdit(comment.commentID);
        },
        queryKey: ["commentEdit", comment.commentID],
    });

    const queryClient = useQueryClient();

    async function invalidateEditCommentQuery() {
        await queryClient.invalidateQueries(["commentEdit", comment.commentID]);
        await queryClient.invalidateQueries(["commentThread"]);
    }
    const classes = discussionThreadClasses();

    useEffect(() => {
        if (comment?.isTroll) {
            if (trollContentVisible) {
                setBody(comment.body);
                setInsertUser(comment.insertUser);
            } else {
                setBody(t(CONTENT_REMOVED_STRING));
                setInsertUser(removedUserFragment());
            }
        }
    }, [comment.body, comment.isTroll, trollContentVisible]);

    return (
        <ThreadItemContextProvider
            threadStyle={props.threadStyle}
            recordType={"comment"}
            recordID={comment.commentID}
            recordUrl={comment.url}
            timestamp={comment.dateInserted}
            name={comment.name}
            attributes={comment.attributes}
            authorID={trollContentVisible ? comment.insertUserID : 0}
            extraMetas={
                <>
                    {debug() &&
                        !readOnly &&
                        comment.experimentalTrending != null &&
                        comment.experimentalTrendingDebug != null && (
                            <ToolTip
                                customWidth={400}
                                label={
                                    <div className={classes.trendingTooltip}>
                                        <strong>Template</strong>
                                        <div
                                            className={cx(classes.trendingMathMl, "code", "codeBlock")}
                                            dangerouslySetInnerHTML={{
                                                __html: comment.experimentalTrendingDebug.mathMl.template,
                                            }}
                                        />
                                        <strong>Equation</strong>
                                        <div
                                            className={cx(classes.trendingMathMl, "code", "codeBlock")}
                                            dangerouslySetInnerHTML={{
                                                __html: comment.experimentalTrendingDebug.mathMl.equation,
                                            }}
                                        />
                                    </div>
                                }
                            >
                                <MetaItem>Trending {comment.experimentalTrending}</MetaItem>
                            </ToolTip>
                        )}
                </>
            }
        >
            <ThreadItem
                isHighlighted={isPermalinked}
                boxOptions={props.boxOptions ?? {}}
                content={body}
                actions={actions}
                editor={
                    isEditing &&
                    !!editCommentQuery.data && (
                        <CommentEditor
                            commentEdit={editCommentQuery.data}
                            comment={props.comment}
                            onSuccess={async (data) => {
                                !!onMutateSuccess && (await onMutateSuccess());
                                await invalidateEditCommentQuery();
                                updateComment(comment.commentID);
                                setIsEditing(false);
                            }}
                            onClose={() => {
                                setIsEditing(false);
                            }}
                        />
                    )
                }
                user={insertUser}
                key={comment.commentID}
                userPhotoLocation={props.userPhotoLocation ?? "header"}
                reactions={!comment.isTroll || !readOnly ? comment.reactions : undefined}
                attachmentsContent={
                    (comment.attachments ?? []).length > 0 && !readOnly ? (
                        <>
                            {comment.attachments?.map((attachment) => (
                                <ReadableIntegrationContextProvider
                                    key={attachment.attachmentID}
                                    attachmentType={attachment.attachmentType}
                                >
                                    <DiscussionAttachment key={attachment.attachmentID} attachment={attachment} />
                                </ReadableIntegrationContextProvider>
                            ))}
                        </>
                    ) : null
                }
                options={
                    !readOnly && (
                        <>
                            <ReportCountMeta
                                countReports={comment.reportMeta?.countReports}
                                recordID={comment.commentID}
                                recordType="comment"
                            />
                            <CommentOptionsMenu
                                discussion={discussion}
                                comment={comment}
                                onCommentEdit={() => {
                                    setIsEditing(true);
                                }}
                                onMutateSuccess={onMutateSuccess}
                                isEditLoading={isEditing && editCommentQuery.isLoading}
                                isInternal={props.isInternal}
                                isTrollContentVisible={trollContentVisible}
                                toggleTrollContent={setTrollContentVisible}
                            />
                        </>
                    )
                }
                suggestionContent={
                    comment.suggestion && {
                        suggestion: {
                            ...comment.suggestion,
                            summary: comment.body,
                        },
                        discussion: discussion!,
                        commentID: comment.commentID,
                        onMutateSuccess,
                    }
                }
                onReply={props.onReply}
                replyLabel={hasActiveDraft ? t("Continue replying") : t("Reply")}
                showOPTag={comment.isTroll ? false : showOPTag}
                categoryID={discussion?.categoryID}
                isClosed={discussion?.closed}
                readOnly={readOnly}
            />
        </ThreadItemContextProvider>
    );
}

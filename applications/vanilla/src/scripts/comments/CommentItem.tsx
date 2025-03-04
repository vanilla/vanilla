/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IComment } from "@dashboard/@types/api/comment";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { CommentEdit } from "@vanilla/addon-vanilla/comments/CommentEdit";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/comments/CommentOptionsMenu";
import React, { useEffect, useMemo, useState } from "react";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { t } from "@vanilla/i18n";
import { removedUserFragment } from "@library/features/users/constants/userFragment";
import { useLocation } from "react-router";
import { MetaItem } from "@library/metas/Metas";
import { debug } from "@vanilla/utils";
import { ToolTip } from "@library/toolTip/ToolTip";
import { css, cx } from "@emotion/css";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { getMeta } from "@library/utility/appUtils";
import { ContributionItem } from "@library/contributionItems/ContributionItem";
import { reactionsVariables } from "@library/reactions/Reactions.variables";
import { useCurrentUser } from "@library/features/users/userHooks";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import CheckBox from "@library/forms/Checkbox";
import { useCommentsBulkActionsContext } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { ContentItem } from "@vanilla/addon-vanilla/contentItem/ContentItem";
import { ContentItemContextProvider } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItemWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import { ContentItemVisibilityRenderer } from "@vanilla/addon-vanilla/contentItem/ContentItemVisibilityRenderer";
import { ReportCountMeta } from "@vanilla/addon-vanilla/reporting/ReportCountMeta";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import {
    isDiscussionCommentParent,
    useCommentThreadParentContext,
} from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";
import { isCommentDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";

interface IProps {
    comment: IComment;
    /** called after a successful PATCH or DELETE.*/
    onMutateSuccess?: () => Promise<void>;
    actions?: React.ComponentProps<typeof ContentItem>["actions"];
    boxOptions?: Partial<IBoxOptions>;
    userPhotoLocation?: "header" | "left";
    isInternal?: boolean;
    onReply?: () => void;
    showOPTag?: boolean;
    isPreview?: boolean;
    readOnly?: boolean;
    authorBadges?: {
        display: boolean;
        limit: number;
    };
}

export const CONTENT_REMOVED_STRING = getMeta("removedString", "This content has been removed.");

export function CommentItem(props: IProps) {
    const { comment, onMutateSuccess, authorBadges } = props;
    const commentParent = useCommentThreadParentContext();
    const [isEditing, setIsEditing] = useState(false);
    const { updateComment, currentReplyFormRef } = useNestedCommentContext();
    const { draft } = useDraftContext();
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();

    const showOPTag = props.showOPTag && (props.isPreview ?? commentParent.insertUserID === comment.insertUserID);
    const isPermalinked = useLocation().hash?.toLowerCase() === `#comment_${comment?.commentID}`;
    const [trollContentVisible, setTrollContentVisible] = useState(false);

    const insertUser = !trollContentVisible && comment?.isTroll ? removedUserFragment() : comment.insertUser;
    const body = !trollContentVisible && comment?.isTroll ? t(CONTENT_REMOVED_STRING) : comment.body;
    const readOnly = (!trollContentVisible && comment?.isTroll) || comment.insertUserID == 0 ? true : props.readOnly;

    // Hide comments (e.g. from ignored users)
    const commentIsFromIgnoredUser = getMeta("ignoredUserIDs", []).includes(comment.insertUserID);
    const [isCommentHidden, setIsCommentHidden] = useState(commentIsFromIgnoredUser);

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
    }
    const classes = commentThreadClasses();

    const showWarningStatus = comment.insertUserID === currentUser?.userID || hasPermission("community.moderate");

    const { canUseAdminCheckboxes, checkedCommentIDs, addCheckedCommentsByIDs, removeCheckedCommentsByIDs } =
        useCommentsBulkActionsContext();

    const containsActiveDraft = () => {
        if (draft?.attributes?.draftMeta && isCommentDraftMeta(draft?.attributes?.draftMeta)) {
            return draft?.attributes?.draftMeta?.commentParentID === comment.commentID && !currentReplyFormRef?.current;
        }
    };

    return (
        <ContentItemContextProvider
            recordType={"comment"}
            recordID={comment.commentID}
            recordUrl={comment.url}
            timestamp={comment.dateInserted}
            dateUpdated={comment.dateUpdated ?? undefined}
            updateUser={comment.updateUser}
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
            <ContentItem
                isHighlighted={isPermalinked}
                boxOptions={props.boxOptions ?? {}}
                beforeContent={
                    comment.warning &&
                    showWarningStatus && (
                        <ContentItemWarning
                            warning={comment.warning}
                            recordName={comment.name}
                            recordUrl={comment.url}
                            moderatorNoteVisible={hasPermission("community.moderate")}
                        />
                    )
                }
                content={body}
                isHidden={isCommentHidden}
                visibilityHandlerComponent={
                    commentIsFromIgnoredUser && (
                        <ContentItemVisibilityRenderer
                            onVisibilityChange={setIsCommentHidden}
                            contentText={t("Content from Ignored User.")}
                            isPostHidden={isCommentHidden}
                        />
                    )
                }
                actions={props.actions}
                editor={
                    isEditing &&
                    !!editCommentQuery.data && (
                        <CommentEdit
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
                reactions={!readOnly ? comment.reactions : undefined}
                attachmentsContent={
                    (comment.attachments ?? []).length > 0 && !readOnly ? (
                        <>
                            {comment.attachments?.map((attachment) => (
                                <ReadableIntegrationContextProvider
                                    key={attachment.attachmentID}
                                    attachmentType={attachment.attachmentType}
                                >
                                    <ContentItemAttachment attachment={attachment} />
                                </ReadableIntegrationContextProvider>
                            ))}
                        </>
                    ) : null
                }
                options={
                    <>
                        <ReportCountMeta
                            countReports={comment.reportMeta?.countReports}
                            recordID={comment.commentID}
                            recordType="comment"
                        />
                        <CommentOptionsMenu
                            commentParent={commentParent}
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
                }
                suggestionContent={
                    comment.suggestion && isDiscussionCommentParent(commentParent)
                        ? {
                              suggestion: {
                                  ...comment.suggestion,
                                  summary: comment.body,
                              },
                              comment,
                              commentParent,
                              commentID: comment.commentID,
                              onMutateSuccess,
                          }
                        : undefined
                }
                onReply={props.onReply}
                replyLabel={containsActiveDraft() ? t("Continue replying") : t("Reply")}
                showOPTag={comment.isTroll ? false : showOPTag}
                categoryID={comment.categoryID}
                isClosed={commentParent.closed}
                readOnly={readOnly}
                additionalAuthorMeta={
                    authorBadges?.display &&
                    comment.insertUser?.badges?.length && (
                        <>
                            {comment.insertUser.badges
                                .map((badge, index) => (
                                    <ContributionItem
                                        key={index}
                                        name={badge.name}
                                        url={badge.url}
                                        photoUrl={badge.photoUrl}
                                        themingVariables={reactionsVariables()}
                                        className={ContentItemClasses().authorBadgesMeta}
                                    />
                                ))
                                .slice(0, authorBadges.limit ?? 5)}
                        </>
                    )
                }
                checkBox={
                    canUseAdminCheckboxes && (
                        <CheckBox
                            checked={checkedCommentIDs.includes(comment.commentID)}
                            label={`Select ${comment.name}`}
                            hideLabel={true}
                            className={css({ marginRight: 8 })}
                            onChange={(e) => {
                                if (e.target.checked) {
                                    addCheckedCommentsByIDs(comment.commentID);
                                } else {
                                    removeCheckedCommentsByIDs(comment.commentID);
                                }
                            }}
                        />
                    )
                }
            />
        </ContentItemContextProvider>
    );
}

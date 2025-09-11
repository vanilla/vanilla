/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    CommentItemFragmentContextProvider,
    useCommentItemFragmentContext,
} from "@vanilla/addon-vanilla/comments/CommentItemFragmentContext";
import { ContentItem, IContentItemProps } from "@vanilla/addon-vanilla/contentItem/ContentItem";
import {
    ContentItemContextProvider,
    useContentItemContext,
} from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { css, cx } from "@emotion/css";
import {
    isDiscussionCommentParent,
    useCommentThreadParentContext,
} from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { useQuery, useQueryClient } from "@tanstack/react-query";

import CheckBox from "@library/forms/Checkbox";
import { CommentEdit } from "@vanilla/addon-vanilla/comments/CommentEdit";
import { CommentOptionsMenu } from "@vanilla/addon-vanilla/comments/CommentOptionsMenu";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { ContentItemAttachment } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachment";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { ContentItemVisibilityRenderer } from "@vanilla/addon-vanilla/contentItem/ContentItemVisibilityRenderer";
import { ContentItemWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import { ContributionItem } from "@library/contributionItems/ContributionItem";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { IComment } from "@dashboard/@types/api/comment";
import { MetaItem } from "@library/metas/Metas";
import { ReadableIntegrationContextProvider } from "@library/features/discussions/integrations/Integrations.context";
import { ReportCountMeta } from "@vanilla/addon-vanilla/reporting/ReportCountMeta";
import { ToolTip } from "@library/toolTip/ToolTip";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import { debug } from "@vanilla/utils";
import { getMeta } from "@library/utility/appUtils";
import { isCommentDraftMeta } from "@vanilla/addon-vanilla/drafts/utils";
import { reactionsVariables } from "@library/reactions/Reactions.variables";
import { removedUserFragment } from "@library/features/users/constants/userFragment";
import { t } from "@vanilla/i18n";
import { useCommentsBulkActionsContext } from "@vanilla/addon-vanilla/comments/bulkActions/CommentsBulkActionsContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import { useLocation } from "react-router";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useState } from "react";

export interface ICommentItemProps {
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

export interface ICommentFragmentImplProps extends ICommentItemProps, IContentItemProps {
    isEditing: boolean;
    setIsEditing: React.Dispatch<React.SetStateAction<boolean>>;
}

/**
 * This component renders a wrapped comment with all the necessary context data
 */
export const CommentItem = (props: ICommentItemProps) => {
    const { comment, readOnly } = props;
    const classes = commentThreadClasses.useAsHook();

    if (!comment) {
        return <></>;
    }

    return (
        <ContentItemContextProvider
            recordType={"comment"}
            recordID={comment.commentID}
            recordUrl={comment.url}
            timestamp={comment.dateInserted}
            dateUpdated={comment.dateUpdated ?? undefined}
            insertUser={comment.insertUser}
            updateUser={comment.updateUser}
            name={comment.name}
            attributes={comment.attributes}
            authorID={comment.insertUserID}
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
            <CommentContexts {...props} />
        </ContentItemContextProvider>
    );
};

export const CONTENT_REMOVED_STRING = getMeta("removedString", "This content has been removed.");

const checkboxMargin = css({ marginRight: 8 });

/**
 * This component wraps the comment component with additional contexts
 * for fragment consumption. It needs to be its own component because it
 * uses values from the content item context.
 */
export const CommentContexts = (props: ICommentItemProps) => {
    const { comment, onMutateSuccess, authorBadges } = props;
    const commentParent = useCommentThreadParentContext();
    const { currentReplyFormRef } = useNestedCommentContext();
    const { draft } = useDraftContext();
    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const location = useLocation();
    const { canUseAdminCheckboxes, checkedCommentIDs, addCheckedCommentsByIDs, removeCheckedCommentsByIDs } =
        useCommentsBulkActionsContext();
    const { trollContentVisible } = useContentItemContext();

    const showOPTag = props.showOPTag && (props.isPreview ?? commentParent.insertUserID === comment.insertUserID);
    const isPermalinked = location.hash?.toLowerCase() === `#comment_${comment?.commentID}`;

    const insertUser = !trollContentVisible && comment.isTroll ? removedUserFragment() : comment.insertUser;
    const body =
        !trollContentVisible && comment.isTroll ? blessStringAsSanitizedHtml(t(CONTENT_REMOVED_STRING)) : comment?.body;
    const readOnly = (!trollContentVisible && comment.isTroll) || comment.insertUserID == 0 ? true : props.readOnly;

    // Hide comments (e.g. from ignored users)
    const commentIsFromIgnoredUser = getMeta("ignoredUserIDs", []).includes(comment?.insertUserID);
    const [isCommentHidden, setIsCommentHidden] = useState(commentIsFromIgnoredUser);

    const showWarningStatus = comment?.insertUserID === currentUser?.userID || hasPermission("community.moderate");

    const containsActiveDraft = () => {
        if (draft?.attributes?.draftMeta && isCommentDraftMeta(draft?.attributes?.draftMeta)) {
            return (
                draft?.attributes?.draftMeta?.commentParentID === comment?.commentID && !currentReplyFormRef?.current
            );
        }
    };

    const contextProps = {
        comment,
        isHighlighted: isPermalinked,
        boxOptions: props.boxOptions ?? {},
        warnings: comment.warning && showWarningStatus && (
            <ContentItemWarning
                warning={comment.warning}
                recordName={comment.name}
                recordUrl={comment.url}
                moderatorNoteVisible={hasPermission("community.moderate")}
            />
        ),
        content: body,
        isHidden: isCommentHidden,
        visibilityHandlerComponent: commentIsFromIgnoredUser && (
            <ContentItemVisibilityRenderer
                onVisibilityChange={setIsCommentHidden}
                contentText={t("Content from Ignored User.")}
                isPostHidden={isCommentHidden}
            />
        ),
        actions: props.actions,
        user: insertUser,
        key: comment.commentID,
        userPhotoLocation: props.userPhotoLocation ?? "header",
        reactions: !readOnly ? comment.reactions : undefined,
        attachmentsContent:
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
            ) : null,
        suggestionContent:
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
                : undefined,
        onReply: props.onReply,
        replyLabel: containsActiveDraft() ? t("Continue Replying") : t("Reply"),
        showOPTag: comment.isTroll ? false : showOPTag,
        categoryID: comment.categoryID,
        isClosed: commentParent.closed,
        readOnly: readOnly,
        additionalAuthorMeta: authorBadges?.display && comment.insertUser?.badges?.length && (
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
        ),
        checkBox: canUseAdminCheckboxes && (
            <CheckBox
                checked={checkedCommentIDs.includes(comment.commentID)}
                label={`Select ${comment.name}`}
                hideLabel={true}
                className={checkboxMargin}
                onChange={(e) => {
                    if (e.target.checked) {
                        addCheckedCommentsByIDs(comment.commentID);
                    } else {
                        removeCheckedCommentsByIDs(comment.commentID);
                    }
                }}
            />
        ),
    };

    return (
        <CommentItemFragmentContextProvider {...props} {...contextProps}>
            <Comment />
        </CommentItemFragmentContextProvider>
    );
};

/**
 * This comment component merges props from the upper contexts together
 * with the editing state and before passing it to the implementation as props.
 */
function Comment() {
    const commentParent = useCommentThreadParentContext();
    const { updateComment } = useNestedCommentContext();
    const { trollContentVisible, setTrollContentVisible } = useContentItemContext();

    // Take in props from the comment fragment context
    const { isEditing, setIsEditing, comment, ...contextProps } = useCommentItemFragmentContext();

    const editCommentQuery = useQuery({
        enabled: isEditing,
        queryFn: async () => {
            return await CommentsApi.getEdit(comment.commentID);
        },
        queryKey: ["commentEdit", comment.commentID],
    });

    const queryClient = useQueryClient();

    async function invalidateEditCommentQuery() {
        await queryClient.invalidateQueries(["commentEdit", comment?.commentID]);
    }

    // Merge context props with those needed for editing
    const withEditProps = {
        ...contextProps,
        isEditing,
        setIsEditing,
        comment,
        editor: isEditing && !!editCommentQuery.data && (
            <CommentEdit
                commentEdit={editCommentQuery.data}
                comment={comment}
                onSuccess={async () => {
                    !!contextProps.onMutateSuccess && (await contextProps.onMutateSuccess());
                    await invalidateEditCommentQuery();
                    updateComment(comment.commentID);
                    setIsEditing(false);
                }}
                onClose={() => {
                    setIsEditing(false);
                }}
            />
        ),
        options: (
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
                    onMutateSuccess={contextProps.onMutateSuccess}
                    isEditLoading={isEditing && editCommentQuery.isLoading}
                    isInternal={contextProps.isInternal}
                    isTrollContentVisible={trollContentVisible}
                    toggleTrollContent={setTrollContentVisible}
                />
            </>
        ),
    };

    return <CommentFragmentImpl {...withEditProps} />;
}

/**
 * This component renders the content item for the comment
 * or the appropriate comment item fragment.
 */
function CommentFragmentImpl(props: ICommentFragmentImplProps) {
    const Impl = useFragmentImpl("CommentItemFragment", ContentItem);
    return <Impl {...props} />;
}

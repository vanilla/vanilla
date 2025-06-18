/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { CommentItemFragmentContextProvider } from "@vanilla/addon-vanilla/comments/CommentItemFragmentContext";
import CommentItemFragmentInjectable from "@vanilla/injectables/CommentItemFragment";
import { ContentItemContextProvider } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import React, { useState } from "react";
import { ContentItemWarning, IPostWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import { hasPermission } from "@library/features/users/Permission";
import { ContentItemVisibilityRenderer } from "@vanilla/addon-vanilla/contentItem/ContentItemVisibilityRenderer";
import { t } from "@vanilla/i18n";
import { ContributionItem } from "@library/contributionItems/ContributionItem";
import { reactionsVariables } from "@library/reactions/Reactions.variables";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import CheckBox from "@library/forms/Checkbox";
import AttachmentLayoutComponent from "@library/features/discussions/integrations/components/AttachmentLayout";
import { Icon } from "@vanilla/icons";
import { CommentEdit } from "@vanilla/addon-vanilla/comments/CommentEdit";

export default function CommentItemFragmentPreview(props: {
    previewData: CommentItemFragmentInjectable.Props;
    children?: React.ReactNode;
    previewProps?: any;
}) {
    const { comment, isHighlighted, warnings, isHidden, onReply, showOPTag, authorBadges } = props.previewData;

    const commentIsFromIgnoredUser = !!props.previewProps?.isIgnoredUser;
    const [isCommentHidden, setIsCommentHidden] = useState(commentIsFromIgnoredUser);

    const showCheckbox = !!props.previewProps?.isCheckboxesVisible;
    const [isChecked, setIsChecked] = useState(false);

    const commentItemFragmentContextProps = {
        comment,
        isHighlighted,
        warnings: warnings && (
            <ContentItemWarning
                warning={warnings as IPostWarning}
                recordName={comment.name}
                recordUrl={comment.url}
                moderatorNoteVisible={hasPermission("community.moderate")}
            />
        ),
        editor: props.previewData.isEditing ? (
            <CommentEdit
                commentEdit={{
                    commentID: 1,
                    parentRecordType: "comment",
                    parentRecordID: 17,
                    body: JSON.stringify([
                        {
                            type: "p",
                            children: [{ text: "This will be the editable content of the comment" }],
                        },
                    ]),
                    format: "rich2",
                }}
                comment={comment}
                onSuccess={async () => new Promise((resolve) => resolve())}
                onClose={() => null}
            />
        ) : null,
        content: comment.body,
        isHidden,
        visibilityHandlerComponent: commentIsFromIgnoredUser && (
            <ContentItemVisibilityRenderer
                onVisibilityChange={setIsCommentHidden}
                contentText={t("Content from Ignored User.")}
                isPostHidden={isCommentHidden}
            />
        ),
        actions: props.previewData.actions,
        user: comment.insertUser,
        key: comment.commentID,
        reactions: comment.reactions,
        attachmentsContent: (
            <>
                {comment.attachments && (
                    <AttachmentLayoutComponent
                        title={"Vanilla Escalation"}
                        notice={"Open"}
                        url={"https://www.vanillaforums.com"}
                        dateUpdated={"2021-02-03 17:51:15"}
                        user={props.previewData.comment.insertUser}
                        icon={<Icon icon={"vanilla-logo"} height={60} width={60} />}
                        metadata={[
                            {
                                labelCode: "Name",
                                value: "Name of the escalation here",
                            },
                            {
                                labelCode: "Number of Reports",
                                value: 12,
                            },
                            {
                                labelCode: "Number of Comments",
                                value: 3,
                            },
                            {
                                labelCode: "Report Reasons",
                                value: ["Reason 1", "Reason 2", "Reason 3"],
                            },
                            {
                                labelCode: "Last Reported",
                                value: "1990-08-20T04:00:00Z",
                                format: "date-time",
                            },
                        ]}
                    />
                )}
            </>
        ),

        onReply: onReply,
        replyLabel: t("Reply"),
        showOPTag: showOPTag,
        categoryID: comment.categoryID,
        isClosed: false,
        readOnly: hasPermission("community.moderate"),
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
        checkBox: showCheckbox && (
            <CheckBox
                checked={isChecked}
                label={`Select ${comment.name}`}
                hideLabel={true}
                onChange={() => setIsChecked(!isChecked)}
            />
        ),
    };

    return (
        <>
            <ContentItemContextProvider
                recordType={"comment"}
                recordID={comment.commentID}
                recordUrl={comment.url}
                timestamp={comment.dateInserted}
                dateUpdated={comment.dateUpdated ?? undefined}
                updateUser={comment.updateUser}
                name={comment.name}
                attributes={comment.attributes}
                authorID={comment.insertUserID}
            >
                <CommentItemFragmentContextProvider {...props.previewData} {...commentItemFragmentContextProps}>
                    {props.children}
                </CommentItemFragmentContextProvider>
            </ContentItemContextProvider>
        </>
    );
}

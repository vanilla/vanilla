import CommentItem from "@vanilla/injectables/CommentItemFragment";
import Components from "@vanilla/injectables/Components";
import Utils from "@vanilla/injectables/Utils";
import React from "react";

export default function CommentItemFragment(props: CommentItem.Props) {
    const {
        // This is the comment item contains the comment body, reactions, and other comment metadata
        comment,
        // This is the editor react component used to update a comment.
        editor,
        // These are the warnings related to the comment item
        warnings,
        // Boolean value to determine if the comment is in editing mode
        isEditing,
        // Get data for attachments related to the comment from Third Party Integrations or Vanilla Moderation tools
        attachmentsContent,
        // Boolean value to determine if the comment is by the original poster
        showOPTag,
        // Boolean value to determine if the comment is highlighted
        isHighlighted,
        // Callback fired when the reply button is clicked
        onReply,
        // Text of the reply button
        replyLabel,
        // Boolean value to determine if the comment should be hidden from the sessioned user
        isHidden,
    } = props;

    const { reactions, insertUser } = comment;

    let content = (
        <>
            {/* If there are any warnings for this comment, it will be displayed here */}
            <CommentItem.Warnings />
            <div className={"commentItemFragment__header"}>
                <CommentHeader {...props} />
            </div>
            <Components.UserContent
                // The comment body is sanitized by the server, it is safe to use here
                vanillaSanitizedHtml={comment.body}
            />
            <CommentItem.UserSignature user={insertUser} classNames={"commentItemFragment__signature"} />
            {/* Renders associated tickets for the comment (Vanilla and third party escalations) */}
            <CommentItem.Attachments />
            <div className={"commentItemFragment__footer"}>
                <CommentItem.CommentReactions reactions={reactions} />
                {onReply && (
                    <CommentItem.ReplyButton
                        onReply={onReply}
                        replyLabel={replyLabel}
                        className={"commentItemFragment__replyButton"}
                    />
                )}
            </div>
        </>
    );

    if (isEditing) {
        content = <CommentItem.CommentEditor />;
    }

    if (isHidden) {
        content = <CommentItem.IgnoredUserContent />;
    }

    return <div className={`commentItemFragment__root ${isHighlighted ? "highlighted" : ""}`}>{content}</div>;
}

function CommentHeader(props: CommentItem.Props) {
    const { comment, showOPTag } = props;
    const { insertUser } = comment;

    const currentUserSignedIn = Utils.useCurrentUserSignedIn();

    if (!insertUser) {
        return null;
    }

    return (
        <div className="commentHeader__root">
            <div className="commentHeader__userPhoto">
                {/* Moderation checkboxes used for bulk comment management */}
                <CommentItem.ModerationCheckBox />
                <Components.ProfileLink userFragment={insertUser}>
                    <Components.UserPhoto userInfo={insertUser} size={"medium"} />
                </Components.ProfileLink>
            </div>
            <div className={"commentHeader__author-lockup"}>
                <div className="commentHeader__userName">
                    <Components.ProfileLink userFragment={insertUser}>{insertUser.name}</Components.ProfileLink>
                </div>
                <div className="commentHeader__userTitle">
                    <Components.UserTitle user={insertUser} showOPTag={showOPTag} />
                </div>
            </div>
            <div className={"commentHeader__meta-lockup"}>
                <CommentItem.ContentItemPermalink />
                <CommentItem.AuthorBadges />
            </div>
            {currentUserSignedIn && (
                <div className={"commentItemFragment__action-lockup"}>
                    <CommentItem.OptionsMenu />
                </div>
            )}
        </div>
    );
}

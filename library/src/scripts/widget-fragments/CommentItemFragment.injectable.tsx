import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import React, { useState } from "react";
import { getMeta, t } from "@library/utility/appUtils";

import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { ContentItemActions } from "@vanilla/addon-vanilla/contentItem/ContentItemActions";
import { ContentItemPermalink } from "@vanilla/addon-vanilla/contentItem/ContentItemPermalink";
import { ICommentFragmentImplProps } from "@vanilla/addon-vanilla/comments/CommentItem";
import { IUserFragment } from "@library/@types/api/users";
import { Icon } from "@vanilla/icons";
import { ReportCountMeta } from "@vanilla/addon-vanilla/reporting/ReportCountMeta";
import UserContent from "@library/content/UserContent";
import { VanillaButtonProps } from "@library/widget-fragments/Components.injectable";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import { useCommentItemFragmentContext } from "@vanilla/addon-vanilla/comments/CommentItemFragmentContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

function UserSignature({ user, classNames }: { user: IUserFragment; classNames?: string }) {
    const signatureContent = user?.signature?.body;
    const hideMobileSignatures = getMeta("signatures.hideMobile", false);
    const showSignature = getMeta("signatures.enabled", false) && !hideMobileSignatures;

    return (
        <>
            {signatureContent && showSignature && (
                <div className={classNames}>
                    <UserContent vanillaSanitizedHtml={blessStringAsSanitizedHtml(signatureContent)} />
                </div>
            )}
        </>
    );
}

interface CommentReplyButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    onReply?: () => void;
    replyLabel?: string;
    buttonType?: VanillaButtonProps["buttonType"];
}

function ReplyButton(props: CommentReplyButtonProps) {
    const permissions = usePermissionsContext();

    const buttonPropsWithDefaults = {
        ...props,
        buttonType: props.buttonType ?? ButtonType.TEXT,
    };

    if (!permissions.hasPermission("comments.add")) {
        return null;
    }

    return (
        <Button onClick={() => props.onReply && props.onReply()} {...buttonPropsWithDefaults}>
            {t("Reply")}
        </Button>
    );
}

function OptionsMenu() {
    const commentProps = useCommentItemFragmentContext();
    const [ownVisible, setOwnVisible] = useState(false);

    const fallbackForEditor = (
        <>
            <ReportCountMeta
                countReports={commentProps.comment.reportMeta?.countReports}
                recordID={commentProps.comment.commentID}
                recordType="comment"
            />
            <DropDown
                name={t("Comment Options")}
                buttonContents={<Icon icon="options-menu" />}
                openDirection={DropDownOpenDirection.BELOW_LEFT}
                flyoutType={FlyoutType.LIST}
                isVisible={ownVisible}
                onVisibilityChange={(newVisibility) => setOwnVisible(newVisibility)}
            >
                <li style={{ padding: 8, textWrap: "pretty" }}>{t("Options for comments will appear here")}</li>
            </DropDown>
        </>
    );

    return <>{commentProps.options ?? fallbackForEditor}</>;
}

function CommentEditor() {
    const { editor } = useCommentItemFragmentContext();
    return <>{editor}</>;
}

function Warnings() {
    const { warnings } = useCommentItemFragmentContext();
    return <>{warnings}</>;
}

function Attachments() {
    const { attachmentsContent } = useCommentItemFragmentContext();
    return <>{attachmentsContent}</>;
}

function AuthorBadges() {
    const { additionalAuthorMeta } = useCommentItemFragmentContext();
    return <>{additionalAuthorMeta}</>;
}

function ModerationCheckBox() {
    const { checkBox } = useCommentItemFragmentContext();
    return <>{checkBox}</>;
}

function IgnoredUserContent() {
    const { visibilityHandlerComponent } = useCommentItemFragmentContext();
    return <>{visibilityHandlerComponent}</>;
}

const CommentItemFragmentInjectable = {
    CommentReactions: ContentItemActions,
    UserSignature,
    ContentItemPermalink,
    ReplyButton,
    OptionsMenu,
    CommentEditor,
    Warnings,
    Attachments,
    AuthorBadges,
    ModerationCheckBox,
    IgnoredUserContent,
};

namespace CommentItemFragmentInjectable {
    export interface Props
        extends Omit<ICommentFragmentImplProps, "content" | "user" | "userPhotoLocation" | "setIsEditing"> {}
}

export default CommentItemFragmentInjectable;

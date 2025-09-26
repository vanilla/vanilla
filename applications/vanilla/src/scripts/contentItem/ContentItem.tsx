/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { AcceptedAnswerComment, IAcceptedAnswerProps } from "@library/suggestedAnswers/AcceptedAnswerComment";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";

import { BorderType } from "@library/styles/styleHelpersBorders";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CollapsableContent } from "@library/content/CollapsableContent";
import { ContentItemActions } from "@vanilla/addon-vanilla/contentItem/ContentItemActions";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { ContentItemHeader } from "@vanilla/addon-vanilla/contentItem/ContentItemHeader";
import FlexSpacer from "@library/layout/FlexSpacer";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { PageBox } from "@library/layout/PageBox";
import ProfileLink from "@library/navigation/ProfileLink";
import React from "react";
import UserContent from "@library/content/UserContent";
import type { VanillaSanitizedHtml } from "@vanilla/dom-utils";
import { getMeta } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n";
import { useContentItemContext } from "./ContentItemContext";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

export interface IContentItemProps {
    warnings?: React.ReactNode;
    content: VanillaSanitizedHtml;
    editor?: React.ReactNode;
    user: IUserFragment;
    userPhotoLocation?: "header" | "left";
    collapsed?: boolean;
    boxOptions?: Partial<IBoxOptions>;
    options?: React.ReactNode;
    actions?: React.ReactNode;
    reactions?: IReaction[];
    attachmentsContent?: React.ReactNode;
    suggestionContent?: IAcceptedAnswerProps;
    onReply?: () => void;
    showOPTag?: boolean;
    isPreview?: boolean;
    isHighlighted?: boolean;
    isClosed?: boolean;
    categoryID: ICategory["categoryID"];
    readOnly?: boolean;
    replyLabel?: string;
    additionalAuthorMeta?: React.ReactNode;
    checkBox?: React.ReactNode;

    // these two are to handle show/hide thread item, e.g. for posts from ignored users
    visibilityHandlerComponent?: React.ReactNode;
    isHidden?: boolean;
}

function getDomID(recordType: string, recordID: string | number) {
    const prefix = recordType.charAt(0).toUpperCase() + recordType.slice(1, recordType.length).toLowerCase();
    return `${prefix}_${recordID}`;
}

export function ContentItem(props: IContentItemProps) {
    const {
        warnings,
        content,
        user,
        userPhotoLocation,
        collapsed,
        suggestionContent,
        showOPTag,
        isHighlighted,
        visibilityHandlerComponent,
        isHidden,
    } = props;
    const { recordType, recordID } = useContentItemContext();
    const domID = getDomID(recordType, recordID);
    const device = useDevice();
    const isMobile = [Devices.XS, Devices.MOBILE].includes(device);
    const permissions = usePermissionsContext();

    const headerHasUserPhoto = userPhotoLocation === "header";

    const classes = ContentItemClasses.useAsHook(headerHasUserPhoto);

    let userContent = suggestionContent ? (
        <AcceptedAnswerComment {...suggestionContent} className={classes.userContent} />
    ) : (
        <UserContent vanillaSanitizedHtml={content} className={classes.userContent} />
    );

    const signatureContent = user.signature?.body;
    const hideMobileSignatures = getMeta("signatures.hideMobile", false);
    const isHiddenMobile = hideMobileSignatures && isMobile;
    const showSignature = getMeta("signatures.enabled", false) && !isHiddenMobile;
    let signature: React.ReactNode = null;

    if (signatureContent && showSignature) {
        signature = (
            <div className={classes.signature}>
                <UserContent vanillaSanitizedHtml={signatureContent} />
            </div>
        );
    }

    if (collapsed) {
        userContent = (
            <CollapsableContent maxHeight={200} overshoot={250}>
                {userContent}
            </CollapsableContent>
        );
    }

    let result = (
        <div className={classes.threadItemContainer}>
            <ContentItemHeader
                options={props.options}
                user={user}
                excludePhoto={!headerHasUserPhoto}
                showOPTag={showOPTag}
                categoryID={props.categoryID}
                isClosed={props.isClosed}
                readOnly={props.readOnly}
                additionalAuthorMeta={props.additionalAuthorMeta}
                checkBox={props.checkBox}
            />
            {props.editor || (
                <>
                    {warnings}
                    {userContent}
                    {signature}
                    {props.actions}
                    {!props.readOnly && (
                        <div className={classes.footerWrapper}>
                            {props.reactions && <ContentItemActions reactions={props.reactions} />}
                            <FlexSpacer actualSpacer />
                            {props.onReply && permissions.hasPermission("comments.add") && (
                                <Button
                                    className={classes.replyButton}
                                    onClick={() => props.onReply && props.onReply()}
                                    buttonType={ButtonTypes.TEXT}
                                >
                                    {props.replyLabel ?? t("Reply")}
                                </Button>
                            )}
                        </div>
                    )}
                    {!!props.attachmentsContent && (
                        <div className={classes.attachmentsContentWrapper}>{props.attachmentsContent}</div>
                    )}
                </>
            )}
        </div>
    );

    if (!headerHasUserPhoto) {
        result = (
            <div className={classes.resultWrapper}>
                <ProfileLink userFragment={user}>
                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                </ProfileLink>
                <PageBox>{result}</PageBox>
            </div>
        );
    }

    if (isHighlighted) {
        result = (
            <PageBox
                options={{
                    borderType: BorderType.BORDER,
                    border: {
                        color: globalVariables().mainColors.primary,
                    },
                    background: {
                        color: globalVariables().mixPrimaryAndBg(0.1),
                    },
                }}
            >
                {result}
            </PageBox>
        );
    }

    return (
        <PageBox
            id={domID}
            options={{
                borderType: BorderType.SEPARATOR_BETWEEN,
                ...props.boxOptions,
            }}
        >
            {visibilityHandlerComponent}
            {!isHidden && result}
        </PageBox>
    );
}

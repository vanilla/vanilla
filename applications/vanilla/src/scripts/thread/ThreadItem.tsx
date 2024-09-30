/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { PageBox } from "@library/layout/PageBox";
import ProfileLink from "@library/navigation/ProfileLink";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { AcceptedAnswerComment, IAcceptedAnswerProps } from "@library/suggestedAnswers/AcceptedAnswerComment";
import ThreadItemClasses from "@vanilla/addon-vanilla/thread/ThreadItem.classes";
import ThreadItemActions from "@vanilla/addon-vanilla/thread/ThreadItemActions";
import { ThreadItemHeader } from "@vanilla/addon-vanilla/thread/ThreadItemHeader";
import React from "react";
import { useThreadItemContext } from "./ThreadItemContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n";

interface IProps {
    content: string;
    editor?: React.ReactNode;
    user: IUserFragment;
    userPhotoLocation: "header" | "left";
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
}

function getThreadItemID(recordType: string, recordID: string | number) {
    const prefix = recordType.charAt(0).toUpperCase() + recordType.slice(1, recordType.length).toLowerCase();
    return `${prefix}_${recordID}`;
}

export function ThreadItem(props: IProps) {
    const { content, user, userPhotoLocation, collapsed, suggestionContent, showOPTag, isHighlighted } = props;

    const headerHasUserPhoto = userPhotoLocation === "header";

    const classes = ThreadItemClasses(headerHasUserPhoto);

    let userContent = suggestionContent ? (
        <AcceptedAnswerComment {...suggestionContent} className={classes.userContent} />
    ) : (
        <UserContent content={content} className={classes.userContent} />
    );

    if (collapsed) {
        userContent = (
            <CollapsableContent maxHeight={200} overshoot={250}>
                {userContent}
            </CollapsableContent>
        );
    }

    const { recordType, recordID } = useThreadItemContext();

    const itemID = getThreadItemID(recordType, recordID);

    let result = (
        <>
            <ThreadItemHeader
                options={props.options}
                user={user}
                excludePhoto={!headerHasUserPhoto}
                showOPTag={showOPTag}
            />
            {props.editor || (
                <>
                    {userContent}
                    {props.actions}
                    <div className={classes.footerWrapper}>
                        {props.reactions && <ThreadItemActions reactions={props.reactions} />}
                        {props.onReply && (
                            <Button onClick={() => props.onReply && props.onReply()} buttonType={ButtonTypes.TEXT}>
                                {t("Reply")}
                            </Button>
                        )}
                    </div>
                    {!!props.attachmentsContent && (
                        <div className={classes.attachmentsContentWrapper}>{props.attachmentsContent}</div>
                    )}
                </>
            )}
        </>
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
            id={itemID}
            options={{
                borderType: BorderType.SEPARATOR_BETWEEN,
                ...props.boxOptions,
            }}
        >
            {result}
        </PageBox>
    );
}

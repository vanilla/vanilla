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
import React, { useEffect, useMemo } from "react";
import { useThreadItemContext } from "./ThreadItemContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { globalVariables } from "@library/styles/globalStyleVars";
import { t } from "@vanilla/i18n";
import { useLocation } from "react-router";
import qs from "qs";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import {
    getDraftParentIDAndPath,
    getThreadItemByMatchingPathOrID,
    isThreadReply,
} from "@vanilla/addon-vanilla/thread/threadUtils";
import { getMeta } from "@library/utility/appUtils";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { DRAFT_PARENT_ID_AND_PATH_KEY } from "@vanilla/addon-vanilla/thread/components/ThreadCommentEditor";
import { getLocalStorageOrDefault } from "@vanilla/react-utils";
import FlexSpacer from "@library/layout/FlexSpacer";

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
    isClosed?: boolean;
    categoryID: ICategory["categoryID"];
    readOnly?: boolean;
    replyLabel?: string;
}

function getThreadItemID(recordType: string, recordID: string | number) {
    const prefix = recordType.charAt(0).toUpperCase() + recordType.slice(1, recordType.length).toLowerCase();
    return `${prefix}_${recordID}`;
}

export function ThreadItem(props: IProps) {
    const { content, user, userPhotoLocation, collapsed, suggestionContent, showOPTag, isHighlighted } = props;
    const { recordType, recordID, threadStyle } = useThreadItemContext();
    const itemID = getThreadItemID(recordType, recordID);

    // when we have draftID in the URL, we want to show the draft content
    const { showReplyForm, threadStructure, discussion } = useCommentThread();
    const queryFromUrl = useLocation().search;
    const draftInUrl =
        queryFromUrl.includes("hasDraft") && qs.parse(queryFromUrl, { ignoreQueryPrefix: true }).hasDraft;

    const threadItemFromThreadStructure = useMemo(() => {
        return getThreadItemByMatchingPathOrID(threadStructure, undefined, recordID);
    }, [threadStructure]);

    const draftParentIDAndPath = useMemo(() => {
        return getDraftParentIDAndPath(discussion.discussionID);
    }, [threadStructure, discussion.discussionID]);

    // coming here through a link with a draft in the URL
    useEffect(() => {
        if (threadStyle === "nested" && draftInUrl && isHighlighted) {
            if (
                draftParentIDAndPath &&
                !threadItemFromThreadStructure.children.find((childItem) => isThreadReply(childItem))
            ) {
                showReplyForm(threadItemFromThreadStructure);
            } else if (!draftParentIDAndPath) {
                const fullUrl = window.location.href;
                const newUrl = fullUrl.split("?hasDraft=true").join("");
                window.history.replaceState(null, "", newUrl);
            }
        }
    }, [draftInUrl, isHighlighted, threadStyle, threadItemFromThreadStructure, draftParentIDAndPath]);

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

    let result = (
        <div className={classes.threadItemContainer}>
            <ThreadItemHeader
                options={props.options}
                user={user}
                excludePhoto={!headerHasUserPhoto}
                showOPTag={showOPTag}
                categoryID={props.categoryID}
                isClosed={props.isClosed}
                readOnly={props.readOnly}
            />
            {props.editor || (
                <>
                    {userContent}
                    {props.actions}
                    {!props.readOnly && (
                        <div className={classes.footerWrapper}>
                            {props.reactions && <ThreadItemActions reactions={props.reactions} />}
                            <FlexSpacer actualSpacer />
                            {props.onReply && (
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

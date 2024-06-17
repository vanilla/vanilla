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
import ThreadItemActions from "@vanilla/addon-vanilla/thread/ThreadItemActions";
import { ThreadItemHeader } from "@vanilla/addon-vanilla/thread/ThreadItemHeader";
import React from "react";
import ThreadItemClasses from "@vanilla/addon-vanilla/thread/ThreadItem.classes";
import { useThreadItemContext } from "./ThreadItemContext";

interface IProps {
    content: string;
    editor?: React.ReactNode;
    contentMeta: React.ReactNode;
    user: IUserFragment;
    userPhotoLocation: "header" | "left";
    collapsed?: boolean;
    boxOptions?: Partial<IBoxOptions>;
    options?: React.ReactNode;
    actions?: React.ReactNode;
    reactions?: IReaction[];
    attachmentsContent?: React.ReactNode;
}

function getThreadItemID(recordType: string, recordID: string | number) {
    const prefix = recordType.charAt(0).toUpperCase() + recordType.slice(1, recordType.length).toLowerCase();
    return `${prefix}_${recordID}`;
}

export function ThreadItem(props: IProps) {
    const { content, contentMeta, user, userPhotoLocation, collapsed } = props;

    const headerHasUserPhoto = userPhotoLocation === "header";

    const classes = ThreadItemClasses(headerHasUserPhoto);

    let userContent = <UserContent content={content} className={classes.userContent} />;
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
                metas={contentMeta}
                excludePhoto={!headerHasUserPhoto}
            />
            {props.editor || (
                <>
                    {userContent}
                    {props.actions}
                    <ThreadItemActions reactions={props.reactions} />
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

    return (
        <PageBox id={itemID} options={{ borderType: BorderType.SEPARATOR_BETWEEN, ...props.boxOptions }}>
            {result}
        </PageBox>
    );
}

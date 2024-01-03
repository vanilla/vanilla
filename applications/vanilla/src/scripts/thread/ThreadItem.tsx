/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { IUserFragment } from "@library/@types/api/users";
import { CollapsableContent } from "@library/content/CollapsableContent";
import UserContent from "@library/content/UserContent";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { PageBox } from "@library/layout/PageBox";
import ProfileLink from "@library/navigation/ProfileLink";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { IThreadItemContext, ThreadItemContextProvider } from "@vanilla/addon-vanilla/thread/ThreadItemContext";
import { ThreadItemHeader } from "@vanilla/addon-vanilla/thread/ThreadItemHeader";
import React from "react";

interface IProps extends IThreadItemContext {
    content: string;
    editor?: React.ReactNode;
    contentMeta: React.ReactNode;
    user: IUserFragment;
    userPhotoLocation: "header" | "left";
    collapsed?: boolean;
    boxOptions?: Partial<IBoxOptions>;
    options?: React.ReactNode;
    actions?: React.ReactNode;
}

export function ThreadItem(props: IProps) {
    const { content, contentMeta, user, userPhotoLocation, collapsed } = props;

    const headerHasUserPhoto = userPhotoLocation === "header";

    let userContent = <UserContent content={content} className={css({ paddingTop: headerHasUserPhoto ? 12 : 2 })} />;
    if (collapsed) {
        userContent = (
            <CollapsableContent maxHeight={200} overshoot={250}>
                {userContent}
            </CollapsableContent>
        );
    }

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
                </>
            )}
        </>
    );

    if (!headerHasUserPhoto) {
        result = (
            <div style={{ display: "flex", gap: 12 }}>
                <ProfileLink userFragment={user}>
                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                </ProfileLink>
                <PageBox className={css({})}>{result}</PageBox>
            </div>
        );
    }

    return (
        <ThreadItemContextProvider recordType={props.recordType} recordID={props.recordID}>
            <PageBox options={{ borderType: BorderType.SEPARATOR_BETWEEN, ...props.boxOptions }}>{result}</PageBox>
        </ThreadItemContextProvider>
    );
}

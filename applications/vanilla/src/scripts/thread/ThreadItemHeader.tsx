/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { threadItemHeaderClasses } from "@vanilla/addon-vanilla/thread/ThreadItemHeader.classes";
import { UserTitle } from "@library/content/UserTitle";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { MetaItem, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import React from "react";

/**
 * Contains, user avatar, name, and optionnal meta data
 */

interface IUserLabel {
    user: IUserFragment;
    metas: React.ReactNode;
    excludePhoto?: boolean;
    options?: React.ReactNode;
}

export function ThreadItemHeader(props: IUserLabel) {
    const { user, excludePhoto, options } = props;

    const classes = threadItemHeaderClasses();

    const nameMeta = (
        <MetaItem>
            <ProfileLink className={classes.userName} userFragment={user} isUserCard />
        </MetaItem>
    );
    const titleMeta = (
        <MetaItem>
            <UserTitle user={user} />
        </MetaItem>
    );

    if (excludePhoto) {
        // Display as a standard meta row.
        return (
            <Metas>
                {nameMeta}
                {titleMeta}
                {props.metas}
            </Metas>
        );
    }

    return (
        <div className={classes.root}>
            <ProfileLink className={classes.userName} userFragment={user} isUserCard aria-hidden="true">
                <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
            </ProfileLink>
            <div className={classes.main}>
                <Metas>
                    {nameMeta}
                    {titleMeta}
                </Metas>
                {props.metas && <Metas>{props.metas}</Metas>}
            </div>
            {props.options}
        </div>
    );
}

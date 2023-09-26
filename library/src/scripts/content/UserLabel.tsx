/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IUserFragment } from "@library/@types/api/users";
import { userLabelClasses } from "@library/content/UserLabel.classes";
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
}

export function UserLabel(props: IUserLabel) {
    const { user } = props;

    const classes = userLabelClasses();

    return (
        <div className={classes.root}>
            <ProfileLink className={classes.userName} userFragment={user} isUserCard aria-hidden="true">
                <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
            </ProfileLink>
            <div className={classes.main}>
                <Metas>
                    <MetaItem>
                        <ProfileLink className={classes.userName} userFragment={user} isUserCard />
                    </MetaItem>
                    <MetaItem>
                        <UserTitle user={user} />
                    </MetaItem>
                </Metas>
                {props.metas && <Metas>{props.metas}</Metas>}
            </div>
        </div>
    );
}

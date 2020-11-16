/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUserFragment } from "@vanilla/library/src/scripts/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import NumberFormatted from "@library/content/NumberFormatted";
import DateTime from "@library/content/DateTime";
import { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
import { useLayout } from "@library/layout/LayoutContext";
import ProfileLink from "@library/navigation/ProfileLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { memberListClasses } from "@dashboard/components/MemberList.styles";

interface IProps {
    userCardInfo?: IUserCardInfo;
}

interface IInfoProps {
    countPost: number;
    dateLastActive: string;
    isCompact: boolean;
}

interface IUserProps {
    userInfo: IUserFragment;
    countPost: number;
}

export default function Member(props: IProps) {
    const { userCardInfo } = props;
    const { isCompact } = useLayout();

    if (!userCardInfo) {
        return null;
    }
    const userInfo: IUserFragment = {
        userID: userCardInfo.userID,
        name: userCardInfo.name,
        photoUrl: userCardInfo.photoUrl,
        dateLastActive: userCardInfo.dateLastActive || null,
        label: userCardInfo.label,
    };

    const classes = memberListClasses();
    return (
        <tr className={classes.root}>
            <td className={classNames(classes.cell, classes.isLeft, classes.mainColumn)}>
                <span className={classes.user}>
                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={userInfo} />
                    <span
                        className={classNames(classes.mainContent, {
                            [classes.mainContentCompact]: isCompact,
                        })}
                    >
                        <span className={classes.align}>
                            <ProfileLink
                                className={classNames(classes.profileLink)}
                                username={userInfo.name}
                                userID={userInfo.userID}
                                buttonType={ButtonTypes.TEXT}
                            >
                                {userInfo.name}
                            </ProfileLink>
                            {!isCompact && userInfo.label && (
                                <span className={classes.label} dangerouslySetInnerHTML={{ __html: userInfo.label }} />
                            )}
                        </span>
                        {isCompact && (
                            <span className={classNames({ [classes.postsUserSection]: isCompact })}>
                                <NumberFormatted value={userCardInfo.countComments || 0} />
                                {` ${t("Posts")}`}
                            </span>
                        )}
                    </span>
                </span>
            </td>
            {!isCompact && (
                <td className={classNames(classes.cell, classes.posts, classes.postsColumn)}>
                    <span className={classes.minHeight}>
                        <NumberFormatted value={userCardInfo.countComments || 0} />
                    </span>
                </td>
            )}
            {!isCompact && (
                <td className={classNames(classes.cell, classes.date, classes.lastActiveColumn)}>
                    <span className={classes.minHeight}>
                        <DateTime timestamp={userCardInfo.dateJoined || ""} />
                    </span>
                </td>
            )}
            <td className={classNames(classes.cell, classes.date, classes.isRight, classes.lastActiveColumn)}>
                <span className={classes.minHeight}>
                    <DateTime timestamp={userCardInfo.dateLastActive || ""} />
                </span>
            </td>
        </tr>
    );
}

/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUser } from "@library/@types/api/users";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import NumberFormatted from "@library/content/NumberFormatted";
import { useSection } from "@library/layout/LayoutContext";
import ProfileLink from "@library/navigation/ProfileLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { memberListClasses } from "@dashboard/components/MemberList.styles";
import DateTime from "@library/content/DateTime";

export interface IMemberResultProps {
    // We always have userInfo on these member queries.
    userInfo?: IUser;
}

export default function Member(props: IMemberResultProps) {
    const user = props.userInfo;
    const { isCompact } = useSection();

    if (!user) {
        return <></>;
    }

    const classes = memberListClasses();
    return (
        <tr className={classes.root}>
            <td className={classNames(classes.cell, classes.isLeft, classes.mainColumn)}>
                <span className={classes.user}>
                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                    <span
                        className={classNames(classes.mainContent, {
                            [classes.mainContentCompact]: isCompact,
                        })}
                    >
                        <span className={classes.align}>
                            <ProfileLink
                                className={classNames(classes.profileLink)}
                                userFragment={user}
                                buttonType={ButtonTypes.TEXT}
                            >
                                {user.name}
                            </ProfileLink>
                            {!isCompact && user.label && (
                                <span className={classes.label} dangerouslySetInnerHTML={{ __html: user.label }} />
                            )}
                        </span>
                        {isCompact && (
                            <span className={classNames({ [classes.postsUserSection]: isCompact })}>
                                <NumberFormatted value={user.countComments || 0} />
                                {` ${t("Posts")}`}
                            </span>
                        )}
                    </span>
                </span>
            </td>
            {!isCompact && (
                <td className={classNames(classes.cell, classes.posts, classes.postsColumn)}>
                    <span className={classes.minHeight}>
                        <NumberFormatted value={user.countPosts || 0} />
                    </span>
                </td>
            )}
            {!isCompact && (
                <td className={classNames(classes.cell, classes.date, classes.lastActiveColumn)}>
                    <span className={classes.minHeight}>
                        <DateTime timestamp={user.dateInserted || ""} />
                    </span>
                </td>
            )}
            <td className={classNames(classes.cell, classes.date, classes.isRight, classes.lastActiveColumn)}>
                <span className={classes.minHeight}>
                    <DateTime timestamp={user.dateLastActive || ""} />
                </span>
            </td>
        </tr>
    );
}

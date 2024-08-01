/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUser } from "@library/@types/api/users";
import { formatDateStringIgnoringTimezone } from "@library/editProfileFields/utils";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import classNames from "classnames";
import { t } from "@vanilla/i18n";
import NumberFormatted from "@library/content/NumberFormatted";
import { useSection } from "@library/layout/LayoutContext";
import ProfileLink from "@library/navigation/ProfileLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { memberListClasses } from "@dashboard/components/MemberList.styles";
import DateTime from "@library/content/DateTime";
import { DateElement, isSameDate } from "@library/content/DateTimeHelpers";
import { IResult } from "@library/result/Result";
import { logError } from "@vanilla/utils";

export interface IMemberResult extends IResult {
    // We always have userInfo on these member queries.
    userInfo?: IUser;
}

export default function Member(props: IMemberResult) {
    const user = props.userInfo;
    const { isCompact } = useSection();

    if (!user) {
        return <></>;
    }

    const classes = memberListClasses();

    const safelyFormatDate = (dateString: string | null): string => {
        if (dateString) {
            try {
                const nowDate = new Date();
                const date = new Date(dateString);
                const isSameDay = isSameDate(date, nowDate, DateElement.DAY);
                return isSameDay ? dateString : formatDateStringIgnoringTimezone(dateString);
            } catch (e) {
                logError(e);
                return "";
            }
        }
        return "";
    };
    const safeData = safelyFormatDate(user.dateLastActive);
    return (
        <tr className={classes.root}>
            <td className={classNames(classes.cell, classes.isLeft, classes.mainColumn)}>
                <span className={classes.user}>
                    <ProfileLink userFragment={user} isUserCard>
                        <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                    </ProfileLink>
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
                        <DateTime timestamp={safelyFormatDate(user.dateInserted)} />
                    </span>
                </td>
            )}
            <td className={classNames(classes.cell, classes.date, classes.isRight, classes.lastActiveColumn)}>
                <span className={classes.minHeight}>{safeData !== "" && <DateTime timestamp={safeData} />}</span>
            </td>
        </tr>
    );
}

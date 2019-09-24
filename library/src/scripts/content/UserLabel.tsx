import React from "react";
import { IUser, IUserFragment } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";
import { makeProfileUrl } from "@library/utility/appUtils";
import { userLabelClasses } from "@library/content/userLabelStyles";
import { Roles } from "@library/content/Roles";
import classNames from "classnames";
import DateTime from "@library/content/DateTime";

/**
 * Contains, user avatar, name, and optionnal meta data
 */

interface IUserLabel {
    user: IUser;
    date?: string;
    dateLink?: string;
    displayOptions?: {
        showRole?: boolean;
    };
}

export function UserLabel(props: IUserLabel) {
    const { user, date, dateLink, displayOptions = {} } = props;
    const { showRole } = displayOptions;

    const userUrl = makeProfileUrl(user.name);
    const classes = userLabelClasses();
    return (
        <article className={classes.root}>
            <SmartLink to={userUrl} className={classes.avatarLink}>
                <img src={user.photoUrl} alt={user.name} className={classes.avatar} />
            </SmartLink>
            <div className={classes.main}>
                <div className={classes.topRow}>
                    <SmartLink to={userUrl} className={classes.userName}>
                        {user.name}
                    </SmartLink>
                    {showRole && user.roles && <Roles roles={user.roles} wrapper={false} />}
                </div>
                {date && dateLink && (
                    <div className={classes.bottomRow}>
                        <SmartLink to={dateLink} className={classes.dateLink}>
                            <DateTime timestamp={date} className={classes.date} />
                        </SmartLink>
                    </div>
                )}
            </div>
        </article>
    );
}

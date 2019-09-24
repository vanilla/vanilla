import React from "react";
import { IUser, IUserFragment, IUserFragmentAndRoles, IUserRoles } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";
import { makeProfileUrl } from "@library/utility/appUtils";
import { userLabelClasses } from "@library/content/userLabelStyles";
import { Roles } from "@library/content/Roles";
import classNames from "classnames";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/styles/metasStyles";

/**
 * Contains, user avatar, name, and optionnal meta data
 */

interface IUserLabel extends IUserRoles {
    user: IUserFragment | IUserFragmentAndRoles;
    date?: string;
    dateLink?: string;
    category?: string;
    categoryLink?: string;
    displayOptions?: {
        showRole?: boolean;
        showCategory?: boolean;
    };
}

export function UserLabel(props: IUserLabel) {
    const { user, date, dateLink, displayOptions = {}, category, categoryLink } = props;
    const { showRole = true, showCategory = false } = displayOptions;

    const userUrl = makeProfileUrl(user.name);
    const classes = userLabelClasses();
    const classesMeta = metasClasses();
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
                    {showRole && "roles" in user && <Roles roles={user.roles} wrapper={false} />}
                </div>
                {date && (
                    <div className={classes.bottomRow}>
                        {dateLink ? (
                            <SmartLink to={dateLink} className={classes.dateLink}>
                                <DateTime timestamp={date} className={classes.date} />
                            </SmartLink>
                        ) : (
                            <DateTime timestamp={date} className={classes.date} />
                        )}
                    </div>
                )}
                {showCategory && category && categoryLink && (
                    <SmartLink to={categoryLink} className={classesMeta.meta}>
                        {category}
                    </SmartLink>
                )}
            </div>
        </article>
    );
}

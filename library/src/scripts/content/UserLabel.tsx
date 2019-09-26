import React from "react";
import { IUser, IUserFragment, IUserFragmentAndRoles, IUserRoles } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";
import { makeProfileUrl } from "@library/utility/appUtils";
import { userLabelClasses } from "@library/content/userLabelStyles";
import { Roles } from "@library/content/Roles";
import classNames from "classnames";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/styles/metasStyles";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";

/**
 * Contains, user avatar, name, and optionnal meta data
 */

interface IUserLabel {
    user: IUserFragment;
    date?: string;
    dateLink?: string;
    category?: ICategoryFragment;
    displayOptions?: {
        showRole?: boolean;
        showCategory?: boolean;
    };
    compact?: boolean;
}

export function UserLabel(props: IUserLabel) {
    const { user, date, dateLink, displayOptions = {}, category, compact = false } = props;
    const { showRole = true, showCategory = false } = displayOptions;

    const userUrl = makeProfileUrl(user.name);
    const classes = userLabelClasses();
    const classesMeta = metasClasses();

    const dateComponent = dateLink ? (
        <SmartLink to={dateLink} className={classNames(classesMeta.meta)}>
            <DateTime timestamp={date} className={classNames(classesMeta.metaStyle)} />
        </SmartLink>
    ) : (
        <DateTime timestamp={date} className={classNames(classesMeta.meta)} />
    );

    const categoryComponent =
        showCategory && category ? (
            <SmartLink to={category.url} className={classesMeta.meta}>
                {category.name}
            </SmartLink>
        ) : null;

    if (compact) {
        return (
            <div className={classesMeta.root}>
                <SmartLink to={userUrl} className={classNames(classes.userName, classes.isCompact, classesMeta.meta)}>
                    {user.name}
                </SmartLink>
                {dateComponent}
                {categoryComponent}
            </div>
        );
    } else {
        return (
            <article className={classes.root}>
                <SmartLink to={userUrl} className={classes.avatarLink}>
                    <img src={user.photoUrl} alt={user.name} className={classes.avatar} />
                </SmartLink>
                <div className={classes.main}>
                    <div className={classNames(classesMeta.root, "isFlexed", classes.topRow)}>
                        <SmartLink to={userUrl} className={classNames(classes.userName, classesMeta.meta)}>
                            {user.name}
                        </SmartLink>
                        {showRole && user.title && <Roles roles={[{ roleID: 0, name: user.title }]} wrapper={false} />}
                    </div>
                    <div className={classNames(classesMeta.root, "isFlexed", classes.bottomRow)}>
                        {dateComponent}
                        {categoryComponent}
                    </div>
                </div>
            </article>
        );
    }
}

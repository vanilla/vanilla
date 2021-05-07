import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";
import { makeProfileUrl } from "@library/utility/appUtils";
import { userLabelClasses } from "@library/content/userLabelStyles";
import { Roles } from "@library/content/Roles";
import classNames from "classnames";
import { metasClasses } from "@library/metas/Metas.styles";
import { ICategoryFragment } from "@vanilla/addon-vanilla/@types/api/categories";
import DateTime from "@library/content/DateTime";

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
    fixLineHeight?: boolean;
}

export function UserLabel(props: IUserLabel) {
    const { user, date, dateLink, displayOptions = {}, category, compact = false, fixLineHeight = false } = props;
    const { showRole = true, showCategory = false } = displayOptions;

    const userUrl = makeProfileUrl(user.name);
    const classes = userLabelClasses();
    const classesMeta = metasClasses();

    const dateComponent =
        dateLink && date ? (
            <SmartLink to={dateLink} className={classNames(classesMeta.meta)}>
                <DateTime timestamp={date} className={classNames(classesMeta.metaStyle)} />
            </SmartLink>
        ) : date ? (
            <DateTime timestamp={date} className={classNames(classesMeta.meta)} />
        ) : null;

    const categoryComponent =
        showCategory && category ? (
            <SmartLink to={category.url} className={classesMeta.meta}>
                {category.name}
            </SmartLink>
        ) : null;

    if (compact) {
        return (
            <div className={classNames(classes.compact, classesMeta.root, { [classes.fixLineHeight]: fixLineHeight })}>
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
                    <img src={user.photoUrl} alt={user.name} className={classes.avatar} loading="lazy" />
                </SmartLink>
                <div className={classes.main}>
                    <div className={classNames(classesMeta.root, "isFlexed", classes.topRow)}>
                        <SmartLink to={userUrl} className={classNames(classes.userName, classesMeta.meta)}>
                            {user.name}
                        </SmartLink>
                        {showRole && user.label && <Roles roles={[{ roleID: 0, name: user.label }]} wrapper={false} />}
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

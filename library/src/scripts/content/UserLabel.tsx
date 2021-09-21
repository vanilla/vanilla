import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import SmartLink from "@library/routing/links/SmartLink";
import { makeProfileUrl } from "@library/utility/appUtils";
import { userLabelClasses } from "@library/content/userLabelStyles";
import { Roles } from "@library/content/Roles";
import classNames from "classnames";
import { metasClasses } from "@library/metas/Metas.styles";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import DateTime from "@library/content/DateTime";
import { MetaItem, MetaLink } from "@library/metas/Metas";

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

    const dateComponent = (
        <MetaItem>
            {dateLink && date ? (
                <SmartLink to={dateLink}>
                    <DateTime timestamp={date} />
                </SmartLink>
            ) : date ? (
                <DateTime timestamp={date} />
            ) : null}
        </MetaItem>
    );
    const categoryComponent = showCategory && category ? <MetaLink to={category.url}>{category.name}</MetaLink> : null;

    if (compact) {
        return (
            <div className={classNames(classes.compact, classesMeta.root, { [classes.fixLineHeight]: fixLineHeight })}>
                <MetaLink to={userUrl} className={classNames(classes.userName, classes.isCompact)}>
                    {user.name}
                </MetaLink>
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
                        <MetaLink to={userUrl} className={classes.userName}>
                            {user.name}
                        </MetaLink>
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

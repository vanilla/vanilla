/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { badgeClasses } from "@library/badge/Badge.classes";
import Count from "@library/content/Count";

export interface IBadge {
    name: string;
    url: string;
    photoUrl: string;
    count?: number;
}

export function Badge(props: IBadge) {
    const { name, photoUrl, url, count } = props;

    const classes = badgeClasses();
    return (
        <SmartLink to={url} title={name} className={classes.link}>
            <div className={count && count > 0 ? classes.itemHasCount : ""}>
                <img alt={name} src={photoUrl} className={classes.image} />
                {(count ?? 0) > 0 && (
                    <Count label={name} count={count} className={classes.count} useFormatted={true} useMax={false} />
                )}
            </div>
        </SmartLink>
    );
}

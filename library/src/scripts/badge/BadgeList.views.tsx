/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Badge, IBadge } from "@library/badge/Badge";
import { badgeListClasses } from "@library/badge/Badge.classes";

export interface IProps {
    items: IBadge[];
    keyID?: string;
}

export function BadgeListView(props: IProps) {
    const { items } = props;

    const classes = badgeListClasses();

    return (
        <ul className={classes.list}>
            {items.map((item, index) => (
                <li key={props?.keyID && item[props.keyID] ? item[props.keyID] : index} className={classes.listItem}>
                    <Badge {...item} />
                </li>
            ))}
        </ul>
    );
}

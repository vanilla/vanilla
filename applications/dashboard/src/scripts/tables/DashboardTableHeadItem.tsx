/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

export enum TableColumnSize {
    XS = "column-xs",
    SM = "column-sm",
    MD = "column-md",
    LG = "column-lg",
    XL = "column-xl",
}

interface IProps {
    size?: TableColumnSize;
    children: React.ReactNode;
    noCollapse?: boolean;
}

export function DashboardTableHeadItem(props: IProps) {
    const size = props.size || TableColumnSize.SM;
    return (
        <th className={size} data-tj-ignore={props.noCollapse ? true : undefined}>
            {props.children}
        </th>
    );
}

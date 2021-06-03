/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { PropsWithChildren } from "react";
import thumbnailGridClasses from "@dashboard/components/ThumbnailGrid.classes";

export function ThumbnailGrid(props: PropsWithChildren<{}>) {
    const classes = thumbnailGridClasses();
    return <div className={classes.grid}>{props.children}</div>;
}

export function ThumbnailGridItem(props: PropsWithChildren<{}>) {
    const classes = thumbnailGridClasses();
    return <div className={classes.gridItem}>{props.children}</div>;
}

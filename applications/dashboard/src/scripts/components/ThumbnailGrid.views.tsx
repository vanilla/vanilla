/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { PropsWithChildren } from "react";
import thumbnailGridClasses from "@dashboard/components/ThumbnailGrid.classes";
import { cx } from "@emotion/css";

export function ThumbnailGrid(props: PropsWithChildren<{ className?: string }>) {
    const classes = thumbnailGridClasses();
    return <div className={cx(classes.grid, props.className)}>{props.children}</div>;
}

export function ThumbnailGridItem(props: PropsWithChildren<{}>) {
    const classes = thumbnailGridClasses();
    return <div className={classes.gridItem}>{props.children}</div>;
}

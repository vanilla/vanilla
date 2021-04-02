/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ResponsiveImage } from "@library/content/ResponsiveImage";
import { listItemMediaClasses } from "@library/lists/ListItemMedia.styles";
import React from "react";

interface IProps extends React.HTMLAttributes<HTMLImageElement> {
    src: string;
    alt: string;
}

export function ListItemMedia(props: IProps) {
    const classes = listItemMediaClasses();
    return <ResponsiveImage className={classes.mediaItem} src={props.src} alt={props.alt} />;
}

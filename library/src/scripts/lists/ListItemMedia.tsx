/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { ResponsiveImage } from "@library/content/ResponsiveImage";
import { listItemMediaClasses } from "@library/lists/ListItemMedia.styles";
import { createSourceSetValue, ImageSourceSet } from "@library/utility/appUtils";
import React, { useMemo } from "react";

interface IProps extends React.HTMLAttributes<HTMLImageElement> {
    src: string;
    alt: string;
    srcSet?: string | ImageSourceSet;
    className?: string;
    ratio?: {
        vertical: number;
        horizontal: number;
    };
}

export function ListItemMedia(props: IProps) {
    const { srcSet, className, ...rest } = props;
    const classes = listItemMediaClasses();
    const mediaClass = cx(classes.mediaItem, className);

    const urlSrcSet = useMemo<string | undefined>(() => {
        if (typeof props.srcSet === "object") return createSourceSetValue(props.srcSet);

        return props.srcSet ?? "";
    }, [props.srcSet]);

    if (!props.src)
        return (
            <div className={cx(mediaClass, classes.ratioContainer(props.ratio ?? { vertical: 9, horizontal: 16 }))} />
        );

    return <ResponsiveImage {...rest} className={mediaClass} srcSet={urlSrcSet} />;
}

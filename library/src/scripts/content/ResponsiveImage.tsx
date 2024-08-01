/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { responsiveImageClasses } from "@library/content/ResponsiveImage.styles";
import { HtmlAttributes } from "csstype";
import React from "react";

interface IProps extends React.ImgHTMLAttributes<HTMLImageElement> {
    ratio?: {
        vertical: number;
        horizontal: number;
    };
}

export function ResponsiveImage(_props: IProps) {
    const { className, ratio, ...imgProps } = _props;
    const classes = responsiveImageClasses();

    return (
        <div className={cx(classes.ratioContainer(ratio ?? { vertical: 9, horizontal: 16 }), className)}>
            <img {...imgProps} className={classes.image} loading="lazy" />
        </div>
    );
}

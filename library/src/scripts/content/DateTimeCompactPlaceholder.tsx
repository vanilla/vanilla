/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dateTimeClasses } from "@library/content/dateTimeStyles";
import { LoadingRectange, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import classNames from "classnames";

interface IProps {
    size?: number;
    className?: string;
}

export function DateTimeCompactPlaceholder(props: IProps) {
    const classes = dateTimeClasses();
    const size = props.size ?? 38;
    return <LoadingRectange className={classNames(classes.compactRoot, props.className)} height={size} width={size} />;
}

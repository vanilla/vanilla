/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { DetailedHTMLProps } from "react";
import { loadingCircleClass, loadingRectangleClass, loadingSpacerClass } from "@library/loaders/loadingRectangleStyles";
import classNames from "classnames";

interface IProps extends DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> {
    height: string | number;
    width?: string | number;
}

export function LoadingRectangle(props: IProps) {
    return <div {...props} className={classNames(loadingRectangleClass(props.height, props.width), props.className)} />;
}

export function LoadingSpacer(props: IProps) {
    return <div {...props} className={classNames(loadingSpacerClass(props.height), props.className)} />;
}

export function LoadingCircle(props: IProps) {
    return <div {...props} className={classNames(loadingCircleClass(props.height), props.className)} />;
}

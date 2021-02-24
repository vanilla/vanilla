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
    inline?: boolean;
}

export function LoadingRectangle(_props: IProps) {
    const { inline, ...props } = _props;
    return (
        <div
            {...props}
            className={classNames(loadingRectangleClass(props.height, props.width, inline), props.className)}
        />
    );
}

export function LoadingSpacer(props: IProps) {
    return <div {...props} className={classNames(loadingSpacerClass(props.height), props.className)} />;
}

export function LoadingCircle(_props: IProps) {
    const { inline, ...props } = _props;
    return <div {...props} className={classNames(loadingCircleClass(props.height, inline), props.className)} />;
}

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { DetailedHTMLProps } from "react";
import { loadingRectangeClass, loadingSpacerClass } from "@library/loaders/loadingRectangeStyles";
import classNames from "classnames";

interface IProps extends DetailedHTMLProps<React.HTMLAttributes<HTMLDivElement>, HTMLDivElement> {
    height: string | number;
    width?: string | number;
}

export function LoadingRectange(props: IProps) {
    return (
        <div {...props} className={classNames(loadingRectangeClass(props.height, props.width), props.className)}></div>
    );
}

export function LoadingSpacer(props: IProps) {
    return <div {...props} className={classNames(loadingSpacerClass(props.height), props.className)}></div>;
}

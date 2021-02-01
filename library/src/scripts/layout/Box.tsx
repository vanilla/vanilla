/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import { boxClasses } from "@library/layout/Box.styles";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children?: React.ReactNode;
    options?: Partial<IBoxOptions>;
}

export const Box = React.forwardRef(function Box(_props: IProps, ref: React.Ref<HTMLDivElement>) {
    const { options, ...props } = _props;
    const classes = boxClasses(options ?? {});

    return <div {...props} ref={ref} className={cx(classes.root, props.className)} />;
});

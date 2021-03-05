/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { cx, css } from "@emotion/css";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { Mixins } from "@library/styles/Mixins";
import { usePageBoxContext } from "@library/layout/PageBox.context";

interface IProps extends React.HTMLAttributes<HTMLElement> {
    children?: React.ReactNode;
    options?: Partial<IBoxOptions>;
    as?: keyof JSX.IntrinsicElements;
}

export const PageBox = React.forwardRef(function Box(_props: IProps, ref: React.Ref<HTMLElement>) {
    const { options: propOptions, as, ...props } = _props;

    const contextOptions = usePageBoxContext().options;

    const boxClass = css(Mixins.box(Variables.box(propOptions ?? contextOptions ?? {})));
    const Component = (as as "div") ?? "div";

    return (
        <Component
            {...props}
            ref={ref as React.Ref<HTMLDivElement>}
            className={cx("pageBox", boxClass, props.className)}
        />
    );
});

/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ElementType, useRef } from "react";
import { cx, css } from "@emotion/css";
import { IBoxOptions } from "@library/styles/cssUtilsTypes";
import { Variables } from "@library/styles/Variables";
import { Mixins } from "@library/styles/Mixins";
import { PageBoxDepthContextProvider, usePageBoxContext } from "@library/layout/PageBox.context";

interface IProps extends React.HTMLAttributes<HTMLElement> {
    children?: React.ReactNode;
    options?: Partial<IBoxOptions>;
    as?: ElementType;
}

export const PageBox = React.forwardRef(function Box(_props: IProps, passedRef: React.Ref<HTMLElement>) {
    const { options: propOptions, as, children, ...props } = _props;
    const ownRef = useRef<HTMLElement>();
    const ref = passedRef ?? ownRef;

    const contextOptions = usePageBoxContext().options;

    const boxClass = css(Mixins.box(Variables.box(propOptions ?? contextOptions ?? {})));
    const Component = (as as "div") ?? "div";

    return (
        <Component
            {...props}
            ref={ref as React.Ref<HTMLDivElement>}
            className={cx("pageBoxNoCompat", boxClass, props.className)}
        >
            <PageBoxDepthContextProvider boxRef={ref as any}>{children}</PageBoxDepthContextProvider>
        </Component>
    );
});

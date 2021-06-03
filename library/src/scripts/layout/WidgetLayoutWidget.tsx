/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";
import React from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    withContainer?: boolean;
    children?: React.ReactNode;
}

export const WidgetLayoutWidget = React.forwardRef(function WidgetLayoutWidget(
    _props: IProps,
    ref: React.RefObject<HTMLDivElement>,
) {
    const { withContainer, ...props } = _props;
    const classes = useWidgetLayoutClasses();
    return (
        <div
            {...props}
            ref={ref}
            className={cx(withContainer ? classes.widgetWithContainerClass : classes.widgetClass, props.className)}
        />
    );
});

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { forwardRef } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    childrenBefore?: React.ReactNode;
    childrenAfter?: React.ReactNode;
}

export const SectionFullWidth = forwardRef(function SectionFullWidth(
    props: IProps,
    ref: React.ForwardedRef<HTMLDivElement | null>,
) {
    const { children, childrenBefore, childrenAfter, ...elementProps } = props;
    return (
        <div {...elementProps} ref={ref}>
            {childrenBefore}
            {children}
            {childrenAfter}
        </div>
    );
});

export default SectionFullWidth;

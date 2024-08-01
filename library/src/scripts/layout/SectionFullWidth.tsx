/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    childrenBefore?: React.ReactNode;
    childrenAfter?: React.ReactNode;
    contentRef?: React.RefObject<HTMLDivElement>;
}

export function SectionFullWidth(props: IProps) {
    const { children, childrenBefore, childrenAfter, contentRef, ...elementProps } = props;
    return (
        <div {...elementProps} ref={contentRef}>
            {childrenBefore}
            {children}
            {childrenAfter}
        </div>
    );
}

export default SectionFullWidth;

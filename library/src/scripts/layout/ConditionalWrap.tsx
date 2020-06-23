/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    condition: boolean;
    className?: string;
    tag?: keyof JSX.IntrinsicElements;
    children?: React.ReactNode;
}

/**
 * Facilitates wrapping conditionally a component
 */
export default function ConditionalWrap(props: IProps) {
    const { condition, className, tag, children } = props;
    const Tag = tag || "div";
    if (condition) {
        return <Tag className={className}>{children}</Tag>;
    } else {
        return <>{children}</>;
    }
}

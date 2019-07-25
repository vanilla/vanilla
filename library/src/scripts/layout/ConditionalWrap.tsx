/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    condition: boolean;
    className?: string;
    tag?: keyof JSX.IntrinsicElements;
    children: React.ReactNode;
}

/**
 * Facilitates wrapping conditionally a component
 */
export default class ConditionalWrap extends React.Component<IProps> {
    public render() {
        const { condition, className, tag, children } = this.props;
        const Tag = tag || "div";
        if (condition) {
            return <Tag className={className}>{children}</Tag>;
        } else {
            return <React.Fragment>{children}</React.Fragment>;
        }
    }
}

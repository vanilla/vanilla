/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    condition: boolean;
    className?: string;
    tag?: keyof JSX.IntrinsicElements;
    component?: React.ComponentType<any>;
    children?: React.ReactNode;
    componentProps?: object;
}

/**
 * Facilitates wrapping conditionally a component
 */
export default function ConditionalWrap(props: IProps) {
    const { condition, className, tag, children, component, componentProps = {} } = props;
    const Component = component || tag || "div";
    if (condition) {
        return (
            <Component className={className} {...componentProps}>
                {children}
            </Component>
        );
    } else {
        return <>{children}</>;
    }
}

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import className from "classnames";
import { containerClasses } from "@library/layout/components/containerStyles";

export interface IContainer {
    className?: string;
    children?: React.ReactNode;
    tag?: keyof JSX.IntrinsicElements;
}

/*
 * Implements "Container" component used to set max width of content of page.
 */
export default class Container extends React.Component<IContainer> {
    public static defaultProps = {
        tag: "div",
    };

    public render() {
        if (this.props.children) {
            const classes = containerClasses();
            const Tag = this.props.tag || "div";
            return <Tag className={className(classes.root, this.props.className)}>{this.props.children}</Tag>;
        } else {
            return null;
        }
    }
}

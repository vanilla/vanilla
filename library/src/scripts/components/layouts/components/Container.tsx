/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import className from "classnames";

export interface IContainer {
    className?: string;
    children?: React.ReactNode;
    tag?: string;
}

export default class Container extends React.Component<IContainer> {
    public static defaultProps = {
        tag: "div",
    };

    public render() {
        if (this.props.children) {
            const Tag = `${this.props.tag}`;
            return <Tag className={className("container", this.props.className)}>{this.props.children}</Tag>;
        } else {
            return null;
        }
    }
}

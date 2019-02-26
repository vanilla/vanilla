/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import className from "classnames";

export enum WidgetContainerSize {
    LARGE = "large",
    STANDARD = "standard",
}

export interface IContainer {
    className?: string;
    children?: React.ReactNode;
    tag?: string;
    size: WidgetContainerSize;
}

/*
 * Implements "WidgetContainer" component used to set a smaller max width than the Container component
 */
export default class WidgetContainer extends React.Component<IContainer> {
    public static defaultProps = {
        tag: "div",
        size: WidgetContainerSize.STANDARD,
    };

    public render() {
        if (this.props.children) {
            const Tag = `${this.props.tag}`;
            return (
                <Tag
                    className={className("widgetContainer", this.props.className, {
                        isLarge: this.props.size === WidgetContainerSize.LARGE,
                    })}
                >
                    {this.props.children}
                </Tag>
            );
        } else {
            return null;
        }
    }
}

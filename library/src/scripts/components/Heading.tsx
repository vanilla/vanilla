/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";

export interface ICommonHeadingProps {
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    className?: string;
}

export interface IStringTitle extends ICommonHeadingProps {
    title: string;
}

export interface IComponentTitle extends ICommonHeadingProps {
    children: JSX.Element | string;
}

export type IHeadingProps = IStringTitle | IComponentTitle;

export default class Heading extends React.Component<IHeadingProps> {
    public static defaultProps = {
        depth: 2,
    };

    public render() {
        const Tag = `h${this.props.depth}`;
        const stringTitle = "title" in this.props ? this.props.title : this.props.children;
        const componentTitle = "children" in this.props ? this.props.children : null;

        return (
            <Tag
                className={classNames(
                    "heading",
                    `heading-${this.props.depth}`,
                    { pageTitle: this.props.depth === 1 },
                    this.props.className,
                )}
            >
                {stringTitle || componentTitle}
            </Tag>
        );
    }
}

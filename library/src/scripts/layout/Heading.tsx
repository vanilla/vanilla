/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { typographyClasses } from "@library/styles/typographyStyles";

export interface ICommonHeadingProps {
    id?: string;
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    renderAsDepth?: 1 | 2 | 3 | 4 | 5 | 6;
    className?: string;
    title: string;
}

export interface IHeadingProps extends ICommonHeadingProps {
    children?: React.ReactNode;
    titleRef: React.RefObject<HTMLHeadingElement | null>;
}

/**
 * A component representing a element.
 */
export default class Heading extends React.Component<IHeadingProps> {
    public static defaultProps: Partial<IHeadingProps> = {
        depth: 2,
    };

    private get renderAsDepth(): number {
        return this.props.renderAsDepth ? this.props.renderAsDepth : this.props.depth!;
    }

    public render() {
        const { children, title } = this.props;
        const Tag = `h${this.props.depth}` as "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
        const classesTypography = typographyClasses();

        return (
            <Tag
                id={this.props.id}
                ref={this.props.titleRef}
                className={classNames(
                    "heading",
                    `heading-${this.renderAsDepth}`,
                    { [`${classesTypography.pageTitle}`]: this.renderAsDepth === 1 },
                    this.props.className,
                )}
            >
                {children ? children : title}
            </Tag>
        );
    }
}

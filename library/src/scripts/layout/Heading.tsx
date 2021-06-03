/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { typographyClasses } from "@library/styles/typographyStyles";
import { cx } from "@emotion/css";

export interface ICommonHeadingProps {
    id?: string;
    depth?: number;
    renderAsDepth?: number;
    className?: string;
    title?: React.ReactNode;
    custom?: boolean;
}

export interface IHeadingProps extends ICommonHeadingProps, Omit<React.HTMLAttributes<HTMLHeadingElement>, "title"> {
    isLarge?: boolean;
    children?: React.ReactNode;
}

/**
 * A component representing a element.
 */
const Heading = React.forwardRef<HTMLHeadingElement, IHeadingProps>(function Heading(props: IHeadingProps, ref) {
    const { children, title, depth, renderAsDepth, className, isLarge, custom, ...restProps } = props;
    const finalDepth = depth ?? 2;
    const finalRenderDepth = renderAsDepth ?? finalDepth;

    const isPageTitle = finalRenderDepth === 1;
    const isSubTitle = finalRenderDepth === 2;
    const isComponentSubTitle = finalRenderDepth >= 3;

    const Tag = `h${finalDepth}` as "h1";
    const classes = typographyClasses();

    return (
        <Tag
            {...restProps}
            ref={ref}
            className={cx(
                {
                    [classes.pageTitle]: isPageTitle && !custom,
                    [classes.largeTitle]: isLarge && !custom,
                    [classes.subTitle]: isSubTitle && !custom,
                    [classes.componentSubTitle]: isComponentSubTitle && !custom,
                },
                !custom && "heading",
                !custom && `heading-${finalRenderDepth}`,
                className,
            )}
        >
            {children ?? title}
        </Tag>
    );
});

export default Heading;

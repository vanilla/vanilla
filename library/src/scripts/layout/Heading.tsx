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
    renderAsDepth?: 1 | 2 | 3 | 4 | 5 | 6 | "custom";
    className?: string;
    title?: React.ReactNode;
}

export interface IHeadingProps extends ICommonHeadingProps, Omit<React.HTMLAttributes<HTMLHeadingElement>, "title"> {
    isLarge?: boolean;
    children?: React.ReactNode;
}

/**
 * A component representing a element.
 */
const Heading = React.forwardRef<HTMLHeadingElement, IHeadingProps>(function Heading(props: IHeadingProps, ref) {
    const { children, title, depth, renderAsDepth, className, isLarge, ...restProps } = props;
    const finalDepth = depth ?? 2;
    const finalRenderDepth = renderAsDepth ?? finalDepth;

    const Tag = `h${finalDepth}` as "h1";
    const classes = typographyClasses();
    const customStyles = renderAsDepth !== "custom";

    return (
        <Tag
            {...restProps}
            ref={ref}
            className={classNames(
                "heading",
                `heading-${finalRenderDepth}`,
                {
                    [classes.pageTitle]: finalRenderDepth === 1 && !customStyles,
                    [classes.largeTitle]: isLarge && !customStyles,
                    [classes.subTitle]: finalRenderDepth === 2 && !customStyles,
                    [classes.componentSubTitle]: finalRenderDepth >= 3 && !customStyles,
                },
                className,
            )}
        >
            {children ?? title}
        </Tag>
    );
});

export default Heading;

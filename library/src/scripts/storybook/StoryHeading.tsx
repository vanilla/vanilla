/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import classNames from "classnames";

export interface IStoryHeadingProps {
    depth?: number;
    children: React.ReactNode;
    separator?: boolean;
}

/**
 * Heading component, for react storybook.
 */
export function StoryHeading(props: IStoryHeadingProps) {
    const classes = storyBookClasses();
    const depth = props.depth ? props.depth : 2;
    const Tag = `h${depth}` as "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
    return (
        <Tag
            className={classNames(classes.heading, {
                [classes.headingH1]: depth === 1,
                [classes.headingH2]: depth === 2,
                [classes.headingH3]: depth === 3,
            })}
        >
            {props.children}
        </Tag>
    );
}

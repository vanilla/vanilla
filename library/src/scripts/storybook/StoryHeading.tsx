/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { CSSProperties } from "react";
import { StorySeparator } from "@library/storybook/StorySeparator";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

export interface IStoryHeadingProps {
    depth?: 1 | 2 | 3 | 4 | 5 | 6;
    children: React.ReactNode;
}

/**
 * Heading component, for react storybook.
 */
export function StoryHeading(props: IStoryHeadingProps) {
    const classes = storyBookClasses();
    const depth = props.depth ? props.depth : 2;
    const Tag = `h${depth}` as "h1" | "h2" | "h3" | "h4" | "h5" | "h6";
    return (
        <>
            {Tag !== "h1" && <StorySeparator width={500} />}
            <Tag className={classes.heading}>{props.children}</Tag>
            {Tag === "h2" && <StorySeparator width={500} />}
        </>
    );
}

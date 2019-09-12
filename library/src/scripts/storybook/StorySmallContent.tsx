/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

export interface IStoryHeadingProps {
    depth?: number;
    children: React.ReactNode;
    separator?: boolean;
}

/**
 * Heading component, for react storybook.
 */
export function StorySmallContent(props: IStoryHeadingProps) {
    const classes = storyBookClasses();
    return <div className={classes.smallContent}>{props.children}</div>;
}

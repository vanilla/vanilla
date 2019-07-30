/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { CSSProperties } from "react";
import { StorySeparator } from "@library/storybook/StorySeparator";
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
export function StoryContent(props: IStoryHeadingProps) {
    const classes = storyBookClasses();
    return <div className={classes.content}>{props.children}</div>;
}

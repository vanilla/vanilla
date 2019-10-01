/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { clearUniqueIDCache } from "@library/utility/idUtils";

export interface IStoryHeadingProps {
    depth?: number;
    children: React.ReactNode;
    separator?: boolean;
}

/**
 * Heading component, for react storybook.
 */
export function StoryContent(props: IStoryHeadingProps) {
    useEffect(() => {
        // Ensure consistent IDs in every storybook render.
        clearUniqueIDCache();
    }, []);

    const classes = storyBookClasses();
    return <div className={classes.content}>{props.children}</div>;
}

/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { clearUniqueIDCache } from "@library/utility/idUtils";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { MemoryRouter, Route } from "react-router";
import { LinkContextProvider } from "@library/routing/links/LinkContextProvider";

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
    return (
        <DeviceProvider>
            <MemoryRouter>
                <div className={classes.content}>{props.children}</div>
            </MemoryRouter>
        </DeviceProvider>
    );
}

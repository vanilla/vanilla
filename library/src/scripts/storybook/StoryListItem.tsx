/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

/**
 * Adds <li> element with proper styles for react storybook.
 */
export function StoryListItem(props) {
    const classes = storyBookClasses();
    return <li className={classes.listItem}>{props.children}</li>;
}

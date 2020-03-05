/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses, storyBookVariables } from "@library/storybook/StoryBookStyles";
import { margins, negative } from "@library/styles/styleHelpers";

/**
 * Separator, for react storybook.
 */
export function StoryFullPage(props) {
    const classes = storyBookClasses();
    return <div className={classes.fullPage}>{props.children}</div>;
}

/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, singleBorder, unit } from "@library/styles/styleHelpers";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

interface IProps {
    children: JSX.Element;
}

/**
 * Separator, for react storybook.
 */
export function StoryTile(props: IProps) {
    const classes = storyBookClasses();
    return <li className={classes.tile}>{props.children}</li>;
}

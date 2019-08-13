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
    children: React.ReactNode;
}

/**
 * Separator, for react storybook.
 */
export function StoryTiles(props: IProps) {
    const classes = storyBookClasses();
    return <ul className={classes.tiles}>{props.children}</ul>;
}

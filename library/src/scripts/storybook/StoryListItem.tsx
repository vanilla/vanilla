/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

interface IProps {
    children: JSX.Element;
}

/**
 * Separator, for react storybook.
 */
export function StoryListItem(props: IProps) {
    const classes = storyBookClasses();
    return <li className={classes.listItem}>{props.children}</li>;
}

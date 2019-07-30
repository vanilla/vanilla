/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";

interface IProps {
    href: string;
    children: React.ReactNode;
    newTab?: boolean;
}

/**
 * Separator, for react storybook.
 */
export function StoryLink(props: IProps) {
    const classes = storyBookClasses();
    return (
        <a href={props.href} target={props.newTab ? "_blank" : undefined} className={classes.link}>
            {props.children}
        </a>
    );
}

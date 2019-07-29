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
export function StoryLink(props: IProps) {
    const classes = storyBookClasses();
    return (
        <div className={classes.contanerOuter}>
            <div className={classes.contanerOuter}>{props.children}</div>
        </div>
    );
}

/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { margins, singleBorder, unit } from "@library/styles/styleHelpers";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import classNames from "classnames";
import ConditionalWrap from "@library/layout/ConditionalWrap";

interface IProps {
    title?: string;
    children: JSX.Element;
    dark?: boolean;
    scaleContents?: number;
}

/**
 * Separator, for react storybook.
 */
export function StoryTile(props: IProps) {
    const classes = storyBookClasses();
    return (
        <li title={props.title} className={classNames(classes.tile, { [classes.tileDark]: props.dark })}>
            <ConditionalWrap
                condition={props.scaleContents !== undefined}
                className={classes.scaleContents(props.scaleContents!)}
            >
                {props.children}
            </ConditionalWrap>
        </li>
    );
}

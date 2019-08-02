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

export interface IStoryTileProps {
    mouseOverText?: string;
    children: React.ReactNode;
    type?: string;
    scaleContents?: number;
    tag?: string;
}

/**
 * Separator, for react storybook.
 */
export function StoryTile(props: IStoryTileProps) {
    const classes = storyBookClasses();
    const Tag = `${props.tag ? props.tag : "li"}`;
    return (
        // @ts-ignore
        <Tag
            title={props.mouseOverText}
            className={classNames(classes.tile, { [classes.setBackground(props.type!)]: props.type })}
        >
            <ConditionalWrap
                condition={props.scaleContents !== undefined}
                className={classes.scaleContents(props.scaleContents!)}
            >
                {props.children}
            </ConditionalWrap>
        </Tag>
    );
}

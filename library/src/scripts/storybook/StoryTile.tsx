/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import classNames from "classnames";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ColorHelper } from "csx";

export interface IStoryTileProps {
    mouseOverText?: string;
    children: React.ReactNode;
    type?: string;
    scaleContents?: number;
    tag?: string;
    backgroundColor?: ColorHelper;
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
            style={props.backgroundColor ? { backgroundColor: props.backgroundColor } : undefined}
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

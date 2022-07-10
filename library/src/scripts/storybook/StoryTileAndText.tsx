/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { IStoryTileProps, StoryTile } from "@library/storybook/StoryTile";
import classNames from "classnames";
import { ColorHelper } from "csx";
import React from "react";

export interface IStoryTileAndTextProps extends IStoryTileProps {
    text?: string;
    title?: string;
    compact?: boolean;
    backgroundColor?: ColorHelper;
}

/**
 * Separator, for react storybook.
 */
export function StoryTileAndText(props: IStoryTileAndTextProps) {
    const classes = storyBookClasses();
    return (
        <li className={classNames(classes.tilesAndText, { [classes.compactTilesAndText]: props.compact })}>
            <StoryTile
                tag={"div"}
                mouseOverText={props.title}
                type={props.type}
                scaleContents={props.scaleContents}
                backgroundColor={props.backgroundColor}
            >
                {props.children}
            </StoryTile>
            {(props.title || props.text) && (
                <div className={classNames(classes.tileText, { [classes.tileTextPaddingLeft]: !props.compact })}>
                    {props.title && <h3 className={classes.tileTitle}>{props.title}</h3>}
                    {props.text && <StoryParagraph>{props.text}</StoryParagraph>}
                </div>
            )}
        </li>
    );
}

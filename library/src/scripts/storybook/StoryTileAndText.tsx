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
import { IStoryTileProps, StoryTile } from "@library/storybook/StoryTile";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

export interface IStoryTileAndTextProps extends IStoryTileProps {
    text?: string;
    title?: string;
    compact?: boolean;
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
                mouseOverText={props.mouseOverText}
                type={props.type}
                scaleContents={props.scaleContents}
            >
                {props.children}
            </StoryTile>
            {(props.title || props.text) && (
                <div className={classNames(classes.tileText, { [classes.tileTextPaddingLeft]: !props.compact })}>
                    {props.title && <StoryHeading depth={3}>{props.title}</StoryHeading>}
                    {props.text && <StoryParagraph>{props.text}</StoryParagraph>}
                </div>
            )}
        </li>
    );
}

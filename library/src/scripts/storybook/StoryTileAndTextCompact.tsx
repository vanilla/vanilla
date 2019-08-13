/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IStoryTileAndTextProps, StoryTileAndText } from "@library/storybook/StoryTileAndText";

interface IProps extends IStoryTileAndTextProps {}

/**
 * Separator, for react storybook.
 */
export function StoryTileAndTextCompact(props: IProps) {
    return (
        <StoryTileAndText {...props} compact={true}>
            {props.children}
        </StoryTileAndText>
    );
}

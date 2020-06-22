/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout from "@library/layout/PanelLayout";
import {IStoryTileAndTextProps} from "@library/storybook/StoryTileAndText";
import {twoColumnLayoutClasses} from "@library/layout/twoColumnLayoutStyles";


interface IProps extends Omit<IStoryTileAndTextProps, "leftTop" | "leftBottom" | "renderLeftPanelBackground"> {}

export default function TwoColumnLayout(props: IProps) {
    return <PanelLayout {...props} classes={twoColumnLayoutClasses()}/>;
}

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MemoryRouter } from "react-router";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { color, linearGradient } from "csx";
import Banner from "@library/banner/Banner";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { BannerAlignment, SearchBarPresets, SearchPlacement } from "@library/banner/bannerStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { STORY_LOGO_WHITE, STORY_LOGO_BLACK } from "@library/storybook/storyData";
import { IDateTime } from "@library/content/DateTime";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTiles } from "@library/storybook/StoryTiles";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import * as CommonIcons from "@library/icons/common";
import { StoryContent } from "@library/storybook/StoryContent";
import StoryExampleDropDownMessages from "@library/flyouts/StoryExampleDropDownMessages";

export default {
    title: "Date Icon",
};

function StoryDateIconStory(props) {
    const testDates = [{}];
    const content = testDates.map(date => {
        <StoryTiles>
            <DateIcon {...date} />
        </StoryTiles>;
    });

    return <>{content}</>;
}

export const Standard = storyWithConfig({}, () => <StoryDateIconStory />);

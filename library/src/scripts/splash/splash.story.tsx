/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import Splash from "@library/splash/Splash";
import { MemoryRouter, Router } from "react-router";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import merge from "lodash/merge";

const story = storiesOf("Home Page", module);

story.add("Splash", () => {
    const resetSplashData = {};
    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <StoryContent>
                    <StoryHeading depth={1}>Splash</StoryHeading>
                </StoryContent>
                <StoryContent>
                    <StoryHeading>Default Background</StoryHeading>
                </StoryContent>
                <Splash title={"How can we help you?"} styleOverwrite={merge(resetSplashData, {})} />
                <StoryContent>
                    <StoryHeading>Custom Background</StoryHeading>
                </StoryContent>
                <Splash
                    outerBackgroundImage={"https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg"}
                    title={"How can we help you?"}
                    styleOverwrite={merge(resetSplashData, {})}
                />
            </SearchContext.Provider>
        </MemoryRouter>
    );
});

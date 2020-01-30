/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import Banner, { IBannerStyleOverwrite } from "@library/banner/Banner";
import { MemoryRouter, Router } from "react-router";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import merge from "lodash/merge";
import { assetUrl } from "@library/utility/appUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { bannerFallbackBG } from "@library/banner/bannerStyles";
import { color } from "csx";

const story = storiesOf("Home Page", module);

story.add("Banner", () => {
    const globalVars = globalVariables();

    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <StoryContent>
                    <StoryHeading depth={1}>Banner</StoryHeading>
                    <StoryHeading>Default Background</StoryHeading>
                </StoryContent>
                <Banner
                    title={"How can we help you?"}
                    styleOverwrite={{
                        colors: {
                            bg: globalVars.mainColors.bg,
                            fg: globalVars.mainColors.fg,
                        },
                        outerBackgroundImage: bannerFallbackBG,
                        backgrounds: {
                            useOverlay: false,
                        },
                    }}
                />
                <StoryContent>
                    <StoryHeading>Custom Background</StoryHeading>
                </StoryContent>
                <Banner
                    title={"What can we do for you?"}
                    styleOverwrite={{
                        colors: {
                            bg: globalVars.mainColors.bg,
                            fg: globalVars.mainColors.fg,
                        },
                        outerBackgroundImage: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
                        backgrounds: {
                            useOverlay: true,
                        },
                    }}
                />

                <StoryContent>
                    <StoryHeading>Custom Colors</StoryHeading>
                </StoryContent>
                <Banner
                    title={"What's on your mind?"}
                    styleOverwrite={
                        {
                            colors: {
                                contrast: color("rgb(42,42,42)"),
                                primary: color("#9279a8"),
                                bg: color("#699dff"),
                                fg: color("rgb(255,254,250)"),
                            },
                            backgrounds: {
                                useOverlay: false,
                            },
                            outerBackgroundImage: "none",
                        } as IBannerStyleOverwrite
                    }
                />
            </SearchContext.Provider>
        </MemoryRouter>
    );
});

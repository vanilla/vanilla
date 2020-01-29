/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MemoryRouter } from "react-router";
import SearchContext from "@library/contexts/SearchContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import Splash from "@library/splash/Splash";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Home Page",
    params: {
        chromatic: {
            // viewports: [1400],
        },
    },
};

function StorySplash(props: { title: string }) {
    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <StoryContent>
                    <StoryHeading depth={1}>Splash</StoryHeading>
                    <StoryHeading>{props.title}</StoryHeading>
                </StoryContent>
                <Splash title={"How can we help you?"} />
            </SearchContext.Provider>
        </MemoryRouter>
    );
}

export const Standard = storyWithConfig({}, () => <StorySplash title="Standard" />);

export const LeftAligned = storyWithConfig(
    {
        themeVars: {
            splash: {
                options: {
                    alignment: "left",
                },
            },
        },
    },
    () => <StorySplash title="Left Aligned" />,
);

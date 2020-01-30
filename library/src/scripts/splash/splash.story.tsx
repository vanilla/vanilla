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
import { color } from "csx";

export default {
    title: "Splash",
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
                <Splash
                    title={"How can we help you?"}
                    description="This is a description. They're pretty great, you should try one sometime."
                />
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

export const BackgroundImage = storyWithConfig(
    {
        themeVars: {
            splash: {
                outerBackground: {
                    image: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
                },
                backgrounds: {
                    useOverlay: true,
                },
            },
        },
    },
    () => <StorySplash title="With a background image" />,
);

export const CustomOverlay = storyWithConfig(
    {
        themeVars: {
            splash: {
                outerBackground: {
                    image: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
                },
                backgrounds: {
                    useOverlay: true,
                    overlayColor: color("rgba(100, 44, 120, 0.5)"),
                },
            },
        },
    },
    () => <StorySplash title="With a background image (and colored overlay)" />,
);

export const CustomColors = storyWithConfig(
    {
        themeVars: {
            splash: {
                colors: {
                    contrast: color("rgb(42,42,42)"),
                    primary: color("#9279a8"),
                    bg: color("#699dff"),
                    fg: color("rgb(255,254,250)"),
                },
                backgrounds: {
                    useOverlay: false,
                },
                outerBackground: {
                    image: "none",
                },
            },
        },
    },
    () => <StorySplash title="Custom Colors" />,
);

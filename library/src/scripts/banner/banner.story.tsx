/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { MemoryRouter } from "react-router";
import SearchContext from "@library/contexts/SearchContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { color } from "csx";
import Banner from "@library/banner/Banner";
import { SearchBarButtonType } from "@library/headers/mebox/pieces/compactSearchStyles";
import { DeviceProvider } from "@library/layout/DeviceContext";

export default {
    title: "Banner",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

function StoryBanner(props: { title: string }) {
    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <DeviceProvider>
                    <Banner
                        title={props.title}
                        description="This is a description. They're pretty great, you should try one sometime."
                    />
                </DeviceProvider>
            </SearchContext.Provider>
        </MemoryRouter>
    );
}

export const Standard = storyWithConfig({ useWrappers: false }, () => <StoryBanner title="Standard" />);

export const NoDescription = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                options: {
                    hideDesciption: true,
                },
                colors: {
                    primary: color("#9279a8"),
                },
            },
        },
    },
    () => <StoryBanner title="No Description" />,
);

export const NoSearch = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                options: {
                    hideSearch: true,
                },
            },
        },
    },
    () => <StoryBanner title="No Search" />,
);

export const NoBackground = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                colors: {
                    contrast: color("rgb(42,42,42)"),
                    primary: color("#9279a8"),
                    bg: color("#699dff"),
                    fg: color("rgb(255,254,250)"),
                },
                backgrounds: {
                    useOverlay: false,
                },
                searchButtonOptions: {
                    type: SearchBarButtonType.SOLID,
                },
                outerBackground: {
                    image: "none",
                },
            },
        },
    },
    () => <StoryBanner title="No Background" />,
);

export const LeftAligned = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                options: {
                    alignment: "left",
                },
            },
        },
    },
    () => <StoryBanner title="Left Aligned" />,
);

export const BackgroundImage = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                outerBackground: {
                    image: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
                },
                backgrounds: {
                    useOverlay: true,
                },
                searchButtonOptions: {
                    type: SearchBarButtonType.TRANSPARENT,
                },
            },
        },
    },
    () => <StoryBanner title="With a background image" />,
);

export const CustomOverlay = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
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
    () => <StoryBanner title="With a background image (and colored overlay)" />,
);

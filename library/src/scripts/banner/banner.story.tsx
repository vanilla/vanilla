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
import { ButtonPresets } from "@library/forms/buttonStyles";

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
                    hideDescription: true,
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
                    primary: color("#9279a8"),
                    bg: color("#a98ac1"),
                    fg: color("#fff"),
                },
                backgrounds: {
                    useOverlay: false,
                },
                outerBackground: {
                    image: "none",
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.SOLID,
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
                    alignment: BannerAlignment.LEFT,
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
            },
            presetsBanner: {
                searchButtonOptions: {
                    preset: ButtonPresets.TRANSPARENT,
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

export const ImageAsElement = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: color("#111111"),
                    primaryContrast: color("#fff"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
            },
            banner: {
                options: {
                    alignment: BannerAlignment.LEFT,
                },
                colors: {
                    bg: "#fff",
                    primaryContrast: "#111111",
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                rightImage: {
                    image:
                        "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Image as Element - (With Left Alignment)" />,
);

export const LogoLarge = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: color("#111111"),
                    primaryContrast: color("#fff"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
            },
            banner: {
                colors: {
                    bg: "#fff",
                    primaryContrast: "#111111",
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                logo: {
                    image: "https://us.v-cdn.net/5022541/uploads/594/57SO4ULTV3HP.png",
                    width: "50%",
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Logo - Huge (shrunk with CSS)" />,
);

export const LogoSmall = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: color("#111111"),
                    primaryContrast: color("#fff"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
            },
            banner: {
                colors: {
                    bg: "#fff",
                    primaryContrast: "#111111",
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                logo: {
                    width: 150,
                    image: "https://us.v-cdn.net/5022541/uploads/067/Z28XXGPR2ZCS.png",
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Logo - Small" />,
);
export const LogoAndRightImage = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: color("#111111"),
                    primaryContrast: color("#fff"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
            },
            banner: {
                colors: {
                    bg: "#fff",
                    primaryContrast: "#111111",
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                rightImage: {
                    image:
                        "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                logo: {
                    width: 150,
                    image: "https://us.v-cdn.net/5022541/uploads/067/Z28XXGPR2ZCS.png",
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Image-o-rama!" />,
);

(ImageAsElement as any).story = {
    parameters: {
        chromatic: {
            viewports: [1400, globalVariables().content.width, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
        },
    },
};

export const ImageAsElementWide = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    primary: color("#111111"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
                content: {
                    width: 1350,
                },
            },
            banner: {
                options: {
                    alignment: BannerAlignment.LEFT,
                },
                colors: {
                    bg: "#fff",
                    primaryContrast: "#111111",
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                rightImage: {
                    image:
                        "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Image as Element - (With Left Alignment)" />,
);

export const Shadowed = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                searchBar: {
                    shadow: {
                        show: true,
                    },
                },
            },
        },
    },
    () => <StoryBanner title="Search with shadow" />,
);

export const SearchShadowNoSearchButton = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
                mainColors: {
                    // primary: color("#4fb33f"),
                },
                body: {
                    backgroundImage: {
                        color: color("#efefef"),
                    },
                },
            },
            banner: {
                searchBar: {
                    shadow: {
                        show: true,
                    },
                },
                options: {
                    alignment: BannerAlignment.LEFT,
                },
                rightImage: {
                    image:
                        "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },

                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
            presetsBanner: {
                button: {
                    preset: ButtonPresets.HIDE,
                },
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Image as Element - (With Left Alignment)" />,
);

export const searchPositionBottom = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                options: {
                    searchPlacement: "bottom",
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    color: "#4b496e",
                    image: "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
                },
                searchStrip: {
                    bg: color("#4b496e"),
                    minHeight: 100,
                },
            },
            presetsBanner: {
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Search on bottom" />,
);
export const searchPositionBottomWithOverlayAndOffset = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                options: {
                    searchPlacement: "bottom" as SearchPlacement,
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    position: "50% 100%",
                    color: color("#54367c"),
                    image: "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
                },
                contentContainer: {
                    padding: {
                        bottom: 140,
                    },
                },
                searchStrip: {
                    bg: linearGradient("to bottom", "rgb(0,0,0,0) 0%,rgba(0,0,0, .4) 50%"),
                    minHeight: 140,
                    offset: -140,
                    padding: {
                        top: 0,
                        bottom: 70,
                    },
                    mobile: {
                        minHeight: 0,
                        offset: -50,
                        padding: {
                            bottom: 0,
                        },
                    },
                },
            },
            presetsBanner: {
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Search on bottom with negative offset" />,
);

export const searchBarNoImage = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            colors: {
                primary: "#ff5b64",
                primaryContrast: "#e3e3e3",
            },
            banner: {
                options: {
                    hideDescription: true,
                    hideTitle: true,
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    color: color("#54367c"),
                    unsetBackground: true,
                },
                contentContainer: {
                    padding: {
                        top: 12,
                        bottom: 12,
                    },
                },
            },
            presetsBanner: {
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Only search bar, no image" />,
);

export const bannerImageOnly = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            colors: {
                primary: "#ff5b64",
                primaryContrast: "#e3e3e3",
            },
            banner: {
                options: {
                    hideSearch: true,
                    hideDescription: true,
                    hideTitle: true,
                },
                dimensions: {
                    minHeight: 300,
                },
                outerBackground: {
                    position: "50% 100%",
                    color: color("#54367c"),
                    image: "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
                },
                innerBackground: {
                    image: "https://us.v-cdn.net/6031163/uploads/3021ecca7cc7a015f582d8c2ce56fa09.png",
                },
                contentContainer: {
                    minHeight: 500,
                    padding: {
                        top: 12,
                        bottom: 12,
                    },
                    mobile: {
                        minHeight: 300,
                    },
                },
            },
            presetsBanner: {
                input: {
                    preset: SearchBarPresets.BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Banner Image Only" />,
);

// Only works with button
export const unifiedBorder = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                outerBackground: {
                    image: "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
                },
            },
            presetsBanner: {
                input: {
                    preset: SearchBarPresets.UNIFIED_BORDER,
                },
            },
        },
    },
    () => <StoryBanner title="Unified Border" />,
);

(ImageAsElementWide as any).story = {
    parameters: {
        chromatic: {
            viewports: [1450, 1350, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
        },
    },
};

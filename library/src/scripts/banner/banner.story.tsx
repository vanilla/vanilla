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
import { BannerAlignment, SearchBarPresets, SearchPlacement } from "@library/banner/bannerStyles";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonPreset } from "@library/forms/buttonStyles";
import { STORY_LOGO_BLACK, STORY_LOGO_WHITE } from "@library/storybook/storyData";
import { LayoutProvider } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

export default {
    title: "Banner",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

function StoryBanner(props: { title: string; forceSearchOpen?: boolean; isContentBanner?: boolean }) {
    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <LayoutProvider type={LayoutTypes.THREE_COLUMNS}>
                    <Banner
                        forceSearchOpen={props.forceSearchOpen}
                        isContentBanner={props.isContentBanner}
                        title={props.title}
                        description="This is a description. They're pretty great, you should try one sometime."
                    />
                </LayoutProvider>
            </SearchContext.Provider>
        </MemoryRouter>
    );
}

export const Standard = storyWithConfig({ useWrappers: false }, () => <StoryBanner title="Standard" />);

export const SearchOpen = storyWithConfig({ useWrappers: false }, () => (
    <StoryBanner title="Search Open" forceSearchOpen />
));

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
                presets: {
                    button: {
                        preset: ButtonPreset.SOLID,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.TRANSPARENT,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                logo: {
                    width: 150,
                    image: STORY_LOGO_BLACK,
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                logo: {
                    width: 150,
                    image: STORY_LOGO_BLACK,
                },
                spacing: {
                    padding: {
                        top: 87,
                        bottom: 87,
                    },
                },
            },
        },
    },
    () => <StoryBanner title="Image-o-rama!" />,
);

(ImageAsElement as any).story = {
    parameters: {
        chromatic: {
            viewports: [1400, layoutVariables().contentWidth, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
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
                middleColumn: {
                    width: 1050,
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                presets: {
                    button: {
                        preset: ButtonPreset.HIDE,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
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
                presets: {
                    input: {
                        preset: SearchBarPresets.NO_BORDER,
                    },
                },
                options: {
                    searchPlacement: "bottom" as SearchPlacement,
                },
                backgrounds: {
                    useOverlay: true,
                },
                searchStrip: {},
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
                presets: {
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
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
                dimensions: {
                    mobile: {
                        minHeight: 200,
                    },
                },
                contentContainer: {
                    padding: {
                        bottom: 140,
                    },
                    mobile: {
                        padding: {
                            top: 30,
                            bottom: 100,
                        },
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
                        minHeight: 100,
                        offset: -100,
                        padding: {
                            bottom: 15,
                        },
                    },
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
                presets: {
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
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
                presets: {
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
                options: {
                    hideSearch: true,
                    hideDescription: true,
                    hideTitle: true,
                },
                dimensions: {
                    minHeight: 300,
                    mobile: {
                        minHeight: 200,
                    },
                },
                outerBackground: {
                    position: "50% 100%",
                    color: color("#54367c"),
                    image: "https://us.v-cdn.net/5022541/uploads/091/7G8KTIZCJU5S.jpeg",
                    breakpoints: {
                        mobile: {
                            image: "https://us.v-cdn.net/5022541/uploads/470/U68ZI0LRPRBQ.png",
                        },
                    },
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
                presets: {
                    input: {
                        preset: SearchBarPresets.UNIFIED_BORDER,
                    },
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

export const ContentBannerNoLogo = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            contentBanner: {
                options: {
                    enabled: true,
                },
            },
        },
    },
    () => {
        return <StoryBanner title="Should not appear" isContentBanner />;
    },
);

export const ContentBannerLogo = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            contentBanner: {
                options: {
                    enabled: true,
                },
                logo: {
                    image: STORY_LOGO_WHITE,
                    width: 150,
                },
            },
        },
    },
    () => {
        return <StoryBanner title="Should not appear" isContentBanner />;
    },
);

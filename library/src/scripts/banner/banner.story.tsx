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
import Banner, { IBannerProps } from "@library/banner/Banner";
import { BannerAlignment, bannerVariables, SearchPlacement } from "@library/banner/bannerStyles";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { STORY_LOGO_BLACK, STORY_LOGO_WHITE } from "@library/storybook/storyData";
import { LayoutProvider } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { globalVariables } from "@library/styles/globalStyleVars";

const globalVars = globalVariables();

export default {
    title: "Widgets/Banner",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

interface IStoryBannerProps extends IBannerProps {
    message?: string;
    bannerProps?: IBannerProps;
    onlyOne?: boolean;
}

function StoryBanner(props: IStoryBannerProps) {
    const { bannerProps = {}, message } = props;
    // Allow either passing props through "bannerProps", or overwriting them individually
    const mergedProps: IBannerProps = {
        action: props.action ?? bannerProps.action,
        title: props.title ?? bannerProps.title,
        description:
            props.description ??
            bannerProps.description ??
            `Sample description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.`,
        className: props.className ?? bannerProps.className,
        backgroundImage: props.backgroundImage ?? bannerProps.backgroundImage,
        contentImage: props.contentImage ?? bannerProps.contentImage,
        logoImage: props.logoImage ?? bannerProps.logoImage,
        searchBarNoTopMargin: props.searchBarNoTopMargin ?? bannerProps.searchBarNoTopMargin,
        forceSearchOpen: props.forceSearchOpen ?? bannerProps.forceSearchOpen,
        isContentBanner: props.isContentBanner ?? bannerProps.isContentBanner,
        scope: props.scope ?? bannerProps.scope,
        initialQuery: props.initialQuery ?? bannerProps.initialQuery,
    };

    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <LayoutProvider type={LayoutTypes.THREE_COLUMNS}>
                    <Banner {...mergedProps} />
                </LayoutProvider>
            </SearchContext.Provider>
            <StoryContent>
                {message && (
                    <>
                        <StoryHeading>Note:</StoryHeading>
                        <StoryParagraph>{message}</StoryParagraph>
                    </>
                )}
            </StoryContent>
        </MemoryRouter>
    );
}

function StoryBannerWithScope(props: IStoryBannerProps) {
    const { onlyOne = false } = props;
    const optionsItems: ISelectBoxItem[] = [
        {
            name: "scope1",
            value: "scope1",
        },
        {
            name: "Everywhere",
            value: "every-where",
        },
    ];

    const value = {
        name: "Everywhere",
        value: "every-where",
    };

    const scope = {
        optionsItems,
        value,
    };

    return (
        <DeviceProvider>
            {/* Fix z-index in storybook */}
            <style>{`#root > div { z-index: inherit; }`}</style>
            <style>{`#root { min-height: 100%; }`}</style>

            {!onlyOne && (
                <>
                    <StoryContent>
                        <StoryHeading>{`Banner - Search with no button`}</StoryHeading>
                    </StoryContent>
                </>
            )}
            <StoryBanner {...props} scope={scope} />

            {!onlyOne && (
                <>
                    <StoryContent>
                        <StoryHeading>{`Title bar search with button`}</StoryHeading>
                    </StoryContent>
                    <StoryBanner
                        {...props}
                        initialQuery={
                            "This is an example queryThis is an example queryThis is an example queryThis is an example queryThis is an example queryThis is an example query"
                        }
                    />
                    <StoryContent>
                        <StoryHeading>{`Title bar search with scope`}</StoryHeading>
                    </StoryContent>
                    <StoryBanner
                        {...props}
                        scope={scope}
                        initialQuery={"This is an example queryThis is an example queryThis is an example query"}
                    />
                </>
            )}
        </DeviceProvider>
    );
}

export const Default = storyWithConfig({ useWrappers: false }, () => <StoryBannerWithScope title="Standard" />);

export const SquareRadius = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                searchBar: {
                    border: {
                        radius: 0,
                    },
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Standard" />,
);

export const RoundRadius = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                searchBar: {
                    border: {
                        radius: 20,
                    },
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Search Open" forceSearchOpen />,
);

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
    () => (
        <>
            <StoryBannerWithScope title="No Description" />
        </>
    ),
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
    () => <StoryBannerWithScope title="No Search" onlyOne={true} />,
);

export const NoBackgroundImage = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                backgrounds: {
                    useOverlay: false,
                },
                outerBackground: {
                    unsetBackground: true,
                },
                presets: {
                    button: {
                        preset: ButtonPreset.TRANSPARENT,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
            },
        },
    },
    () => <StoryBannerWithScope title="No Background image" />,
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
    () => <StoryBannerWithScope title="Left Aligned" />,
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
    () => <StoryBannerWithScope title="With a background image" />,
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
    () => <StoryBannerWithScope title="With a background image (and colored overlay)" />,
);

export const ImageAsElement = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
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
                font: {
                    color: color("#111111"),
                    shadow: undefined,
                },
                outerBackground: {
                    color: "#FFF6F5",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                description: {
                    font: {
                        color: color("#323232"),
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
    () => <StoryBannerWithScope title="Image as Element - (With Left Alignment)" />,
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
    () => <StoryBannerWithScope title="Logo - Huge (shrunk with CSS)" />,
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
    () => <StoryBannerWithScope title="Logo - Small" />,
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
    () => <StoryBannerWithScope title="Image-o-rama!" />,
);

ImageAsElement.parameters = {
    chromatic: {
        viewports: [1400, layoutVariables().contentWidth, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
    },
};

export const ImageAsElementWide = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            global: {
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
                font: {
                    color: color("#111111"),
                    shadow: undefined,
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
    () => <StoryBannerWithScope title="Image as Element - (With Left Alignment)" />,
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
    () => <StoryBannerWithScope title="Search with shadow" />,
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
    () => <StoryBannerWithScope title="Image as Element - (With Left Alignment)" />,
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
    () => <StoryBannerWithScope title="Search on bottom" />,
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
                    button: {
                        preset: ButtonPreset.TRANSPARENT,
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
    () => <StoryBannerWithScope title="Search on bottom with negative offset" />,
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
    () => <StoryBannerWithScope title="Only search bar, no image" onlyOne={true} />,
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
    () => <StoryBannerWithScope title="Banner Image Only" onlyOne={true} />,
);

ImageAsElementWide.parameters = {
    chromatic: {
        viewports: [1450, 1350, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
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
        return <StoryBannerWithScope title="Should not appear" isContentBanner onlyOne={true} />;
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
        return <StoryBannerWithScope title="Should not appear" isContentBanner onlyOne={true} />;
    },
);

export const UnifiedBorder = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                presets: {
                    input: {
                        preset: SearchBarPresets.UNIFIED_BORDER,
                    },
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    color: color("#d4d4d4"),
                    unsetBackground: true,
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Standard" />,
);

export const UnifiedBorderSquare = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                presets: {
                    input: {
                        preset: SearchBarPresets.UNIFIED_BORDER,
                    },
                },
                searchBar: {
                    border: {
                        radius: 0,
                    },
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    color: color("#d4d4d4"),
                    unsetBackground: true,
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Standard" />,
);

export const UnifiedBorderRound = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                presets: {
                    input: {
                        preset: SearchBarPresets.UNIFIED_BORDER,
                    },
                },
                searchBar: {
                    border: {
                        radius: 20,
                    },
                },
                backgrounds: {
                    useOverlay: true,
                },
                outerBackground: {
                    color: color("#d4d4d4"),
                    unsetBackground: true,
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Standard" />,
);

export const SearchOnBgColor = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                font: {
                    color: globalVars.mainColors.fg,
                    shadow: "none",
                },
                backgrounds: {
                    useOverlay: false,
                },
                outerBackground: {
                    color: globalVars.mainColors.bg,
                    unsetBackground: true,
                },
                presets: {
                    button: {
                        preset: ButtonPreset.SOLID,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
            },
        },
    },
    () => (
        <>
            <StoryBannerWithScope
                title="Search on bg color"
                description={`Note that this isn't really "banner" related, but since the other variations for the search are here, it makes sense to have them side by side.`}
            />
        </>
    ),
);

export const SearchOnBgColorRound = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                font: {
                    color: globalVars.mainColors.fg,
                    shadow: "none",
                },
                backgrounds: {
                    useOverlay: false,
                },
                outerBackground: {
                    color: globalVars.mainColors.bg,
                    unsetBackground: true,
                },
                presets: {
                    button: {
                        preset: ButtonPreset.SOLID,
                    },
                    input: {
                        preset: SearchBarPresets.BORDER,
                    },
                },
                searchBar: {
                    border: {
                        radius: 20,
                    },
                },
            },
        },
    },
    () => (
        <>
            <StoryBannerWithScope
                title="Search on bg color"
                description={`Note that this isn't really "banner" related, but since the other variations for the search are here, it makes sense to have them side by side.`}
            />
        </>
    ),
);

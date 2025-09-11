/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryBannerWithScope } from "@library/banner/Banner.storyFixtures";
import { BannerAlignment } from "@library/banner/Banner.variables";
import { SearchBarPresets } from "@library/banner/SearchBarPresets";
import { ButtonPreset } from "@library/forms/ButtonPreset";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { STORY_LOGO_BLACK } from "@library/storybook/storyData";
import { color } from "csx";

export default {
    title: "Widgets/Banner",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

export const Default = storyWithConfig({ useWrappers: false }, () => <StoryBannerWithScope title="Standard" />);

export const SquareRadius = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            banner: {
                border: {
                    radius: 0,
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
                border: {
                    radius: 20,
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
                    unsetBackground: true,
                },
                description: {
                    font: {
                        color: color("#323232"),
                    },
                },
                rightImage: {
                    image: "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                padding: {
                    top: 87,
                    bottom: 87,
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
                    unsetBackground: true,
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
                padding: {
                    top: 87,
                    bottom: 87,
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
                    unsetBackground: true,
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
                padding: {
                    top: 87,
                    bottom: 87,
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
                    unsetBackground: true,
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                rightImage: {
                    image: "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                logo: {
                    width: 150,
                    image: STORY_LOGO_BLACK,
                },
                padding: {
                    top: 87,
                    bottom: 87,
                },
            },
        },
    },
    () => <StoryBannerWithScope title="Image-o-rama!" />,
);

ImageAsElement.parameters = {
    chromatic: {
        viewports: [1400, oneColumnVariables().contentWidth, oneColumnVariables().breakPoints.oneColumn, 400],
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
                    unsetBackground: true,
                },
                description: {
                    font: {
                        color: "#323232",
                    },
                },
                rightImage: {
                    image: "https://user-images.githubusercontent.com/1770056/73629535-7fc98600-4621-11ea-8f0b-06b21dbd59e3.png",
                },
                padding: {
                    top: 87,
                    bottom: 87,
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

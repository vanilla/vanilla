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
import { color, rgb } from "csx";
import Banner from "@library/banner/Banner";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { BannerAlignment, SearchBarPresets } from "@library/banner/bannerStyles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonPreset, ButtonTypes } from "@library/forms/buttonStyles";

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
                imageElement: {
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
                imageElement: {
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
                imageElement: {
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

// Only works with button
export const unifiedBorder = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            colors: {
                primary: "#ff5b64",
                primaryContrast: "#e3e3e3",
            },
            banner: {
                outerBackground: {
                    color: "#980013",
                    image: "linear-gradient(215.7deg, #FFFDFC 16.08%, #FFF6F5 63.71%), #C4C4C4",
                },
                presets: {
                    input: {
                        preset: SearchBarPresets.UNIFIED_BORDER,
                    },
                },
            },
        },
    },
    () => <StoryBanner title="Search with shadow" />,
);

(ImageAsElementWide as any).story = {
    parameters: {
        chromatic: {
            viewports: [1450, 1350, layoutVariables().panelLayoutBreakPoints.oneColumn, 400],
        },
    },
};

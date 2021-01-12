/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { MemoryRouter } from "react-router";
import { BannerContextProvider } from "@library/banner/BannerContext";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import TitleBar from "@library/headers/TitleBar";
import { sampleImages } from "@library/embeddedContent/storybook/attachments/sampleAttachmentImages";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { capitalizeFirstLetter } from "@vanilla/utils";

export default {
    title: "Headers/Title Bar",
    parameters: {
        chromatic: {
            viewports: [
                1450,
                layoutVariables().panelLayoutBreakPoints.twoColumns,
                layoutVariables().panelLayoutBreakPoints.oneColumn,
                layoutVariables().panelLayoutBreakPoints.xs,
            ],
        },
    },
};

interface ITitleBarLogoCase {
    src: string;
    type: string;
    ratio: string;
}

const getVariationsOfRatio = (ratio: "square" | "flush" | "tall" | "wide") => {
    const testCases: ITitleBarLogoCase[] = [];

    Object.keys(sampleImages[ratio]).forEach((type) => {
        const src = sampleImages[ratio][type];
        testCases.push({
            ratio,
            src,
            type,
        });
    });
    return testCases;
};

const TitleBarLogoTests = (props: { title?: string }) => {
    const { title = "Testing all logo cases" } = props;

    const squareRatio = getVariationsOfRatio("square");
    const tallRatio = getVariationsOfRatio("tall");
    const wideRatio = getVariationsOfRatio("wide");

    const content = [...squareRatio, ...tallRatio, ...wideRatio].map((testCase, index) => {
        return (
            <MemoryRouter key={index}>
                <BannerContextProvider>
                    <TitleBarDeviceProvider>
                        <StoryHeading>{`${capitalizeFirstLetter(testCase.type)} ${testCase.ratio} logo`}</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} overwriteLogo={testCase.src} />
                    </TitleBarDeviceProvider>
                </BannerContextProvider>
            </MemoryRouter>
        );
    });
    return <>{content}</>;
};

// Logo tests

// Case min min

const minHeight = 48;
const maxHeight = 88;

export const LogoTestMinHeights = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            titleBar: {
                sizing: {
                    height: minHeight,
                    mobile: {
                        height: minHeight,
                    },
                },
            },
            logo: {
                mobile: {
                    maxHeight: minHeight,
                },
            },
        },
    },
    () => <TitleBarLogoTests />,
);

export const LogoTestMaxHeights = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            titleBar: {
                sizing: {
                    height: maxHeight,
                    mobile: {
                        height: maxHeight,
                    },
                },
            },
            logo: {
                mobile: {
                    maxHeight: maxHeight,
                },
            },
        },
    },
    () => <TitleBarLogoTests />,
);

export const LogoTestMinDesktopMaxMobile = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            titleBar: {
                sizing: {
                    height: minHeight,
                    mobile: {
                        height: maxHeight,
                    },
                },
            },
            logo: {
                mobile: {
                    maxHeight: minHeight * 2, // way too big, should be scaled down
                },
            },
        },
    },
    () => <TitleBarLogoTests />,
);

export const LogoTestMaxDesktopMinMobile = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            titleBar: {
                sizing: {
                    height: maxHeight,
                    mobile: {
                        height: minHeight,
                    },
                },
            },
            logo: {
                mobile: {
                    maxHeight: minHeight,
                },
            },
        },
    },
    () => <TitleBarLogoTests />,
);

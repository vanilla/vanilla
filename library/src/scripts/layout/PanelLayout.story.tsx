/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { useStoryConfig, NO_WRAPPER_CONFIG, storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "PanelLayout",
    parameters: {
        chromatic: {
            viewports: Object.values(layoutVariables().panelLayoutBreakPoints),
        },
    },
};

const smallIpsum = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse ac arcu massa. Cras lobortis orci turpis, non viverra ex laoreet a. Sed dolor nisi, condimentum tincidunt gravida nec, pellentesque at felis. Phasellus vitae efficitur nibh, at ultricies tortor. Curabitur mauris lectus, luctus non est sit amet, elementum consectetur augue. Nullam non erat at tellus tincidunt ultricies quis non mi. Quisque vestibulum, nibh a sodales porta, neque diam iaculis lorem, in interdum tortor erat ut eros. Suspendisse magna lorem, euismod at bibendum id, semper non massa. Sed tempus orci dignissim molestie tempus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam eget dolor ut ex consequat egestas quis eget sem. Nulla sit amet orci porta, feugiat nisl scelerisque, ultrices lorem. Suspendisse potenti. Cras et arcu vitae libero congue porta et vel orci. Morbi tincidunt massa et euismod sodales. Aliquam vel massa facilisis, volutpat massa sit amet, laoreet risus.`;
const largeIpsum = smallIpsum + smallIpsum + smallIpsum + smallIpsum + smallIpsum;

const DummyPanel = (props: { bg?: string; children?: React.ReactNode }) => {
    return (
        <PanelWidget>
            <div style={{ background: props.bg || "#444", color: "white", padding: 12 }}>{props.children}</div>
        </PanelWidget>
    );
};

export const SimplePanels = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top{largeIpsum}</DummyPanel>}
                middleTop={<DummyPanel>Middle Top</DummyPanel>}
                middleBottom={<DummyPanel>Middle Bottom{largeIpsum}</DummyPanel>}
                rightTop={<DummyPanel>Right Top{largeIpsum}</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const LargeContent = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top</DummyPanel>}
                leftBottom={<DummyPanel>Left Bottom</DummyPanel>}
                middleTop={<DummyPanel>Middle Top</DummyPanel>}
                middleBottom={<DummyPanel>Middle Bottom{largeIpsum}</DummyPanel>}
                rightTop={<DummyPanel>Right Top</DummyPanel>}
                rightBottom={<DummyPanel>Right bottom</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const LargeLeftPanel = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top {largeIpsum}</DummyPanel>}
                leftBottom={<DummyPanel>Left Bottom</DummyPanel>}
                middleTop={<DummyPanel>Middle Top</DummyPanel>}
                middleBottom={<DummyPanel>Middle Bottom</DummyPanel>}
                rightTop={<DummyPanel>Right Top</DummyPanel>}
                rightBottom={<DummyPanel>Right bottom</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const LargeRightTopPanel = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top</DummyPanel>}
                leftBottom={<DummyPanel>Left Bottom</DummyPanel>}
                middleTop={<DummyPanel>Middle Top</DummyPanel>}
                middleBottom={<DummyPanel>Middle Bottom</DummyPanel>}
                rightTop={<DummyPanel>Right Top {largeIpsum}</DummyPanel>}
                rightBottom={<DummyPanel>Right bottom</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const LargeRightBottomPanel = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top</DummyPanel>}
                leftBottom={<DummyPanel>Left Bottom</DummyPanel>}
                middleTop={<DummyPanel>Middle Top</DummyPanel>}
                middleBottom={
                    <DummyPanel>
                        Middle Bottom{smallIpsum}
                        {smallIpsum}
                    </DummyPanel>
                }
                rightTop={<DummyPanel>Right Top</DummyPanel>}
                rightBottom={<DummyPanel>Right bottom {largeIpsum}</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const LargeEverything = storyWithConfig(NO_WRAPPER_CONFIG, () => {
    return (
        <DeviceProvider>
            <PanelLayout
                leftTop={<DummyPanel>Left Top{largeIpsum}</DummyPanel>}
                leftBottom={<DummyPanel>Left Bottom{largeIpsum}</DummyPanel>}
                middleTop={<DummyPanel>Middle Top{largeIpsum}</DummyPanel>}
                middleBottom={<DummyPanel>Middle Bottom{largeIpsum}</DummyPanel>}
                rightTop={<DummyPanel>Right Top{largeIpsum}</DummyPanel>}
                rightBottom={<DummyPanel>Right bottom {largeIpsum}</DummyPanel>}
            ></PanelLayout>
        </DeviceProvider>
    );
});

export const DarkMode = storyWithConfig(
    {
        ...NO_WRAPPER_CONFIG,
        themeVars: {
            global: {
                mainColors: {
                    bg: "#333",
                    fg: "#fff",
                },
            },
        },
    },
    () => {
        return (
            <DeviceProvider>
                <PanelLayout
                    leftTop={<DummyPanel>Left Top{largeIpsum}</DummyPanel>}
                    leftBottom={<DummyPanel>Left Bottom{largeIpsum}</DummyPanel>}
                    middleTop={<DummyPanel>Middle Top{largeIpsum}</DummyPanel>}
                    middleBottom={<DummyPanel>Middle Bottom{largeIpsum}</DummyPanel>}
                    rightTop={<DummyPanel>Right Top{largeIpsum}</DummyPanel>}
                    rightBottom={<DummyPanel>Right bottom {largeIpsum}</DummyPanel>}
                ></PanelLayout>
            </DeviceProvider>
        );
    },
);

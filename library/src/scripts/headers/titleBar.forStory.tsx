/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { MemoryRouter } from "react-router";
import TitleBar from "@library/headers/TitleBar";
import PageContext from "@library/routing/PagesContext";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { color, linearGradient } from "csx";
import { BannerContextProvider } from "@library/banner/BannerContext";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Container from "@library/layout/components/Container";
import { PanelArea } from "@library/layout/PanelLayout";
import { StoryLongText } from "@library/storybook/StoryLongText";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

export function StoryTitleBar(props: { title: string; signedIn?: boolean; store?: any }) {
    return (
        <PageContext.Provider
            value={{
                pages: {},
            }}
        >
            <MemoryRouter>
                <BannerContextProvider>
                    <Provider store={props.store}>
                        <TitleBarDeviceProvider>
                            <StoryHeading depth={2}>{props.title}</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={true} />
                            <Container>
                                <PanelArea>
                                    <StoryLongText />
                                </PanelArea>
                            </Container>
                        </TitleBarDeviceProvider>
                    </Provider>
                </BannerContextProvider>
            </MemoryRouter>
        </PageContext.Provider>
    );
}

export const titleBarCases = (initialState: any) => {
    return {
        standard: storyWithConfig({ useWrappers: false }, () => (
            <StoryTitleBar title="Standard" store={getStore(initialState, true)} />
        )),
        withGradientAndSwoop: storyWithConfig(
            {
                useWrappers: false,
                themeVars: {
                    global: {
                        mainColors: {
                            fg: color("#858585"),
                            bg: color("#fff"),
                            primary: color("#ccd8e8"),
                        },
                    },
                    titleBar: {
                        sizing: {
                            height: 60,
                        },
                        mobile: {
                            height: 50,
                        },
                        fullBleed: {
                            enabled: true,
                        },
                        colors: {
                            fg: color("#000"),
                            bg: color("#f7f4ef"),
                        },
                        generatedColors: {
                            state: color("#e8e0d6"),
                        },
                        overlay: {
                            background: linearGradient(`-180deg`, `#f7f4ef,#ccd8e8`),
                        },
                        border: {
                            type: BorderType.SHADOW_AS_BORDER,
                            color: color("#e2d5c7"),
                            width: 4,
                        },
                        spacing: {
                            padding: {
                                top: 6,
                                bottom: 15,
                            },
                        },
                        swoop: {
                            amount: 60,
                        },
                        logo: {
                            offsetVertical: {
                                mobile: {
                                    amount: 3,
                                },
                            },
                        },
                    },
                },
            },
            () => <StoryTitleBar title="With Gradient and Swoop" store={getStore(initialState, true)} />,
        ),
        withGradientAndImageOnSticky: storyWithConfig(
            {
                useWrappers: false,
                themeVars: {
                    global: {
                        mainColors: {
                            fg: color("#858585"),
                            bg: color("#fff"),
                            primary: color("#ccd8e8"),
                        },
                    },
                    titleBar: {
                        sizing: {
                            height: 60,
                        },
                        mobile: {
                            height: 50,
                        },
                        fullBleed: {
                            enabled: true,
                        },
                        colors: {
                            fg: color("#000"),
                            bg: color("#f7f4ef"),
                        },
                        generatedColors: {
                            state: color("#e8e0d6"),
                        },
                        overlay: {
                            background: linearGradient(`-180deg`, `#f7f4ef,#ccd8e8`),
                        },
                        border: {
                            type: BorderType.SHADOW_AS_BORDER,
                            color: color("#e2d5c7"),
                            width: 4,
                        },
                        spacing: {
                            padding: {
                                top: 6,
                                bottom: 15,
                            },
                        },
                        swoop: {
                            amount: 60,
                        },
                        logo: {
                            offsetVertical: {
                                mobile: {
                                    amount: 3,
                                },
                            },
                        },
                    },
                },
            },
            () => <StoryTitleBar title="With Gradient and Swoop" store={getStore(initialState, true)} />,
        ),
    };
};

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { MemoryRouter } from "react-router";
import TitleBar from "@library/headers/TitleBar";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { loadTranslations } from "@vanilla/i18n";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { color, linearGradient } from "csx";
import { BannerContextProvider } from "@library/banner/BannerContext";
import { colorOut } from "@library/styles/styleHelpersColors";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Container from "@library/layout/components/Container";
import { PanelArea } from "@library/layout/PanelLayout";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

export default {
    title: "Headers",
    parameters: {
        chromatic: {
            viewports: [1400, 400],
        },
    },
};

const makeMockRegisterUser: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
};

const initialState = testStoreState({
    users: {
        current: {
            status: LoadStatus.SUCCESS,
            data: makeMockRegisterUser,
        },
    },
    theme: {
        assets: {
            data: {
                logo: {
                    type: "image",
                    url: localLogoUrl as string,
                },
            },
        },
    },
});

function StoryTitleBar(props: { title: string }) {
    let dummyData: React.ReactNode[] = [];
    for (let i = 0; i < 1000; i++) {
        dummyData.push(<p>Scrollable content</p>);
    }

    return (
        <MemoryRouter>
            <BannerContextProvider>
                <TitleBarDeviceProvider>
                    <StoryHeading depth={2}>{props.title}</StoryHeading>
                    <TitleBar useMobileBackButton={false} isFixed={true} />
                    <Container>
                        <PanelArea>{dummyData}</PanelArea>
                    </Container>
                </TitleBarDeviceProvider>
            </BannerContextProvider>
        </MemoryRouter>
    );
}

export const Standard = storyWithConfig({ useWrappers: false }, () => <StoryTitleBar title="Standard" />);

export const WithGradientAndSwoop = storyWithConfig(
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
    () => <StoryTitleBar title="With Gradient and Swoop" />,
);

export const WithGradientAndImageOnSticky = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            titleBar: {
                fullBleed: {
                    enabled: true,
                },
                colors: {
                    fg: color("#fff"),
                    bg: color("#756683"),
                    bgImage: `https://us.v-cdn.net/5022541/uploads/289/0TH4LBY14AI9.jpeg`,
                },
                overlay: {
                    background: linearGradient(
                        `-180deg`,
                        `${colorOut(color("#fdfcfa").fade(0.5))},${colorOut(color("#2e2c2b").fade(0.5))}`,
                    ),
                },
            },
        },
    },
    () => <StoryTitleBar title="With Gradient" />,
);

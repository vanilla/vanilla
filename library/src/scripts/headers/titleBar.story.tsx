/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { BannerContextProvider } from "@library/banner/BannerContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import TitleBar, { TitleBar as TitleBarStatic } from "@library/headers/TitleBar";
import { DownTriangleIcon, GlobeIcon } from "@library/icons/common";
import Container from "@library/layout/components/Container";
import PanelArea from "@library/layout/components/PanelArea";
import PanelWidget from "@library/layout/components/PanelWidget";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import getStore from "@library/redux/getStore";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryFullPage } from "@library/storybook/StoryFullPage";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { testStoreState } from "@library/__tests__/testStoreState";
import { loadTranslations } from "@vanilla/i18n";
import { color, linearGradient } from "csx";
import React from "react";
import { Provider } from "react-redux";
import { MemoryRouter } from "react-router";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import { ScrollOffsetProvider } from "@library/layout/ScrollOffsetContext";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

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

export default {
    title: "Headers/Title Bar",
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

function StoryTitleBar(props: {
    title: string;
    openSearch?: boolean;
    scope?: boolean;
    forceMenuOpen?: INavigationVariableItem;
}) {
    let dummyData: React.ReactNode[] = [];
    for (let i = 0; i < 350; i++) {
        dummyData.push(<p>Scrollable content</p>);
    }

    return (
        <>
            <MemoryRouter>
                <BannerContextProvider>
                    <TitleBarDeviceProvider>
                        <ScrollOffsetProvider scrollWatchingEnabled={false}>
                            <StoryHeading depth={2}>{props.title}</StoryHeading>
                            <TitleBar
                                useMobileBackButton={false}
                                isFixed={true}
                                forceVisibility={props.openSearch}
                                scope={props.scope ? scope : undefined}
                                forceMenuOpen={props.forceMenuOpen}
                            />
                            <Container>
                                <PanelArea>
                                    <PanelWidget>{dummyData}</PanelWidget>
                                </PanelArea>
                            </Container>
                        </ScrollOffsetProvider>
                    </TitleBarDeviceProvider>
                </BannerContextProvider>
            </MemoryRouter>
        </>
    );
}

export const Standard = storyWithConfig({ useWrappers: false }, () => <StoryTitleBar title="Standard" />);

export const OpenSearch = storyWithConfig({ useWrappers: false }, () => (
    <StoryTitleBar title="Open Search" openSearch={true} />
));

export const OpenSearchWithScope = storyWithConfig({ useWrappers: false }, () => (
    <StoryTitleBar title="Open Search" openSearch={true} scope={true} />
));

export const CustomContainer = storyWithConfig(
    {
        useWrappers: false,
        themeVars: { titleBar: { titleBarContainer: { maxWidth: "100%", gutterSpacing: { horizontal: 60 } } } },
    },
    () => <StoryTitleBar title="Custom Container" />,
);

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
    () => (
        <>
            <StoryTitleBar title="With Gradient and Swoop" />
        </>
    ),
);
export const WithGradientAndSwoopOpenSearch = storyWithConfig(
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
    () => (
        <>
            <StoryTitleBar title="With Gradient and Swoop" scope={true} openSearch={true} />
        </>
    ),
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
                        `${ColorsUtils.colorOut(color("#fdfcfa").fade(0.5))},${ColorsUtils.colorOut(
                            color("#2e2c2b").fade(0.5),
                        )}`,
                    ),
                },
            },
        },
    },
    () => <StoryTitleBar title="With Gradient" />,
);

loadTranslations({});

const makeMockGuestUser: IMe = {
    name: "test",
    userID: 0,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
};

export const TitleBarGuestUser = storyWithConfig(
    {
        storeState: {
            users: {
                current: {
                    status: LoadStatus.SUCCESS,
                    data: makeMockGuestUser,
                },
            },
            theme: {
                assets: {
                    data: {
                        logo: {
                            type: "image",
                            url: localLogoUrl,
                        },
                    },
                },
            },
        },
    },
    () => {
        TitleBarStatic.registerBeforeMeBox(() => {
            return (
                <Button baseClass={ButtonTypes.TITLEBAR_LINK}>
                    <>
                        <GlobeIcon />
                        <DownTriangleIcon />
                    </>
                </Button>
            );
        });
        return (
            <MemoryRouter>
                <Provider store={getStore(initialState, true)}>
                    <TitleBarDeviceProvider>
                        <StoryFullPage>
                            <StoryHeading>Hamburger menu</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} />
                            <StoryHeading>Hamburger menu - open </StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />
                            <StoryHeading>Hamburger menu - open with scope</StoryHeading>
                            <TitleBar
                                useMobileBackButton={false}
                                isFixed={false}
                                forceVisibility={true}
                                scope={scope}
                            />

                            <StoryHeading>Big Logo</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} />

                            <StoryHeading>Big Logo - open</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />
                            <StoryHeading>Big Logo - open with scope</StoryHeading>
                            <TitleBar
                                useMobileBackButton={false}
                                isFixed={false}
                                forceVisibility={true}
                                scope={scope}
                            />
                            <StoryHeading>Extra Navigation links</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} />
                            <StoryHeading>Extra Navigation links - open</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />
                            <StoryHeading>Extra Navigation links - open with scope</StoryHeading>
                            <TitleBar
                                useMobileBackButton={false}
                                isFixed={false}
                                forceVisibility={true}
                                scope={scope}
                            />
                        </StoryFullPage>
                    </TitleBarDeviceProvider>
                </Provider>
            </MemoryRouter>
        );
    },
);

const navigationItems: INavigationVariableItem[] = [
    {
        id: "builtin-discussions",
        url: "/discussions",
        name: "Discussions",
        children: [
            {
                id: "b4e72a07-5d18-441d-9bbe-cc31c3091be4",
                children: [],
                name: "No Children Item",
                url: "http://www.pinterest.com",
                isCustom: true,
            },
            {
                id: "ab05ed8f-98a2-4d14-a3aa-a4a9dcd32237",
                children: [
                    {
                        id: "670a3c94-5032-434d-ae32-83ba6093bfb2",
                        children: [],
                        name: "Images",
                        url: "/",
                        isCustom: true,
                    },
                    {
                        id: "111cd30b-afd7-40ff-9a76-bcb05e58c607",
                        children: [],
                        name: "Chrome",
                        url: "/",
                        isCustom: true,
                    },
                    {
                        id: "0c84a013-8365-4f34-adb8-be9b401b6bb7",
                        children: [],
                        name: "Android",
                        url: "/",
                        isCustom: true,
                    },
                    {
                        id: "9e8818a0-e40a-49aa-84a8-26979c03b6d4",
                        children: [],
                        name: "Docs",
                        url: "/",
                        isCustom: true,
                    },
                    {
                        id: "c8b927fd-d76f-4879-85fc-3b8640b4561f",
                        children: [],
                        name: "Music",
                        url: "/",
                        isCustom: true,
                    },
                    {
                        id: "79a8a665-070e-497d-90a9-a06359c32de9",
                        children: [],
                        name: "Play",
                        url: "/",
                        isCustom: true,
                    },
                ],
                name: "Google",
                url: "http://www.google.ca",
                isCustom: true,
            },
            {
                id: "972af107-287c-4553-909f-8c6f61dad093",
                children: [
                    {
                        id: "97da68d0-9658-4cc5-9483-48736ea486a9",
                        children: [],
                        name: "AskJeeves",
                        url: "www.askjeeves.ca",
                        isCustom: true,
                    },
                    {
                        id: "e1312bff-a083-449d-b90f-2e6eb526dd34",
                        children: [],
                        name: "Yahoo",
                        url: "www.yahoo.ca",
                        isCustom: true,
                    },
                    {
                        id: "23c61f00-c97c-4673-acee-043f54cd09ac",
                        children: [],
                        name: "Bing",
                        url: "http://www.bing.com",
                        isCustom: true,
                    },
                ],
                name: "Search Engines",
                url: "/",
                isCustom: true,
            },
            {
                id: "6b17455c-01dc-4335-af30-424fa2c5b47a",
                children: [
                    {
                        id: "39fbb486-9708-413a-bb54-d315368c2af6",
                        children: [],
                        name: "Buzzfeed",
                        url: "www.buzzfeed.com",
                        isCustom: true,
                    },
                    {
                        id: "83faec20-1aae-4f46-ad5a-0e0e21bc6fde",
                        children: [],
                        name: "Highsnobiety",
                        url: "www.highsnobiety.com",
                        isCustom: true,
                    },
                    {
                        id: "697bb8c7-a181-417d-b4c3-46658bb4cffa",
                        children: [],
                        name: "Reddit",
                        url: "http://reddit.ca",
                        isCustom: true,
                    },
                ],
                name: "Popular Sites",
                url: "http://popularsites.com",
                isCustom: true,
            },
        ],
    },
    {
        id: "builtin-categories",
        url: "/categories",
        name: "Categories",
        children: [
            {
                id: "3459f3f9-2f1e-4922-a5ab-d3b3675c03e2",
                children: [],
                name: "Canada",
                url: "canada.ca",
                isCustom: true,
            },
            {
                id: "ea71c884-f378-4f9e-b717-1eda30f3b225",
                children: [],
                name: "England",
                url: "uk.org",
                isCustom: true,
            },
            {
                id: "15fbf71d-2710-4021-98db-bdbef411ac40",
                children: [],
                name: "USA",
                url: "usa.com",
                isCustom: true,
            },
        ],
    },
    {
        name: "Help",
        permission: "kb.view",
        url: "/kb",
        children: [],
        id: "builtin-kb",
    },
];

export const TitleBarWithMegaMenu = storyWithConfig(
    { useWrappers: false, themeVars: { navigation: { navigationItems } } },
    () => <StoryTitleBar forceMenuOpen={navigationItems[0]} title="With Mega Menu" />,
);

export const WithMobileOnlyNavigationItems = storyWithConfig(
    {
        useWrappers: false,
        themeVars: {
            navigation: {
                navigationItems: [
                    {
                        url: "/discussions",
                        name: "Discussions",
                    },
                    {
                        url: "/categories",
                        name: "Categories",
                    },
                ],

                mobileOnlyNavigationItems: [
                    {
                        id: "mobileOnlyItem",
                        url: "/somePath",
                        name: "Mobile-only Link",
                    },
                ],
            },
        },
    },
    () => <StoryTitleBar title="With Mobile-only Navigation Items" />,
);

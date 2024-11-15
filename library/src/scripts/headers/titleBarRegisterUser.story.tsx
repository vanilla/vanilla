/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { useEffect } from "react";
import { MemoryRouter } from "react-router";
import TitleBar, { TitleBar as TitleBarStatic } from "@library/headers/TitleBar";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { testStoreState } from "@library/__tests__/testStoreState";
import { IMe } from "@library/@types/api/users";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { DownTriangleIcon } from "@library/icons/common";
import { loadTranslations } from "@vanilla/i18n";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { StoryFullPage } from "@library/storybook/StoryFullPage";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { setMeta } from "@library/utility/appUtils";
import localLogoUrl from "./titleBarStoryLogo.png";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { ReduxThemeContextProvider } from "@library/theming/Theme.context";
import { IThemeVariables } from "@library/theming/themeReducer";
import { LoadStatus } from "@library/@types/api/core";

loadTranslations({});

export default {
    title: "Headers/Title Bar",
};

const makeMockRegisterUser: IMe = {
    name: "Neena",
    userID: 1,
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
    emailConfirmed: true,
};

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

function TestTitleBar(props: { hasConversations: boolean }) {
    const initialState = testStoreState({
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
    useEffect(() => {
        TitleBarStatic.registerBeforeMeBox(() => {
            return (
                <Button buttonType={ButtonTypes.TITLEBAR_LINK}>
                    <>
                        English
                        <DownTriangleIcon />
                    </>
                </Button>
            );
        });

        setMeta("context.conversationsEnabled", props.hasConversations);
    }, []);
    return (
        <MemoryRouter>
            <Provider store={getStore(initialState, true)}>
                <ReduxThemeContextProvider>
                    <CurrentUserContextProvider currentUser={makeMockRegisterUser}>
                        <TitleBarDeviceProvider>
                            <StoryFullPage>
                                <StoryHeading>Hamburger menu</StoryHeading>
                                <TitleBar useMobileBackButton={false} isFixed={false} />

                                <StoryHeading>Hamburger menu - open</StoryHeading>
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
                    </CurrentUserContextProvider>
                </ReduxThemeContextProvider>
            </Provider>
        </MemoryRouter>
    );
}

export const TitleBarRegisteredUser = () => {
    return <TestTitleBar hasConversations={true} />;
};

TitleBarRegisteredUser.story = {
    name: "TitleBar Registered User",

    parameters: {
        chromatic: {
            viewports: [oneColumnVariables().breakPoints.noBleed, oneColumnVariables().breakPoints.xs],
        },
    },
};

export const TitleBarRegisteredUserNoConversations = () => {
    return <TestTitleBar hasConversations={false} />;
};

TitleBarRegisteredUserNoConversations.story = {
    name: "TitleBar Registered User (No Conversations)",

    parameters: {
        chromatic: {
            viewports: [oneColumnVariables().breakPoints.noBleed, oneColumnVariables().breakPoints.xs],
        },
    },
};

export const TitleBarRegisteredUserWithManyNavigationItemsAndCenterAligned = () => {
    const initialState = testStoreState({
        theme: {
            assets: {
                status: LoadStatus.SUCCESS,
                data: {
                    logo: {
                        type: "image",
                        url: localLogoUrl as string,
                    },
                    mobileLogo: {
                        type: "image",
                        url: localLogoUrl as string,
                    },
                    variables: {
                        type: "json",
                        data: {
                            titleBar: {
                                navAlignment: {
                                    alignment: "center",
                                },
                            },
                            navigation: {
                                navigationItems: [
                                    {
                                        id: "builtin-discussions",
                                        url: "/discussions",
                                        name: "Discussions",
                                        children: [],
                                    },
                                    {
                                        id: "builtin-discussions",
                                        url: "/categories",
                                        name: "Categories",
                                        children: [],
                                    },
                                    {
                                        id: "testNavigationItem1",
                                        url: "/testNavigationItem1",
                                        name: "Test Navigation Item 1",
                                        children: [],
                                    },
                                    {
                                        id: "testNavigationItem2",
                                        url: "/testNavigationItem2",
                                        name: "Test Navigation Item 2",
                                        children: [],
                                    },
                                    {
                                        id: "testNavigationItem3",
                                        url: "/testNavigationItem3",
                                        name: "Test Navigation Item 3",
                                        children: [],
                                    },
                                ],
                            },
                        },
                    },
                },
            },
        },
    });
    useEffect(() => {
        TitleBarStatic.registerBeforeMeBox(() => {
            return (
                <Button buttonType={ButtonTypes.TITLEBAR_LINK}>
                    <>
                        English
                        <DownTriangleIcon />
                    </>
                </Button>
            );
        });

        setMeta("context.conversationsEnabled", true);
    }, []);

    return (
        <MemoryRouter>
            <Provider store={getStore(initialState, true)}>
                <ReduxThemeContextProvider>
                    <CurrentUserContextProvider currentUser={makeMockRegisterUser}>
                        <TitleBarDeviceProvider>
                            <StoryFullPage>
                                <StoryHeading>
                                    If mebox section colides with nav items, titlebar will automatically switch to
                                    compact view
                                </StoryHeading>
                                <TitleBar useMobileBackButton={false} isFixed={false} />
                            </StoryFullPage>
                        </TitleBarDeviceProvider>
                    </CurrentUserContextProvider>
                </ReduxThemeContextProvider>
            </Provider>
        </MemoryRouter>
    );
};

TitleBarRegisteredUserWithManyNavigationItemsAndCenterAligned.story = {
    name: "TitleBar With Many Navigation Items And Center Aligned",
    parameters: {
        chromatic: {
            viewports: [oneColumnVariables().breakPoints.noBleed],
        },
    },
};

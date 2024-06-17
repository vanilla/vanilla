/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React, { useState, createRef, useEffect } from "react";
import { MemoryRouter, Router } from "react-router";
import TitleBar, { TitleBar as TitleBarStatic } from "@library/headers/TitleBar";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { DownTriangleIcon } from "@library/icons/common";
import { loadTranslations } from "@vanilla/i18n";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { StoryFullPage } from "@library/storybook/StoryFullPage";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import gdn from "@library/gdn";
import { setMeta } from "@library/utility/appUtils";
import localLogoUrl from "./titleBarStoryLogo.png";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { ReduxThemeContextProvider } from "@library/theming/Theme.context";

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

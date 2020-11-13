/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState, createRef, useEffect } from "react";
import { MemoryRouter, Router } from "react-router";
import TitleBar, { TitleBar as TitleBarStatic } from "@library/headers/TitleBar";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { DownTriangleIcon } from "@library/icons/common";
import { loadTranslations } from "@vanilla/i18n";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { StoryFullPage } from "@library/storybook/StoryFullPage";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import gdn from "@library/gdn";
import { setMeta } from "@library/utility/appUtils";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

const story = storiesOf("Headers/Title Bar", module);

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
    useEffect(() => {
        TitleBarStatic.registerBeforeMeBox(() => {
            return (
                <Button baseClass={ButtonTypes.TITLEBAR_LINK}>
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
                <TitleBarDeviceProvider>
                    <StoryFullPage>
                        <StoryHeading>Hamburger menu</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} />

                        <StoryHeading>Hamburger menu - open</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />

                        <StoryHeading>Hamburger menu - open with scope</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} scope={scope} />

                        <StoryHeading>Big Logo</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} />

                        <StoryHeading>Big Logo - open</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />

                        <StoryHeading>Big Logo - open with scope</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} scope={scope} />

                        <StoryHeading>Extra Navigation links</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} />

                        <StoryHeading>Extra Navigation links - open</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} />

                        <StoryHeading>Extra Navigation links - open with scope</StoryHeading>
                        <TitleBar useMobileBackButton={false} isFixed={false} forceVisibility={true} scope={scope} />
                    </StoryFullPage>
                </TitleBarDeviceProvider>
            </Provider>
        </MemoryRouter>
    );
}

story.add(
    "TitleBar Registered User",
    () => {
        return <TestTitleBar hasConversations={true} />;
    },
    {
        chromatic: {
            viewports: [layoutVariables().panelLayoutBreakPoints.noBleed, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
);

story.add(
    "TitleBar Registered User (No Conversations)",
    () => {
        return <TestTitleBar hasConversations={false} />;
    },
    {
        chromatic: {
            viewports: [layoutVariables().panelLayoutBreakPoints.noBleed, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
);

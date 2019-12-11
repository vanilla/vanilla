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
import { DeviceProvider } from "@library/layout/DeviceContext";
import PageContext from "@library/routing/PagesContext";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import { DownTriangleIcon } from "@library/icons/common";
import { loadTranslations } from "@vanilla/i18n";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

const story = storiesOf("TitleBar - RegisteredUser", module);

const makeMockRegisterUser: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
};

story.add(
    "Titlear",
    () => {
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

        return (
            <PageContext.Provider
                value={{
                    pages: {
                        // search?: IPageLoader;
                        // drafts?: IPageLoader;
                    },
                }}
            >
                <MemoryRouter>
                    <Provider store={getStore()}>
                        <DeviceProvider>
                            <StoryHeading>Hamburger menu</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} hamburger={true} />
                            <StoryHeading>Big Logo</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} hamburger={true} />
                            <StoryHeading>Extra Navigation links</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} navigationLinks={true} />
                        </DeviceProvider>
                    </Provider>
                </MemoryRouter>
            </PageContext.Provider>
        );
    },
    {
        chromatic: {
            viewports: [layoutVariables().panelLayoutBreakPoints.noBleed, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
);

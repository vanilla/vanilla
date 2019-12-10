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
import { addComponent } from "@library/utility/componentRegistry";
import { SubcommunityChooserDropdown } from "@subcommunities/chooser/SubcommunityChooser";
import { getMeta } from "@library/utility/appUtils";
import { CommunityFilterContext } from "@subcommunities/CommunityFilterContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";
import { DownTriangleIcon } from "@library/icons/common";
import { dummyStorybookNavigationData } from "./dummyStorybookNavigationData";
import TitleBarNav from "./mebox/pieces/TitleBarNav";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";
import classNames from "classnames";
import { loadTranslations } from "@vanilla/i18n";

const localLogoUrl = require("./titleBarStoryLogo.png");
loadTranslations({});
const story = storiesOf("TitleBar", module);
const classes = titleBarClasses();
const makeMockUnRegisterUser: IMe = {
    name: "test",
    userID: 0,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
};
const makeMockRegisterUser: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
};
const providerArgs = {
    hideNoProductCommunities: getMeta("featureFlags.SubcommunityProducts.Enabled"),
    linkSuffix: "/kb",
};
const ChooserWithProvider = props => (
    <CommunityFilterContext.Provider value={providerArgs}>
        <SubcommunityChooserDropdown {...props} />
    </CommunityFilterContext.Provider>
);

addComponent("subcommunity-chooser", ChooserWithProvider);

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

story.add(
    "TitleBar-Guest",
    () => {
        const initialState = testStoreState({
            users: {
                current: {
                    status: LoadStatus.SUCCESS,
                    data: makeMockUnRegisterUser,
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
                            <TitleBar useMobileBackButton={false} isFixed={false} />
                            <StoryHeading>Hamburger menu</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} hamburger={true} />
                            <StoryHeading>Big Logo</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} hamburger={true} />
                            <StoryHeading>Navigation links</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} navigationLinks={true} />
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
story.add(
    "TitleBar-RegisteredUser",
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
                            url: localLogoUrl,
                        },
                    },
                },
            },
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
                            <TitleBar useMobileBackButton={false} isFixed={false} />
                            <StoryHeading>Hamburger menu</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} hamburger={true} />
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

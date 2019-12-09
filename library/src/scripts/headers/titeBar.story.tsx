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
import { ThemeProvider } from "@library/theming/ThemeProvider";
const localLogoUrl = require("./titleBarStoryLogo.png");

const story = storiesOf("TitleBar", module);

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

const makeMockUser: IMe = {
    name: "test",
    userID: 0,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
};
const makeMockUser1: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
};

TitleBarStatic.registerBeforeMeBox(() => {
    return (
        <Button baseClass={ButtonTypes.TITLEBAR_LINK}>
            <>
                Extra Icon
                <DownTriangleIcon />
            </>
        </Button>
    );
});

story.add(
    "TitleBar",
    () => {
        const initialState = testStoreState({
            users: {
                current: {
                    status: LoadStatus.SUCCESS,
                    data: makeMockUser1,
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
                            <StoryHeading>TitleBar</StoryHeading>
                            <TitleBar useMobileBackButton={false} isFixed={false} />
                            <StoryHeading>TitleBar- openSearch</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} />
                            <StoryHeading>TitleBar- hamburger menu</StoryHeading>
                            <TitleBar useMobileBackButton={true} isFixed={false} hamburger={true} />
                            <StoryHeading>TitleBar- product dropdown</StoryHeading>
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

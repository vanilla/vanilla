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
import PageContext from "@library/routing/PagesContext";
import { loadTranslations } from "@vanilla/i18n";
import { TitleBarDeviceProvider } from "@library/layout/TitleBarContext";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { color, linearGradient } from "csx";
import { BannerContextProvider } from "@library/banner/BannerContext";
import { colorOut } from "@library/styles/styleHelpersColors";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Container from "@library/layout/components/Container";
import { PanelArea } from "@library/layout/PanelLayout";
import { StoryLongText } from "@library/storybook/StoryLongText";
import getStore from "@library/redux/getStore";
import { Provider } from "react-redux";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { StoryTitleBar, titleBarCases } from "./titleBar.forStory";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

export default {
    title: "Title Bar - Signed Out",
    parameters: {
        chromatic: {
            viewports: [
                layoutVariables().panelLayoutBreakPoints.noBleed,
                layoutVariables().panelLayoutBreakPoints.twoColumn,
                layoutVariables().panelLayoutBreakPoints.oneColumn,
                titleBarVariables().breakpoints.compact,
                layoutVariables().panelLayoutBreakPoints.xs,
            ],
        },
    },
};

const unregisteredUser: IMe = {
    name: "Guest",
    userID: 0,
    permissions: [],
    isAdmin: false,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 0,
};

const initialState = testStoreState({
    users: {
        current: {
            status: LoadStatus.SUCCESS,
            data: unregisteredUser,
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

const cases = titleBarCases(initialState);
export const Standard = cases.standard;
export const WithGradientAndSwoop = cases.withGradientAndSwoop;
export const WithGradientAndImageOnSticky = cases.withGradientAndImageOnSticky;

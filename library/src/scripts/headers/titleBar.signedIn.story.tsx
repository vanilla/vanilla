/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { loadTranslations } from "@vanilla/i18n";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { titleBarVariables } from "@library/headers/titleBarStyles";
import { titleBarCases } from "@library/headers/titleBar.forStory";

const localLogoUrl = require("./titleBarStoryLogo.png");

loadTranslations({});

export default {
    title: "Title Bar - Signed In",
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

const makeMockRegisterUser: IMe = {
    name: "Joe",
    userID: 1,
    permissions: [
        "activity.delete",
        "activity.view",
        "advancedNotifications.allow",
        "articles.add",
        "badges.view",
        "comments.email",
        "comments.me",
        "conversations.add",
        "conversations.email",
        "curation.manage",
        "discussions.email",
        "email.view",
        "flag.add",
        "kb.view",
        "polls.add",
        "profiles.edit",
        "profiles.view",
        "reactions.negative.add",
        "reactions.positive.add",
        "signIn.allow",
        "tags.add",
        "uploads.add",
        "uploads.add",
    ],
    isAdmin: true,
    photoUrl: "https://us.v-cdn.net/5018160/uploads/userpics/312/nMP9OO3P1P3U2.png",
    dateLastActive: "2020-04-03T14:32:00+00:00",
    countUnreadNotifications: 1,
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

const cases = titleBarCases(initialState);

export const Standard = cases.standard;
export const WithGradientAndSwoop = cases.withGradientAndSwoop;
export const WithGradientAndImageOnSticky = cases.withGradientAndImageOnSticky;

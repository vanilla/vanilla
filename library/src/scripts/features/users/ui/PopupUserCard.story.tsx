/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import PopupUserCard, { IUserCardInfo } from "@library/features/users/ui/PopupUserCard";
import { IUser } from "@vanilla/library/src/scripts/@types/api/users";
import { layoutVariables } from "@library/layout/panelLayoutStyles";

export default {
    component: PopupUserCard,
    title: "PopupUserCard",
    parameters: {
        chromatic: {
            viewports: [1450, 700, layoutVariables().panelLayoutBreakPoints.xs],
        },
    },
};

const v: IUserCardInfo = {
    email: "val@vanillaforums.com",
    userID: 1,
    name: "Valérie Robitaille",
    photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/164/nQQG7FTJACOTX.jpg",
    dateLastActive: "May 2014",
    dateJoined: "May 2017",
    label: "Product Manager",
    countDiscussions: 207,
    countComments: 3456,
};

const m: IUserCardInfo = {
    email: "anonymous@where.com",
    userID: 0,
    name: "Anonymous",
    photoUrl: "",
    dateLastActive: "May May 2014",
    dateJoined: "May 2017",
    label: null,
    countDiscussions: 207,
    countComments: 3456,
};

export const UserCardNoPhotoNoLabel = () => (
    <StoryContent>
        <PopupUserCard user={m} visible={true} />
    </StoryContent>
);

export const UserCardWithoutPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "email.view": false,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => (
        <StoryContent>
            <PopupUserCard user={v} visible={true} />
        </StoryContent>
    ),
);

export const UserCardWithPermission = storyWithConfig(
    {
        storeState: {
            users: {
                permissions: {
                    status: LoadStatus.SUCCESS,
                    data: {
                        isAdmin: false,
                        permissions: [
                            {
                                type: "global",
                                id: 1,
                                permissions: {
                                    "email.view": true,
                                },
                            },
                        ],
                    },
                },
            },
        },
    },
    () => (
        <StoryContent>
            <PopupUserCard user={v} visible={true} />
        </StoryContent>
    ),
);

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IMe, IUser, IUserFragment } from "@library/@types/api/users";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ITag } from "@library/features/tags/TagsReducer";

export const STORY_IMAGE = require("./storyDataImage.png");
export const STORY_ICON = require("./storyDataImage.png");

export const STORY_LOGO_WHITE = "https://us.v-cdn.net/6030677/uploads/1861f935b5982c0bec354466296d241f.png";
export const STORY_LOGO_BLACK = "https://us.v-cdn.net/5022541/uploads/067/Z28XXGPR2ZCS.png";

export const STORY_IPSUM_LONG =
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

export const STORY_IPSUM_MEDIUM = STORY_IPSUM_LONG.slice(0, 160) + "…";

export const STORY_IPSUM_SHORT = STORY_IPSUM_LONG.slice(0, 50) + "…";

export const STORY_DATE = "2019-05-05T15:51:23+00:00";

export const STORY_USER: IUser = {
    userID: 1,
    name: "Joe Walsh",
    dateLastActive: "2016-07-25 17:51:15",
    photoUrl: "https://user-images.githubusercontent.com/1770056/74098133-6f625100-4ae2-11ea-8a9d-908d70030647.png",
    label: "SuperModerator",
    title: "Manager",
    email: "joe.walsh@example.com",
    countDiscussions: 207,
    countComments: 3456,
    emailConfirmed: true,
    showEmail: true,
    bypassSpam: false,
    banned: 0,
    dateInserted: "2012-07-25 17:51:15",
    hidden: false,
    roles: [
        {
            roleID: 0,
            name: "Moderator",
        },
    ],
};

export const STORY_ME_ADMIN: IMe = {
    ...STORY_USER,
    isAdmin: true,
    permissions: [],
    countUnreadConversations: 0,
    countUnreadNotifications: 0,
};

export const StoryTextContent = (props: { firstTitle?: string }) => {
    return (
        <div style={{ padding: 16 }}>
            <h2>{props.firstTitle ?? STORY_IPSUM_SHORT}</h2>
            <p>{STORY_IPSUM_LONG}</p>
            <h2>{STORY_IPSUM_SHORT}</h2>
            <p>{STORY_IPSUM_LONG}</p>
        </div>
    );
};

export const STORY_CRUMBS: ICrumb[] = [
    { name: "Success", url: "https://dev.vanilla.localhost/en-hutch/kb/success" },
    {
        name: "Appearance (Theming)",
        url: "https://dev.vanilla.localhost/en-hutch/kb/categories/37-appearance-theming",
    },
];

export const STORY_TAGS: ITag[] = [
    {
        tagID: 1,
        name: "UserTag",
        urlcode: "usertag",
    },
    {
        tagID: 2,
        name: "User Tag2",
        urlcode: "usertag2",
    },
    {
        tagID: 3,
        name: "User Tag 3",
        urlcode: "usertag3",
    },
    {
        tagID: 3,
        name: "UserTag4",
        urlcode: "usertag4",
    },
];

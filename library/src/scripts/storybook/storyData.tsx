/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IMe, IUser, IUserFragment } from "@library/@types/api/users";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ITag } from "@library/features/tags/TagsReducer";
import random from "lodash/random";
import { slugify } from "@vanilla/utils";

export const STORY_IMAGE = require("./storyDataImage.png");
export const STORY_ICON = require("./storyDataImage.png");

export const STORY_LOGO_WHITE = "https://us.v-cdn.net/6030677/uploads/1861f935b5982c0bec354466296d241f.png";
export const STORY_LOGO_BLACK = "https://us.v-cdn.net/5022541/uploads/067/Z28XXGPR2ZCS.png";

export const STORY_IPSUM_LONG =
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

export const STORY_IPSUM_LONG2 =
    "Vivamus vitae purus euismod, porta nulla sed, dapibus tellus. Phasellus orci magna, lobortis a rhoncus ac, aliquet non arcu. Aliquam consectetur sodales nibh, vitae ultrices lectus accumsan id. Morbi ut metus mauris. Quisque posuere lectus vel est efficitur facilisis. Suspendisse vel tristique erat, a lacinia enim. Fusce placerat suscipit tellus ac luctus.";

export const STORY_IPSUM_LONG3 =
    "Proin neque est, mollis eu eleifend vel, viverra vel augue. Nulla euismod quam nec purus vestibulum, in pretium enim lacinia. Sed risus turpis, viverra in congue non, tincidunt a neque. Nulla tincidunt feugiat augue, eget finibus odio fermentum in. Pellentesque tincidunt lectus lorem, eget tincidunt risus congue ac. Sed luctus quam a interdum placerat. Ut ex sem, feugiat eu risus sed, sodales molestie tellus.";

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
    countPosts: 3663,
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
    private: false,
};

export const STORY_USER_BANNED: IUser = {
    ...STORY_USER,
    banned: 1,
};

export const STORY_USER_PRIVATE: IUser = {
    ...STORY_USER,
    private: true,
};

export const STORY_ME_ADMIN: IMe = {
    ...STORY_USER,
    isAdmin: true,
    permissions: [],
    countUnreadConversations: 0,
    countUnreadNotifications: 0,
};

export const STORY_LEADERS = [
    {
        user: STORY_USER,
        points: 320,
    },
    {
        user: {
            ...STORY_USER,
            name: "Christina Morton",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 2,
        },
        points: 280,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nazeem Kanaan",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 3,
        },
        points: 278,
    },
    {
        user: {
            ...STORY_USER,
            name: "Aiden Rosenstengel",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 4,
        },
        points: 254,
    },
    {
        user: {
            ...STORY_USER,
            name: "Tomás Barros",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 5,
        },
        points: 243,
    },
    {
        user: { ...STORY_USER, name: "Lan Tai", userID: 6 },
        points: 241,
    },
    {
        user: {
            ...STORY_USER,
            name: "Ella Jespersen",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 7,
        },
        points: 221,
    },
    {
        user: { ...STORY_USER, name: "Teus van Uum", userID: 8 },
        points: 212,
    },
    {
        user: {
            ...STORY_USER,
            name: "Michael Baker",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 9,
        },
        points: 206,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nicholas Lebrun",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 10,
        },
        points: 196,
    },
    {
        user: { ...STORY_USER, name: "Matthias Friedman", userID: 11 },
        points: 184,
    },
    {
        user: {
            ...STORY_USER,
            name: "Pupa Zito",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 12,
        },
        points: 165,
    },
    {
        user: {
            ...STORY_USER,
            name: "Phoebe Cunningham",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 13,
        },
        points: 164,
    },
];

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

export const getRandomIpsum = function () {
    const ipsums = [STORY_IPSUM_LONG, STORY_IPSUM_LONG2, STORY_IPSUM_LONG3];
    return ipsums[random(0, 2)];
};

export const storyTitleGenerator = function (length = 100) {
    const phrases = STORY_IPSUM_LONG.replace(/,/g, "").split(".");
    return phrases[random(0, 3)].slice(0, length).trim();
};

export const getArticlesStub = function (limit = 9, host = "#") {
    const stub = [] as any;

    for (let i = 0; i < limit; i++) {
        const title = STORY_IPSUM_SHORT;
        const slug = slugify(title);
        stub.push({
            id: `article-${i}`,
            to: `${host}/kb/articles/${slug}`,
            name: title,
            description: STORY_IPSUM_MEDIUM,
        });
    }

    return stub;
};

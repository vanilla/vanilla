/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IMe, IUser } from "@library/@types/api/users";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ITag } from "@library/features/tags/TagsReducer";
import random from "lodash-es/random";
import { slugify } from "@vanilla/utils";
import { ILeader } from "@library/leaderboardWidget/LeaderboardWidget";
import { IReaction } from "@dashboard/@types/api/reaction";
import { IPostReaction } from "@library/postReactions/PostReactions.types";
import STORY_IMAGE from "./storyDataImage.png";
import type { IComment } from "@dashboard/@types/api/comment";

export { STORY_IMAGE };
export const STORY_ICON = STORY_IMAGE;

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

export const STORY_DATE_STARTS = "2019-05-05T15:51:23+00:00";
export const STORY_DATE_ENDS = "2019-05-05T16:51:23+00:00";

export const STORY_USER: IUser = {
    admin: 0,
    isAdmin: false,
    isSysAdmin: false,
    isSuperAdmin: false,
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
    countUnreadConversations: 0,
    countUnreadNotifications: 0,
};

export const STORY_LEADERS: ILeader[] = [
    {
        user: STORY_USER,
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Christina Morton",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 2,
            title: "Product Manager",
        },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nazeem Kanaan",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 3,
            title: "Community Leader",
        },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Aiden Rosenstengel",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 4,
        },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Tomás Barros",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 5,
            label: "SuccessTeam",
        },
        points: 999,
    },
    {
        user: { ...STORY_USER, name: "Lan Tai", userID: 6 },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Ella Jespersen",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 7,
        },
        points: 999,
    },
    {
        user: { ...STORY_USER, name: "Teus van Uum", userID: 8 },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Michael Baker",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 9,
        },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Nicholas Lebrun",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 10,
        },
        points: 999,
    },
    {
        user: { ...STORY_USER, name: "Matthias Friedman", userID: 11 },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Pupa Zito",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 12,
        },
        points: 999,
    },
    {
        user: {
            ...STORY_USER,
            name: "Phoebe Cunningham",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 13,
        },
        points: 999,
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
    { name: "Success", url: "https://dev.vanilla.local/en-hutch/kb/success" },
    {
        name: "Appearance (Theming)",
        url: "https://dev.vanilla.local/en-hutch/kb/categories/37-appearance-theming",
    },
];

export const STORY_TAGS: ITag[] = [
    {
        tagID: 1,
        name: "UserTag",
        urlcode: "usertag",
        type: "User",
    },
    {
        tagID: 2,
        name: "User Tag2",
        urlcode: "usertag2",
        type: "User",
    },
    {
        tagID: 3,
        name: "User Tag 3",
        urlcode: "usertag3",
        type: "User",
    },
    {
        tagID: 3,
        name: "UserTag4",
        urlcode: "usertag4",
        type: "User",
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

export const STORY_COMMENT: IComment = {
    commentID: 999999,
    parentRecordType: "discussion",
    parentRecordID: 999999,
    categoryID: 1,
    insertUser: {
        ...STORY_USER,
        userID: 13,
    },
    insertUserID: 13,
    dateInserted: "2020-10-06T15:30:44+00:00",
    dateUpdated: "2020-10-06T15:30:44+00:00",
    score: 999,
    url: "https://vanillaforums.com/discussion/comment/999999#Comment_999999",
    attributes: {},
    body: "This content is generated by users on the site. You can't update it here.",
    name: "This content is generated by users on the site. You can't update it here.",
};

export const STORY_REACTIONS = [
    {
        tagID: 1,
        urlcode: "Promote",
        name: "Promote",
        class: "Positive",
        hasReacted: false,
        reactionValue: 5,
        count: 0,
    },
    {
        tagID: 2,
        urlcode: "Disagree",
        name: "Disagree",
        class: "Negative",
        hasReacted: false,
        reactionValue: 0,
        count: 3,
    },
    {
        tagID: 3,
        urlcode: "Agree",
        name: "Agree",
        class: "Positive",
        hasReacted: true,
        reactionValue: 1,
        count: 2,
    },
    {
        tagID: 4,
        urlcode: "Like",
        name: "Like",
        class: "Positive",
        hasReacted: false,
        reactionValue: 1,
        count: 0,
    },
    {
        tagID: 5,
        urlcode: "LOL",
        name: "LOL",
        class: "Positive",
        hasReacted: false,
        reactionValue: 0,
        count: 7,
    },
];

export function getMockReactionLog(reactions: IReaction[] = STORY_REACTIONS, counts?: Array<Partial<IReaction>>) {
    const reacted = reactions.filter(({ count }) => count && count > 0);
    const tmpLog: IPostReaction[] = [];

    reacted.forEach((reaction) => {
        const { tagID } = reaction;
        const passedCount = counts?.find((item) => item.tagID === tagID);
        const count = (passedCount ? passedCount.count : reaction.count) ?? 0;

        for (let userID = 1; userID <= count; userID++) {
            tmpLog.push({
                recordType: "Discussion",
                recordID: 1,
                tagID: tagID as number,
                userID,
                dateInserted: "2024-02-05T19:05:00.000Z",
                user: {
                    ...STORY_USER,
                    userID,
                    name: `Test User ${userID}`,
                },
                reactionType: reaction,
            });
        }
    });

    return tmpLog;
}

export const STORY_COMMENTS = [
    {
        ...STORY_COMMENT,
        insertUser: {
            ...STORY_USER,
            userID: 13,
        },
        insertUserID: 13,
        body: STORY_IPSUM_MEDIUM,
        reactions: STORY_REACTIONS,
    },
    {
        ...STORY_COMMENT,
        insertUser: {
            ...STORY_USER,
            name: "Christina Morton",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 2,
            title: "Product Manager",
        },
        insertUserID: 2,
        body: STORY_IPSUM_LONG,
        reactions: STORY_REACTIONS,
    },
    {
        ...STORY_COMMENT,
        insertUser: {
            ...STORY_USER,
            name: "Phoebe Cunningham",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/defaultavatar/nOGOPGSGY4ISZ.jpg",
            userID: 12,
        },
        insertUserID: 12,
        body: STORY_IPSUM_SHORT,
        reactions: STORY_REACTIONS,
    },
    {
        ...STORY_COMMENT,
        insertUser: {
            ...STORY_USER,
            name: "Pupa Zito",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/n2K5HYT9EZOF6.png",
            userID: 15,
        },
        insertUserID: 15,
        body: STORY_IPSUM_LONG2,
        reactions: STORY_REACTIONS,
    },
    {
        ...STORY_COMMENT,
        insertUser: {
            ...STORY_USER,
            name: "Ella Jespersen",
            photoUrl: "https://us.v-cdn.net/6032207/uploads/avatarstock/nA5BAUNMSEDPV.png",
            userID: 7,
        },
        insertUserID: 7,
        body: STORY_IPSUM_LONG3,
        reactions: STORY_REACTIONS,
    },
];

export const STORY_DISCUSSION = {
    discussionID: 9999999,
    type: "discussion",
    name: "Discussion Title",
    body: "This content is generated by users on the site. You can't update it here.<br><br>This content is generated by users on the site. You can't update it here. This content is generated by users on the site. You can't update it here.<br><br>This content is generated by users on the site. You can't update it here.",
    url: "https://vanillaforums.com/discussion/999999",
    canonicalUrl: "https://vanillaforums.com/discussion/999999",
    dateInserted: "2020-10-06T15:30:44+00:00",
    insertUserID: STORY_USER.userID,
    insertUser: STORY_USER,
    lastUser: STORY_USER,
    dateUpdated: "2020-10-06T15:30:44+00:00",
    dateLastComment: "2020-10-06T15:30:44+00:00",
    pinned: false,
    closed: false,
    score: 0,
    countViews: 999,
    countComments: 9999,
    categoryID: 1111111111111111,
    category: {
        name: "Category 1",
        url: "#",
        categoryID: 1111111111111111,
    },
    reactions: STORY_REACTIONS,
    statusID: 1,
};

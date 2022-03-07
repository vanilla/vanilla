/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IMe, IUser, IUserFragment } from "@library/@types/api/users";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ITag } from "@library/features/tags/TagsReducer";
import random from "lodash/random";
import { EventAttendance, IEvent } from "@groups/events/state/eventsTypes";
import { DeepPartial } from "redux";

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

export const STORY_EVENTS: DeepPartial<IEvent[]> = [
    {
        eventID: 1,
        name: "Watercolor for beginners",
        excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        dateStarts: STORY_DATE,
        location: "Your home",
        url: "http://google.ca",
        attending: EventAttendance.MAYBE,
    },
    {
        eventID: 2,
        name: "Painting with acrilic",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStarts: STORY_DATE,
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attending: EventAttendance.NOT_GOING,
    },
    {
        eventID: 3,
        name:
            "From all of us here, I want to wish you happy painting and God bless, my friends. Just make little strokes like that. Let your imagination just wonder around when you're doing these things. We want to use a lot pressure while using no pressure at all. It is a lot of fun.",
        excerpt: "No worries. No cares. Just float and wait for the wind to blow you around.",
        dateStarts: STORY_DATE,
        location: "Winnipeg, MB",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
    },
    {
        eventID: 4,
        name: "Just make little strokes like that.",
        dateStarts: STORY_DATE,
        url: "http://google.ca",
    },
    {
        eventID: 5,
        name: "Example with locations: - location URL, no location text",
        excerpt:
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
        dateStarts: STORY_DATE,
        safeLocationUrl: "http://google.ca",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
    },
    {
        eventID: 6,
        name: "Example with locations: - unreasonably long url text",
        excerpt:
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
        dateStarts: STORY_DATE,
        location:
            "Our character limit is 255, so this is an example of an unreasonably long text used as in the location field of for this event so we can test if the styles correctly handle a ridiculously long text like this which is unlikely, but it's a good idea to test",
        safeLocationUrl: "http://google.ca",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
    },
    {
        eventID: 7,
        name: "Watercolor for beginners",
        excerpt: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        dateStarts: STORY_DATE,
        location: "Your home",
        url: "http://google.ca",
        attending: EventAttendance.MAYBE,
    },
    {
        eventID: 8,
        name: "Painting with acrilic",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStarts: STORY_DATE,
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attending: EventAttendance.NOT_GOING,
    },
    {
        eventID: 9,
        name: "Just make little strokes like that. Let your imagination just wonder around.",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStarts: STORY_DATE,
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attending: EventAttendance.NOT_GOING,
    },
    {
        eventID: 10,
        name: "Just make little strokes like that. Let your imagination just wonder around.",
        excerpt:
            "Making all those little fluffies that live in the clouds. Everything's not great in life, but we can still find beauty in it. No worries. No cares. Just float and wait for the wind to blow you around. Of course he's a happy little stone, cause we don't have any other kind. We don't have anything but happy trees here. Once you learn the technique, ohhh! Turn you loose on the world; you become a tiger. Didn't you know you had that much power? You can move mountains. You can do anything. Fluff that up. Let's do that again. We'll take a little bit of Van Dyke Brown. Nothing wrong with washing your brush. Just think about these things in your mind - then bring them into your world. A thin paint will stick to a thick paint. If you overwork it you become a cloud killer. There's nothing worse than a cloud killer. In this world, everything can be happy. Maybe there's a happy little waterfall happening over here. We'll throw some old gray clouds in here just sneaking around and having fun. The secret to doing anything is believing that you can do it. Anything that you believe you can do strong enough, you can do. Anything. As long as you believe. Be careful. You can always add more - but you can't take it away. This is truly an almighty mountain. We have no limits to our world. We're only limited by our imagination. We'll paint one happy little tree right here. Anytime you learn something your time and energy are not wasted. Every time you practice, you learn more Everyone needs a friend. Friends are the most valuable things in the world. It is a lot of fun. Let's make some happy little clouds in our world. I like to beat the brush. Just let these leaves jump off the brush This painting comes right out of your heart.",
        dateStarts: STORY_DATE,
        location: "Zoom meeting with Bob Ross explaing the basic fundamentals of Watercolor",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
    },
    {
        eventID: 11,
        name:
            "From all of us here, I want to wish you happy painting and God bless, my friends. Just make little strokes like that. Let your imagination just wonder around when you're doing these things. We want to use a lot pressure while using no pressure at all. It is a lot of fun.",
        excerpt: "No worries. No cares. Just float and wait for the wind to blow you around.",
        dateStarts: STORY_DATE,
        location: "Winnipeg, MB",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
    },
    {
        eventID: 12,
        name: "Just make little strokes like that.",
        dateStarts: STORY_DATE,
        url: "http://google.ca",
    },
    {
        eventID: 13,
        name: "Example with locations: - location text, no location URL",
        excerpt:
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.",
        dateStarts: STORY_DATE,
        location: "Winnipeg, MB",
        url: "http://google.ca",
        attending: EventAttendance.GOING,
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

/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { DiscussionList } from "@library/features/discussions/DiscussionList.views";
import { STORY_IPSUM_MEDIUM, STORY_USER } from "@library/storybook/storyData";

export default {
    title: "Components/DiscussionLists",
};

const dummyUserFragment = STORY_USER;

const commonFields = {
    dateInserted: "2021-02-11 17:51:15",
    dateUpdated: "2021-02-03 17:51:15",
    type: "discussion",
    pinned: false,
    insertUserID: dummyUserFragment.userID,
    closed: false,
    score: 0,
};

const discussions = [
    {
        ...commonFields,
        url: "#",
        name: "With everything",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 10,
        tags: ["Annoucement", "Featured"],
        countViews: 10,
        countComments: 20,
        byText: "Most recent by",
        insertUser: dummyUserFragment,
        updateUser: dummyUserFragment,
        lastUser: dummyUserFragment,
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-2 17:51:15",
        type: "discussion",
        pinned: true,
    },
    {
        ...commonFields,
        url: "#",
        name: "With everything",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 2,
        tags: ["Annoucement"],
        countViews: 200,
        countComments: 1299,
        insertUser: dummyUserFragment,
        updateUser: dummyUserFragment,
        closed: true,
    },
    {
        ...commonFields,
        url: "#",
        name: "With everything",
        excerpt: STORY_IPSUM_MEDIUM,
        discussionID: 5,
        countViews: 1029,
        countComments: 11,
        insertUser: dummyUserFragment,
        updateUser: dummyUserFragment,
        dateInserted: "2021-02-11 17:51:15",
        dateUpdated: "2021-02-11 17:51:15",
    },
];

export const Default = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        return (
            <StoryContent>
                <DiscussionList discussions={discussions}></DiscussionList>
            </StoryContent>
        );
    },
);

export const Theme = storyWithConfig(
    {
        themeVars: {
            tags: {
                background: {
                    color: "#E7F0F7",
                },
                font: {
                    weight: "bold",
                },
                border: {
                    style: "none",
                },
            },
        },
    },
    () => {
        return (
            <StoryContent>
                <DiscussionList discussions={discussions}></DiscussionList>
            </StoryContent>
        );
    },
);

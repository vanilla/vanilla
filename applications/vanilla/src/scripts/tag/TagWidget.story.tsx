/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import { BorderType } from "@library/styles/styleHelpersBorders";

export default {
    title: "Widgets/Tag",
    parameters: {},
};

const stubTags = [
    {
        tagID: 1,
        id: 1,
        name: "Active",
        urlcode: "Active",
        parentTagID: null,
        countDiscussions: 2,
        urlCode: "Active",
    },
    {
        tagID: 15,
        id: 15,
        name: "Dislike",
        urlcode: "Dislike",
        parentTagID: null,
        countDiscussions: 2,
        urlCode: "Dislike",
    },
    {
        tagID: 16,
        id: 16,
        name: "Like",
        urlcode: "Like",
        parentTagID: null,
        countDiscussions: 1,
        urlCode: "Like",
    },
    {
        tagID: 22,
        id: 22,
        name: "artichoke",
        urlcode: "artichoke",
        parentTagID: null,
        countDiscussions: 1,
        urlCode: "artichoke",
    },
    {
        tagID: 25,
        id: 25,
        name: "ricebean",
        urlcode: "ricebean",
        parentTagID: null,
        countDiscussions: 1,
        urlCode: "ricebean",
    },
    {
        tagID: 31,
        id: 31,
        name: "question",
        urlcode: "question",
        parentTagID: null,
        countDiscussions: 1,
        urlCode: "question",
    },
    {
        tagID: 32,
        id: 32,
        name: "really long long long long long long long long tag",
        urlcode: "really long long long long long long long long tag",
        parentTagID: null,
        countDiscussions: 1,
        urlCode: "really long long long long long long long long tag",
    },
    {
        tagID: 2,
        id: 2,
        name: "Already Offered",
        urlcode: "Already Offered",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Already Offered",
    },
    {
        tagID: 3,
        id: 3,
        name: "Declined",
        urlcode: "Declined",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Declined",
    },
    {
        tagID: 4,
        id: 4,
        name: "Completed",
        urlcode: "Completed",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Completed",
    },
    {
        tagID: 5,
        id: 5,
        name: "In Progress",
        urlcode: "In Progress",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "In Progress",
    },
    {
        tagID: 6,
        id: 6,
        name: "In Review",
        urlcode: "In Review",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "In Review",
    },
    {
        tagID: 7,
        id: 7,
        name: "Down",
        urlcode: "Down",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Down",
    },
    {
        tagID: 8,
        id: 8,
        name: "Spam",
        urlcode: "Spam",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Spam",
    },
    {
        tagID: 9,
        id: 9,
        name: "Abuse",
        urlcode: "Abuse",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Abuse",
    },
    {
        tagID: 10,
        id: 10,
        name: "Promote",
        urlcode: "Promote",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Promote",
    },
    {
        tagID: 11,
        id: 11,
        name: "OffTopic",
        urlcode: "OffTopic",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "OffTopic",
    },
    {
        tagID: 12,
        id: 12,
        name: "Insightful",
        urlcode: "Insightful",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Insightful",
    },
    {
        tagID: 13,
        id: 13,
        name: "Disagree",
        urlcode: "Disagree",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Disagree",
    },
    {
        tagID: 14,
        id: 14,
        name: "Agree",
        urlcode: "Agree",
        parentTagID: null,
        countDiscussions: 0,
        urlCode: "Agree",
    },
];

const tagStubPropsShadow = {
    tags: stubTags,
    title: "Tag Cloud",
    subtitle: "Some subtitle here",
    description: "Read me, I'm a really important description. You don't want to miss it.",
    containerOptions: {
        borderType: BorderType.SHADOW as BorderType,
        headerAlignment: "center" as "left" | "center",
    },
};

const tagStubPropsBorder = {
    tags: stubTags,
    containerOptions: {
        outerBackground: {
            color: "#AEE4FE",
        },
        innerBackground: {
            color: "#FFFFFF",
        },
    },
};

const tagStubNaked = {
    tags: stubTags,
};

export const ContainerOptionsVariants = storyWithConfig({}, () => (
    <>
        <StoryHeading>{'containerOptions: { borderType: "shadow" , headerAlignment: "center"}'}</StoryHeading>
        <TagWidget {...tagStubPropsShadow} />
        <StoryHeading>
            {'containerOptions: { borderType: "none" , outerBackground: "#AEE4FE", innerBackground: "#FFFFFF"}'}
        </StoryHeading>
        <TagWidget {...tagStubPropsBorder} />
        <StoryHeading>{"naked"}</StoryHeading>
        <TagWidget {...tagStubNaked} />
    </>
));

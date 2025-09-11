/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ITag } from "@library/features/tags/TagsReducer";
import { TagPreset } from "@library/metas/Tags.variables";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { BorderType } from "@library/styles/styleHelpersBorders";
import TagWidget from "@vanilla/addon-vanilla/tag/TagWidget";
import React from "react";

export default {
    title: "Widgets/Tag",
    parameters: {},
};

const stubTags: ITag[] = [
    {
        tagID: 1,
        name: "Active",
        urlcode: "Active",
        countDiscussions: 2,
        urlCode: "Active",
    },
    {
        tagID: 15,
        name: "Dislike",
        urlcode: "Dislike",
        countDiscussions: 2,
        urlCode: "Dislike",
    },
    {
        tagID: 16,
        name: "Like",
        urlcode: "Like",
        countDiscussions: 1,
        urlCode: "Like",
    },
    {
        tagID: 22,
        name: "artichoke",
        urlcode: "artichoke",
        countDiscussions: 1,
        urlCode: "artichoke",
    },
    {
        tagID: 25,
        name: "ricebean",
        urlcode: "ricebean",
        countDiscussions: 1,
        urlCode: "ricebean",
    },
    {
        tagID: 31,
        name: "question",
        urlcode: "question",
        countDiscussions: 1,
        urlCode: "question",
    },
    {
        tagID: 32,
        name: "really long long long long long long long long tag",
        urlcode: "really long long long long long long long long tag",
        countDiscussions: 1,
        urlCode: "really long long long long long long long long tag",
    },
    {
        tagID: 2,
        name: "Already Offered",
        urlcode: "Already Offered",
        countDiscussions: 0,
        urlCode: "Already Offered",
    },
    {
        tagID: 3,
        name: "Declined",
        urlcode: "Declined",
        countDiscussions: 0,
        urlCode: "Declined",
    },
    {
        tagID: 4,
        name: "Completed",
        urlcode: "Completed",
        countDiscussions: 0,
        urlCode: "Completed",
    },
    {
        tagID: 5,
        name: "In Progress",
        urlcode: "In Progress",
        countDiscussions: 0,
        urlCode: "In Progress",
    },
    {
        tagID: 6,
        name: "In Review",
        urlcode: "In Review",
        countDiscussions: 0,
        urlCode: "In Review",
    },
    {
        tagID: 7,
        name: "Down",
        urlcode: "Down",
        countDiscussions: 0,
        urlCode: "Down",
    },
    {
        tagID: 8,
        name: "Spam",
        urlcode: "Spam",
        countDiscussions: 0,
        urlCode: "Spam",
    },
    {
        tagID: 9,
        name: "Abuse",
        urlcode: "Abuse",
        countDiscussions: 0,
        urlCode: "Abuse",
    },
    {
        tagID: 10,
        name: "Promote",
        urlcode: "Promote",
        countDiscussions: 0,
        urlCode: "Promote",
    },
    {
        tagID: 11,
        name: "OffTopic",
        urlcode: "OffTopic",
        countDiscussions: 0,
        urlCode: "OffTopic",
    },
    {
        tagID: 12,
        name: "Insightful",
        urlcode: "Insightful",
        countDiscussions: 0,
        urlCode: "Insightful",
    },
    {
        tagID: 13,
        name: "Disagree",
        urlcode: "Disagree",
        countDiscussions: 0,
        urlCode: "Disagree",
    },
    {
        tagID: 14,
        name: "Agree",
        urlcode: "Agree",
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
    itemOptions: {
        tagPreset: TagPreset.PRIMARY as TagPreset,
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
        <StoryHeading>
            {
                'containerOptions: { borderType: "shadow" , headerAlignment: "center"}, itemOptions: { tagPreset: "primary" }'
            }
        </StoryHeading>
        <TagWidget {...tagStubPropsShadow} />
        <StoryHeading>
            {'containerOptions: { borderType: "none" , outerBackground: "#AEE4FE", innerBackground: "#FFFFFF"}'}
        </StoryHeading>
        <TagWidget {...tagStubPropsBorder} />
        <StoryHeading>{"naked"}</StoryHeading>
        <TagWidget {...tagStubNaked} />
    </>
));

export const ThemeVariables = storyWithConfig(
    {
        themeVars: {
            tags: {
                tagCloud: {
                    showCount: false,
                    tagPreset: "success",
                    box: {
                        background: {
                            color: "rgb(255,241,219)",
                        },
                    },
                },
            },
        },
    },
    () => (
        <>
            <StoryHeading>
                {
                    'containerOptions: { borderType: "shadow" , headerAlignment: "center"}, itemOptions: { tagPreset: "primary" }'
                }
            </StoryHeading>
            <TagWidget {...tagStubPropsShadow} />
            <StoryHeading>
                {'containerOptions: { borderType: "none" , outerBackground: "#AEE4FE", innerBackground: "#FFFFFF"}'}
            </StoryHeading>
            <TagWidget {...tagStubPropsBorder} />
            <StoryHeading>{"naked"}</StoryHeading>
            <TagWidget {...tagStubNaked} />
        </>
    ),
);

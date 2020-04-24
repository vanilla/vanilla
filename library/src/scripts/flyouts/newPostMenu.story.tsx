import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import NewPostMenu, { PostTypes, IAddPost } from "@library/flyouts/NewPostMenu";
import { NewDiscussionIcon, NewIdeaIcon, NewPollIcon } from "@library/icons/common";

export default {
    component: NewPostMenu,
    title: "NewPostMenu",
};

const items = [
    {
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 1");
        },
        icon: <NewPollIcon />,
        label: "New Poll",
    },
    {
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 2");
        },
        icon: <NewIdeaIcon />,
        label: "New Idea",
    },
    {
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 3");
        },
        icon: <NewDiscussionIcon />,
        label: "New Discussion",
    },
    {
        type: PostTypes.LINK,
        action: "http://google.ca",
        icon: <NewDiscussionIcon />,
        label: "Some Link",
    },
] as IAddPost[];

export const Basic = () => (
    <StoryContent>
        <StoryHeading depth={1}> Basic </StoryHeading>
        <NewPostMenu items={items as IAddPost[]} />
    </StoryContent>
);

export const Empty = () => (
    <StoryContent>
        <StoryHeading depth={1}> Empty </StoryHeading>
        <NewPostMenu items={[] as IAddPost[]} />
    </StoryContent>
);

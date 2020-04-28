import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import NewPostMenu, { PostTypes, IAddPost } from "@library/flyouts/NewPostMenu";
import { NewDiscussionIcon, NewIdeaIcon, NewPollIcon } from "@library/icons/common";
import NewPostItems from "@library/flyouts/NewPostItems";

export default {
    component: NewPostMenu,
    title: "NewPostMenu",
};

const items = [
    {
        id: "1",
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 1");
        },
        icon: <NewPollIcon />,
        label: "New Poll",
    },
    {
        id: "2",
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 2");
        },
        icon: <NewIdeaIcon />,
        label: "New Idea",
    },
    {
        id: "3",
        type: PostTypes.BUTTON,
        action: () => {
            alert("hello! 3");
        },
        icon: <NewDiscussionIcon />,
        label: "New Discussion",
    },
    {
        id: "4",
        type: PostTypes.LINK,
        action: "http://google.ca",
        icon: <NewDiscussionIcon />,
        label: "Some Link",
    },
] as IAddPost[];

export const Basic = () => (
    <StoryContent>
        <NewPostMenu items={items} />
    </StoryContent>
);

export const Empty = () => (
    <StoryContent>
        <NewPostMenu items={[]} />
    </StoryContent>
);

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import NewPostMenu, { PostTypes, IAddPost } from "@library/flyouts/NewPostMenu";
import { NewDiscussionIcon, NewIdeaIcon, NewPollIcon } from "@library/icons/common";
import { logDebug } from "@vanilla/utils";

export default {
    component: NewPostMenu,
    title: "Components/Post Menu",
};

const items: IAddPost[] = [
    {
        id: "1",
        type: PostTypes.BUTTON,
        action: () => {
            logDebug("Some Action");
        },
        icon: <NewPollIcon />,
        label: "New Poll",
    },
    {
        id: "2",
        type: PostTypes.BUTTON,
        action: () => {
            logDebug("Some Action");
        },
        icon: <NewIdeaIcon />,
        label: "New Idea",
    },
    {
        id: "3",
        type: PostTypes.BUTTON,
        action: () => {
            logDebug("Some Action");
        },
        icon: <NewDiscussionIcon />,
        label: "New Discussion",
    },
    {
        id: "4",
        type: PostTypes.LINK,
        action: "http://google.ca",
        icon: <NewDiscussionIcon />,
        label: "to Google",
    },
];

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

import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import ActionFlyout, { IActionButton, ActionType, IActionLink } from "@library/flyouts/ActionFlyout";
import { NewDiscussionIcon, NewIdeaIcon, NewPollIcon } from "@library/icons/common";

export default {
    component: ActionFlyout,
    title: "ActionFlyout",
};

const items: Array<IActionLink | IActionButton> = [
    {
        id: "1",
        type: ActionType.BUTTON,
        action: () => {},
        assets: {
            icon: <NewPollIcon />,
            label: "New Poll",
        },
    },
    {
        id: "2",
        type: ActionType.BUTTON,
        action: () => {},
        assets: {
            icon: <NewIdeaIcon />,
            label: "New Idea",
        },
    },
    {
        id: "3",
        type: ActionType.BUTTON,
        action: () => {},
        assets: {
            icon: <NewDiscussionIcon />,
            label: "New Discussion",
        },
    },
    {
        id: "4",
        type: ActionType.LINK,
        link: "some link",
        assets: {
            icon: <NewDiscussionIcon />,
            label: "Some Link",
        },
    },
];

export const Basic = () => (
    <StoryContent>
        <StoryHeading depth={1}> Basic </StoryHeading>
        <ActionFlyout actions={items} />
    </StoryContent>
);

export const Empty = () => (
    <StoryContent>
        <StoryHeading depth={1}> Empty </StoryHeading>
        <ActionFlyout actions={[]} />
    </StoryContent>
);

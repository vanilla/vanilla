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

export const WithItemsClosed = () => (
    <StoryContent>
        <StoryHeading depth={1}> With Items Closed </StoryHeading>
        <ActionFlyout actions={items} openStatus={false} />
    </StoryContent>
);

export const WithItemsOpen = () => (
    <StoryContent>
        <StoryHeading depth={1}> With Items Open </StoryHeading>
        <ActionFlyout actions={items} openStatus />
    </StoryContent>
);

export const NoItemsClosed = () => (
    <StoryContent>
        <StoryHeading depth={1}> No Items Closed </StoryHeading>
        <ActionFlyout actions={[]} openStatus={false} />
    </StoryContent>
);

export const NoItemsOpen = () => (
    <StoryContent>
        <StoryHeading depth={1}> No Items Open </StoryHeading>
        <ActionFlyout actions={[]} openStatus />
    </StoryContent>
);

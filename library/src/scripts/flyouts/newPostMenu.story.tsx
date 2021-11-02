import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import NewPostMenu, { PostTypes, IAddPost } from "@library/flyouts/NewPostMenu";
import { logDebug } from "@vanilla/utils";
import { Icon } from "@vanilla/icons";
import { testStoreState } from "@library/__tests__/testStoreState";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";

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
        icon: "new-poll",
        label: "New Poll",
    },
    {
        id: "2",
        type: PostTypes.BUTTON,
        action: () => {
            logDebug("Some Action");
        },
        icon: "new-idea",
        label: "New Idea",
    },
    {
        id: "3",
        type: PostTypes.BUTTON,
        action: () => {
            logDebug("Some Action");
        },
        icon: "new-discussion",
        label: "New Discussion",
    },
    {
        id: "4",
        type: PostTypes.LINK,
        action: () => {
            logDebug("Some Action");
        },
        icon: "new-question",
        label: "New Question",
    },
    {
        id: "5",
        type: PostTypes.LINK,
        action: () => {
            logDebug("Some Action");
        },
        icon: "new-event",
        label: "New Event",
    },
];

const stateWithVarsApplied = testStoreState({
    theme: {
        assets: {
            data: {
                variables: {
                    type: "json",
                    data: {
                        newPostMenu: {
                            fab: {
                                iconsOnly: true,
                            },
                        },
                    },
                },
            },
        },
    },
});

export const OnDesktop = () => (
    <StoryContent>
        <StoryHeading depth={1}>New Post Menu on desktop (as button)</StoryHeading>
        <NewPostMenu items={items} />
    </StoryContent>
);

export const OnSmallerViews = () => (
    <StoryContent>
        <StoryHeading depth={1}>New Post Menu on smaller views (as toggle)</StoryHeading>
        <NewPostMenu items={items} />
    </StoryContent>
);

export const OnSmallerViewsWithIconsOnly = () => (
    <Provider store={getStore(stateWithVarsApplied, true)}>
        <StoryContent>
            <StoryHeading depth={1}>New Post Menu on smaller views (as toggle), only icons</StoryHeading>
            <NewPostMenu items={items} />
        </StoryContent>
    </Provider>
);

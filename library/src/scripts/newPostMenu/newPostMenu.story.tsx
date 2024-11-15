/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import NewPostMenu from "@library/newPostMenu/NewPostMenu";
import { testStoreState } from "@library/__tests__/testStoreState";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { newPostItems } from "@library/newPostMenu/NewPostMenu.fixture";

export default {
    component: NewPostMenu,
    title: "Components/Post Menu",
};

//some items are separate buttons
const itemsWithSeparateButtons = [...newPostItems].map((item) => {
    return { ...item, asOwnButton: item.id === "2" || item.id === "4" };
});

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
        <StoryHeading depth={1}>
            New Post Menu on desktop (all post types in dropdown, no separate buttons)
        </StoryHeading>
        <NewPostMenu items={newPostItems} forceDesktopOnly />
    </StoryContent>
);

export const OnSmallerViews = () => (
    <StoryContent>
        <StoryHeading depth={1}>New Post Menu on smaller views (as toggle)</StoryHeading>
        <NewPostMenu items={newPostItems} />
    </StoryContent>
);

export const OnSmallerViewsWithIconsOnly = () => (
    <Provider store={getStore(stateWithVarsApplied, true)}>
        <StoryContent>
            <StoryHeading depth={1}>New Post Menu on smaller views (as toggle), only icons</StoryHeading>
            <NewPostMenu items={newPostItems} />
        </StoryContent>
    </Provider>
);

export const WithSomeSeparateButtons = () => (
    <StoryContent>
        <StoryHeading depth={1}>New Post Menu as dropdown and some post types as separate buttons</StoryHeading>
        <NewPostMenu items={itemsWithSeparateButtons} forceDesktopOnly />
    </StoryContent>
);

/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import AddonListComponent from "@library/addons/AddonList";
import ADDON_STORY_IMAGE from "./addonStoryImage.png";
import Addon, { IAddon } from "@library/addons/Addon";

const fakeAddons: Array<Omit<IAddon, "onEnabledChange">> = [
    {
        imageUrl: ADDON_STORY_IMAGE,
        name: "User cards",
        enabled: true,
        description:
            "Usercards allow you to get a quick in-line snapshot of a user’s information. When viewing posts and leaderboards, click on the username to see a card showcasing the users basic profile info without having to navigate away from the page. Enable this feature to add usercards to your custom theme.",
        notes: "N.B. This new Search Page first needs to be configured to match your custom theme. This can be done using our new theme editor. Find out more.",
    },
    {
        imageUrl: ADDON_STORY_IMAGE,
        name: "New Search Page",
        enabled: false,
        description:
            "Vanilla’s new search service is finally here. Enable our new search page UI to gain access to the newest search features such as Member Search, search sorting and term highlighting.",
        notes: "N.B. This new Search Page first needs to be configured to match your custom theme. This can be done using our new theme editor. Find out more.",
    },
];

export const AddonList = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        return (
            <AddonListComponent>
                {fakeAddons.map((data, i) => (
                    <Addon key={i} {...data} onEnabledChange={() => {}} />
                ))}
            </AddonListComponent>
        );
    },
);

export default {
    title: "Components/Addons",
    includeStories: ["AddonList"],
};

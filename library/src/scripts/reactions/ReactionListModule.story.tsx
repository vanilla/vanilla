/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { IReaction } from "@library/reactions/Reaction";
import { ReactionListModule as ReactionListModuleView } from "@library/reactions/ReactionListModule";
import { dummyReactionsData } from "@library/reactions/dummyReactions";

const fakeReactions: IReaction[] = Object.entries(dummyReactionsData).map((reactionWithKey) => reactionWithKey[1]);

export default {
    title: "Components/ProfileReactionList",
    parameters: {},
};

export const ReactionsListWithNames = storyWithConfig(
    {
        themeVars: {
            reactions: {
                name: {
                    display: true,
                },
            },
        },
    },
    () => (
        <StoryContent>
            <ReactionListModuleView homeWidget title="Reactions" apiData={fakeReactions} apiParams={{ userID: 1 }} />
        </StoryContent>
    ),
);

export const ReactionsList = storyWithConfig({}, () => (
    <StoryContent>
        <ReactionListModuleView homeWidget title="Reactions" apiData={fakeReactions} apiParams={{ userID: 1 }} />
    </StoryContent>
));

export const NoReactions = storyWithConfig({}, () => (
    <StoryContent>
        <ReactionListModuleView homeWidget title="Reactions" apiData={[]} apiParams={{ userID: 1 }} />
    </StoryContent>
));

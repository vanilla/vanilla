/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import VoteCounterComponent from "@library/voteCounter/VoteCounter";

export const VoteCounter = storyWithConfig(
    {
        themeVars: {},
    },
    () => {
        const [upvoted, setUpvoted] = useState<boolean>(false);
        const [downvoted, setDownvoted] = useState<boolean>(false);
        const [initialScore] = useState(25);
        const score = initialScore + (downvoted ? -1 : upvoted ? 1 : 0);

        async function handleToggleUpvoted(): Promise<void> {
            return new Promise((resolve, reject) => {
                setTimeout(() => {
                    setDownvoted(false);
                    setUpvoted(!upvoted);
                    resolve();
                }, 100);
            });
        }

        async function handleToggleDownvoted(): Promise<void> {
            return new Promise((resolve, reject) => {
                setTimeout(() => {
                    setUpvoted(false);
                    setDownvoted(!downvoted);
                    resolve();
                }, 100);
            });
        }

        return (
            <StoryContent>
                <VoteCounterComponent upvoted={upvoted} onToggleUpvoted={handleToggleUpvoted} score={score} />
                <br />
                <VoteCounterComponent
                    upvoted={upvoted}
                    onToggleUpvoted={handleToggleUpvoted}
                    downvoted={downvoted}
                    onToggleDownvoted={handleToggleDownvoted}
                    score={score}
                />
            </StoryContent>
        );
    },
);

export default {
    title: "Components",
    parameters: {},
};

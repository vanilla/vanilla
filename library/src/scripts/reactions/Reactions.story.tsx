/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryContent } from "@library/storybook/StoryContent";
import { Reactions as ReactionsComponent } from "@library/reactions/Reactions";
import { STORY_REACTIONS } from "@library/storybook/storyData";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

export default {
    title: "Components/Reactions",
};

const allReactions = [
    ...STORY_REACTIONS,
    {
        tagID: 10,
        urlcode: "OffTopic",
        name: "Off Topic",
        class: "Negative",
        hasReacted: false,
        reactionValue: -1,
        count: 0,
    },
    {
        tagID: 11,
        urlcode: "Insightful",
        name: "Insightful",
        class: "Positive",
        hasReacted: false,
        reactionValue: 1,
        count: 3,
    },
    {
        tagID: 12,
        urlcode: "Dislike",
        name: "Dislike",
        class: "Negative",
        hasReacted: false,
        reactionValue: -1,
        count: 0,
    },
    {
        tagID: 13,
        urlcode: "Down",
        name: "Vote Down",
        class: "Negative",
        hasReacted: false,
        reactionValue: -1,
        count: 0,
    },
    {
        tagID: 14,
        urlcode: "Up",
        name: "Vote Up",
        class: "Positive",
        hasReacted: false,
        reactionValue: 1,
        count: 3,
    },
    {
        tagID: 15,
        urlcode: "Awesome",
        name: "Awesome",
        class: "Positive",
        hasReacted: false,
        reactionValue: 1,
        count: 10,
    },
];

export const Reactions = storyWithConfig({}, () => {
    const queryClient = new QueryClient({
        defaultOptions: {
            queries: {
                enabled: false,
                retry: false,
                staleTime: Infinity,
            },
        },
    });

    return (
        <StoryContent>
            <PermissionsFixtures.AllPermissions>
                <QueryClientProvider client={queryClient}>
                    <ReactionsComponent reactions={allReactions} recordType="discussion" recordID={1} />
                </QueryClientProvider>
            </PermissionsFixtures.AllPermissions>
        </StoryContent>
    );
});

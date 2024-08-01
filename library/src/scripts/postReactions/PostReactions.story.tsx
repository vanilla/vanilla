/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { IReaction } from "@dashboard/@types/api/reaction";
import { IUserFragment } from "@library/@types/api/users";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { PostReactions } from "@library/postReactions/PostReactions";
import { IPostReaction } from "@library/postReactions/PostReactions.types";
import { PostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { PostReactionsLog, PostReactionsLogAsModal } from "@library/postReactions/PostReactionsLog";
import { StoryContent } from "@library/storybook/StoryContent";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { STORY_REACTIONS, STORY_USER, getMockReactionLog } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactNode, useState } from "react";

export default {
    title: "Components/PostReactions",
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            enabled: false,
            retry: false,
            staleTime: Infinity,
        },
    },
});

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

function StoryWrapper(props: { children: ReactNode }) {
    const [reactionLog, setReactionLog] = useState<IPostReaction[]>(getMockReactionLog());
    const [counts, setCounts] = useState<Array<Partial<IReaction>>>();

    const getUsers = (tagID: number): IUserFragment[] => {
        return reactionLog.filter((reaction) => reaction.tagID === tagID).map(({ user }) => user);
    };

    const toggleReaction = (props: { reaction?: IReaction; user?: IUserFragment }) => {
        const { reaction, user } = props;
        if (!reaction) {
            return;
        }

        if (user) {
            const tmpLog = reactionLog.filter((item) => {
                return !(reaction.tagID === item.tagID && user.userID === item.userID);
            });
            setReactionLog(tmpLog);
        } else {
            const tmpCounts = (counts ?? allReactions).map(({ tagID, count }) => {
                let newCount = count;
                let hasReacted = false;
                if (reaction.tagID === tagID) {
                    if (reaction.hasReacted) {
                        newCount = count - 1;
                    } else {
                        newCount = count + 1;
                        hasReacted = true;
                    }
                }

                return { tagID, count: newCount, hasReacted };
            });

            setCounts(tmpCounts);
            setReactionLog(getMockReactionLog(allReactions, tmpCounts));
        }
    };

    return (
        <StoryContent>
            <QueryClientProvider client={queryClient}>
                <PostReactionsContext.Provider value={{ reactionLog, getUsers, counts, toggleReaction }}>
                    {props.children}
                </PostReactionsContext.Provider>
            </QueryClientProvider>
        </StoryContent>
    );
}

export const ReactionsAsMember = storyWithConfig({}, () => {
    return (
        <StoryWrapper>
            <PermissionsFixtures.SpecificPermissions permissions={["reactions.positive.add", "reactions.negative.add"]}>
                <PostReactions reactions={allReactions} />
            </PermissionsFixtures.SpecificPermissions>
        </StoryWrapper>
    );
});

export const ReactionsWithAllPermissions = storyWithConfig({}, () => {
    return (
        <StoryWrapper>
            <PermissionsFixtures.AllPermissions>
                <PostReactions reactions={allReactions} />
            </PermissionsFixtures.AllPermissions>
        </StoryWrapper>
    );
});

export const ReactionsLog = storyWithConfig({}, () => {
    return (
        <StoryWrapper>
            <PermissionsFixtures.AllPermissions>
                <StoryHeading>Reaction Log as Modal</StoryHeading>
                <StoryParagraph>Click button to open the log</StoryParagraph>
                <PostReactionsLogAsModal />
                <StoryHeading>Reaction Log Component</StoryHeading>
                <PostReactionsLog />
            </PermissionsFixtures.AllPermissions>
        </StoryWrapper>
    );
});

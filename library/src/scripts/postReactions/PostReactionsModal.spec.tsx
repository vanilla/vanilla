/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { render, waitFor, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import PostReactionsModalImpl from "@library/postReactions/PostReactionsModal.loadable";
import { getMockReactionLog } from "@library/storybook/storyData";
import { IUserFragment } from "@library/@types/api/users";
import { PostReactionsContext } from "@library/postReactions/PostReactionsContext";

const queryClient = new QueryClient();
const reactionLog = getMockReactionLog();

function MockQueryClient(props: { children: ReactNode }) {
    const getUsers = (tagID: number): IUserFragment[] => {
        return reactionLog.filter((reaction) => reaction.tagID === tagID).map(({ user }) => user);
    };
    return (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                <PostReactionsContext.Provider
                    value={{
                        reactionLog,
                        getUsers,
                        toggleReaction: () => null,
                        recordID: 1,
                        recordType: "discussion",
                    }}
                >
                    {props.children}
                </PostReactionsContext.Provider>
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );
}

describe("PostReactionsModal", () => {
    it("Renders Modal", async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                    <PostReactionsContext.Provider
                        value={{
                            reactionLog: [],
                            getUsers: () => [],
                            toggleReaction: () => null,
                            recordID: 1,
                            recordType: "discussion",
                        }}
                    >
                        <PostReactionsModalImpl visibility={true} onVisibilityChange={() => null} />
                    </PostReactionsContext.Provider>
                </CurrentUserContextProvider>
            </QueryClientProvider>,
        );
        await waitFor(() => expect(screen.getByText("Reactions")).toBeInTheDocument());
        await waitFor(() => expect(screen.queryByText("No Reactions Yet")).toBeInTheDocument());
    });
    it("Renders Log", async () => {
        render(
            <MockQueryClient>
                <PostReactionsModalImpl visibility={true} onVisibilityChange={() => null} />
            </MockQueryClient>,
        );
        await waitFor(() => expect(screen.getByText("Reactions")).toBeInTheDocument());
        await waitFor(() => expect(screen.getAllByText(/Test User/)[0]).toBeInTheDocument());
    });
});

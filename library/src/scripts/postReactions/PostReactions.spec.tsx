/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IUserFragment } from "@library/@types/api/users";
import { mockAPI } from "@library/__tests__/utility";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { PostReactions } from "@library/postReactions/PostReactions";
import { IPostRecord, PostReactionIconType } from "@library/postReactions/PostReactions.types";
import { PostReactionsContext } from "@library/postReactions/PostReactionsContext";
import { STORY_REACTIONS, getMockReactionLog } from "@library/storybook/storyData";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { act, render, screen } from "@testing-library/react";
import { renderHook } from "@testing-library/react-hooks";
import { ReactNode } from "react";
import { PostReactionTooltip } from "./PostReactionTooltip";
import { useReactionLog } from "./PostReactions.hooks";
import { PostReactionsLog } from "./PostReactionsLog";
import { vitest } from "vitest";
import MockAdapter from "axios-mock-adapter/types";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";

let mockAdapter: MockAdapter;
const REACTIONS_URL = "/discussions/1/reactions";

const MOCK_RECORD: IPostRecord = {
    recordType: "discussion",
    recordID: 1,
};

const queryClient = new QueryClient();
// Wrapper with QueryClientProvider for testing hooks
function queryClientWrapper() {
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                {children}
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );

    return Wrapper;
}

function MockQueryClient(props: { children: ReactNode }) {
    return (
        <QueryClientProvider client={queryClient}>
            <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                {props.children}
            </CurrentUserContextProvider>
        </QueryClientProvider>
    );
}

const toggleReaction = vitest.fn();
const reactionLog = getMockReactionLog();

function MockProvider(props: { children: ReactNode }) {
    const getUsers = (tagID: number): IUserFragment[] => {
        return reactionLog.filter((reaction) => reaction.tagID === tagID).map(({ user }) => user);
    };

    return (
        <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
            <PostReactionsContext.Provider
                value={{
                    reactionLog,
                    getUsers,
                    toggleReaction,
                    recordID: 1,
                    recordType: "discussion",
                }}
            >
                {props.children}
            </PostReactionsContext.Provider>
        </CurrentUserContextProvider>
    );
}

describe("Post Reactions Component", () => {
    it("Renders reaction buttons as a member with counts. Promote should not be available", () => {
        render(
            <MockQueryClient>
                <MockProvider>
                    <PermissionsFixtures.SpecificPermissions
                        permissions={["reactions.positive.add", "reactions.negative.add"]}
                    >
                        <PostReactions reactions={STORY_REACTIONS} />
                    </PermissionsFixtures.SpecificPermissions>
                </MockProvider>
            </MockQueryClient>,
        );

        STORY_REACTIONS.forEach((reaction) => {
            if (reaction.urlcode === "Promote") {
                expect(screen.queryByLabelText(reaction.name)).toBeNull();
            } else {
                const button = screen.getByLabelText(reaction.name);
                expect(button).toBeInTheDocument();
                if (reaction.count > 0) {
                    expect(button).toHaveTextContent(reaction.count.toString());
                } else {
                    expect(button).toHaveTextContent("");
                }
            }
        });
    });

    it("Renders all reaction buttons, including Promote, with proper permissions.", () => {
        render(
            <MockQueryClient>
                <MockProvider>
                    <PermissionsFixtures.AllPermissions>
                        <PostReactions reactions={STORY_REACTIONS} />
                    </PermissionsFixtures.AllPermissions>
                </MockProvider>
            </MockQueryClient>,
        );

        STORY_REACTIONS.forEach((reaction) => {
            const button = screen.getByLabelText(reaction.name);
            expect(button).toBeInTheDocument();
            if (reaction.count > 0) {
                expect(button).toHaveTextContent(reaction.count.toString());
            } else {
                expect(button).toHaveTextContent("");
            }
        });
    });

    it("Active state applied to current user's reacted choice.", () => {
        render(
            <MockQueryClient>
                <MockProvider>
                    <PermissionsFixtures.AllPermissions>
                        <PostReactions reactions={STORY_REACTIONS} />
                    </PermissionsFixtures.AllPermissions>
                </MockProvider>
            </MockQueryClient>,
        );

        STORY_REACTIONS.forEach((reaction) => {
            const button = screen.getByLabelText(reaction.name);
            expect(button).toBeInTheDocument();
            if (reaction.hasReacted) {
                expect(button).toHaveStyle("background-color: rgb(3, 105, 158)");
            } else {
                expect(button).toHaveStyle("background-color: rgb(255, 255, 255)");
            }
        });
    });

    it("Tooltip displays reaction name and no users for user's without reactions.view permission", () => {
        render(
            <MockProvider>
                <PostReactionTooltip iconType={PostReactionIconType.DISAGREE} name="Disagree" tagID={2} />
            </MockProvider>,
        );

        expect(screen.getByText(/Disagree/)).toBeInTheDocument();
        expect(screen.queryByRole("list")).toBeNull();
    });

    it("Tooltip displays reaction name and reacted user list for user's with reactions.view permission", () => {
        render(
            <MockProvider>
                <PermissionsFixtures.SpecificPermissions permissions={["reactions.view"]}>
                    <PostReactionTooltip iconType={PostReactionIconType.DISAGREE} name="Disagree" tagID={2} />
                </PermissionsFixtures.SpecificPermissions>
            </MockProvider>,
        );

        expect(screen.getByText(/Disagree/)).toBeInTheDocument();
        expect(screen.getByRole("list")).toBeInTheDocument();
        expect(screen.getByText(/Test User 1/)).toBeInTheDocument();
        expect(screen.getByText(/Test User 2/)).toBeInTheDocument();
        expect(screen.getByText(/Test User 3/)).toBeInTheDocument();
    });

    it("Reaction Log displays list of all users that have reacted with their corresponding reaction.", async () => {
        render(
            <MockProvider>
                <PostReactionsLog />
            </MockProvider>,
        );

        const list = await screen.findAllByRole("listitem");
        expect(list.length).toEqual(reactionLog.length);

        list.forEach((item, idx) => {
            const reaction = reactionLog[idx];
            expect(item).toHaveTextContent(reaction.reactionType.name as string);
            expect(item).toHaveTextContent(reaction.user.name);
        });
    });
});

describe("Post Reactions Hooks", () => {
    it("Fetches the reaction log when requested", async () => {
        mockAdapter = mockAPI();
        mockAdapter.onGet(REACTIONS_URL).replyOnce(200, reactionLog);
        const { result, waitFor } = renderHook(() => useReactionLog(MOCK_RECORD), { wrapper: queryClientWrapper() });
        act(() => {
            void result.current.refetch();
        });
        await waitFor(() => result.current.isSuccess);
        expect(result.current.data).toStrictEqual(reactionLog);
    });
});

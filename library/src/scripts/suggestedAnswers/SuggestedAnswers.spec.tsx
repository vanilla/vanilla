/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { SuggestedAnswers } from "./SuggestedAnswers";
import { ReactNode } from "react";
import { QueryClient, QueryClientProvider, useQueryClient } from "@tanstack/react-query";
import { setMeta } from "@library/utility/appUtils";
import { LoadStatus } from "@library/@types/api/core";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { format } from "path";
import { SuggestedAnswersOptionsMenu } from "./SuggestedAnswersOptionsMenu";

const queryClient = new QueryClient();

function MockWrapper(props: { children: ReactNode }) {
    setMeta("aiAssistant", {
        userID: 1,
        name: "AI Assistant",
    });

    return (
        <TestReduxProvider
            state={{
                users: {
                    usersByID: {
                        2: {
                            status: LoadStatus.SUCCESS,
                            data: UserFixture.createMockUser({ userID: 2 }),
                        },
                    },
                },
            }}
        >
            <QueryClientProvider client={queryClient}>
                <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                    {props.children}
                </CurrentUserContextProvider>
            </QueryClientProvider>
        </TestReduxProvider>
    );
}

const suggestions = [
    {
        format: "Vanilla",
        type: "discussion",
        id: 1,
        url: "http://example.com/suggested-discussion/1",
        title: "Suggested Discussion",
        summary: "Summary for suggested answer from a discussion.",
        hidden: false,
    },
    {
        format: "Vanilla",
        type: "article",
        id: 2,
        url: "http://example.com/suggested-article/2",
        title: "Suggested Article",
        summary: "Summary for suggested answer from an article.",
        hidden: true,
    },
    {
        format: "Zendesk",
        type: "article",
        id: 2,
        url: "http://example.com/suggested-zendesk/2",
        title: "Suggested Zendesk Article",
        summary: "Summary for suggested answer from a Zendesk Article.",
        hidden: false,
        commentID: 1,
    },
];

describe("AI Suggested Answers", () => {
    describe("SuggestedAnswers", () => {
        it("Displays the AI Assistant name and button to hide the suggestions and only the first suggestion", async () => {
            render(
                <MockWrapper>
                    <SuggestedAnswers suggestions={suggestions} showSuggestions={true} />
                </MockWrapper>,
            );

            const aiAssistant = await screen.getAllByText(/AI Assistant/);
            expect(aiAssistant).toHaveLength(3);

            const hideButton = screen.getByRole("button", { name: "Hide Suggestions" });
            expect(hideButton).toBeDefined();

            const suggestionItems = await screen.getAllByRole("listitem");
            expect(suggestionItems).toHaveLength(1);
        });

        it("Displays a box to give option to regenerate suggestions. Show/Hide button should be gone.", async () => {
            render(
                <MockWrapper>
                    <SuggestedAnswers suggestions={suggestions} showSuggestions={false} postHasBeenEdited={true} />
                </MockWrapper>,
            );

            const regenerateButton = screen.getByRole("button", { name: "Regenerate Suggestions" });
            expect(regenerateButton).toBeInTheDocument();

            const hideButton = await screen.queryByRole("button", { name: "Hide Suggestions" });
            const showButton = await screen.queryByRole("button", { name: "Show Suggestions" });

            expect(hideButton).toBeNull();
            expect(showButton).toBeNull();
        });
    });

    describe("SuggestedAnswersOptionsMenu", () => {
        it("Displays all menu options", async () => {
            render(
                <MockWrapper>
                    <SuggestedAnswersOptionsMenu suggestions={suggestions} regenerateSuggestions={() => {}} />
                </MockWrapper>,
            );

            const menuButton = screen.getByRole("button", { name: "Suggested Answers Options" });
            expect(menuButton).toBeInTheDocument();

            fireEvent.click(menuButton);

            const whyButton = await screen.getByRole("button", { name: "Why am I seeing this?" });
            expect(whyButton).toBeInTheDocument();

            const turnOffButton = await screen.getByRole("button", { name: "Turn off AI Suggested Answers" });
            expect(turnOffButton).toBeInTheDocument();

            const dismissedButton = await screen.getByRole("button", { name: "Show Dismissed Suggestions" });
            expect(dismissedButton).toBeInTheDocument();

            const acceptAllButton = await screen.getByRole("button", {
                name: "Mark All Suggested Answers as Accepted",
            });
            expect(acceptAllButton).toBeInTheDocument();

            const regenerateButton = await screen.getByRole("button", { name: "Regenerate AI Suggestions" });
            expect(regenerateButton).toBeInTheDocument();
        });

        it("Displays only the why am I seeing this and turn off buttons", async () => {
            render(
                <MockWrapper>
                    <SuggestedAnswersOptionsMenu
                        suggestions={suggestions}
                        showActions={false}
                        regenerateSuggestions={() => {}}
                    />
                </MockWrapper>,
            );

            const menuButton = screen.getByRole("button", { name: "Suggested Answers Options" });
            expect(menuButton).toBeInTheDocument();

            fireEvent.click(menuButton);

            const whyButton = await screen.getByRole("button", { name: "Why am I seeing this?" });
            expect(whyButton).toBeInTheDocument();

            const turnOffButton = await screen.getByRole("button", { name: "Turn off AI Suggested Answers" });
            expect(turnOffButton).toBeInTheDocument();

            const dismissedButton = await screen.queryByRole("button", { name: "Show Dismissed Suggestions" });
            expect(dismissedButton).toBeNull();

            const acceptAllButton = await screen.queryByRole("button", {
                name: "Mark All Suggested Answers as Accepted",
            });
            expect(acceptAllButton).toBeNull();

            const regenerateButton = await screen.queryByRole("button", { name: "Regenerate AI Suggestions" });
            expect(regenerateButton).toBeNull();
        });

        it("Regenerate button calls the passed method", async () => {
            const regenerate = vitest.fn();
            render(
                <MockWrapper>
                    <SuggestedAnswersOptionsMenu suggestions={suggestions} regenerateSuggestions={regenerate} />
                </MockWrapper>,
            );

            const menuButton = screen.getByRole("button", { name: "Suggested Answers Options" });
            expect(menuButton).toBeInTheDocument();

            fireEvent.click(menuButton);

            const regenerateButton = await screen.getByRole("button", { name: "Regenerate AI Suggestions" });
            expect(regenerateButton).toBeInTheDocument();

            fireEvent.click(regenerateButton);

            await waitFor(() => {
                expect(regenerate).toHaveBeenCalled();
            });
        });
    });
});

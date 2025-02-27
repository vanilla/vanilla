/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { render, screen } from "@testing-library/react";
import { LoadStatus } from "@library/@types/api/core";
import MoveDiscussionForm from "./MoveDiscussionForm";
import { configureStore, createReducer } from "@reduxjs/toolkit";
import { Provider } from "react-redux";
import { vitest } from "vitest";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const renderInProvider = () => {
    const discussion = { ...DiscussionFixture.fakeDiscussions[0], url: "/mockPath", name: "Mock Discussion" };

    const testReducer = createReducer(
        {
            discussions: {
                discussionsByID: {
                    10: {
                        status: LoadStatus.SUCCESS,
                        data: discussion,
                    },
                },
                bookmarkStatusesByID: {
                    10: LoadStatus.SUCCESS,
                },
            },
            forum: {
                categories: {
                    suggestionsByQuery: {
                        "''": { status: LoadStatus.SUCCESS },
                    },
                },
            },
        },
        (builder) => {},
    );

    const store = configureStore({ reducer: testReducer });
    render(
        <Provider store={store}>
            <QueryClientProvider client={queryClient}>
                <MoveDiscussionForm discussion={discussion} onCancel={vitest.fn} />
            </QueryClientProvider>
        </Provider>,
    );
};

describe("MoveDiscussionForm", () => {
    it("Modal is rendered and we have the category input as well as redirect link checkbox", () => {
        renderInProvider();
        expect(screen.queryByText("Move Discussion")).toBeInTheDocument();
        expect(screen.queryByLabelText("Category")).toBeInTheDocument();
        expect(
            screen.queryByRole("checkbox", {
                name: "Leave a redirect link",
            }),
        ).toBeInTheDocument();
    });
});

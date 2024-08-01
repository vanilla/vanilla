/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen } from "@testing-library/react";
import { LoadStatus } from "@library/@types/api/core";
import MoveDiscussionForm from "./MoveDiscussionForm";
import { configureStore, createReducer } from "@reduxjs/toolkit";
import { Provider } from "react-redux";
import { vitest } from "vitest";
import { DiscussionFixture } from "@vanilla/addon-vanilla/thread/__fixtures__/Discussion.Fixture";

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
            <MoveDiscussionForm discussion={discussion} onCancel={vitest.fn} />
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

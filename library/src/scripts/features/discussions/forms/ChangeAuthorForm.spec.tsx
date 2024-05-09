/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import ChangeAuthorForm from "@library/features/discussions/forms/ChangeAuthorForm.loadable";
import { STORY_DISCUSSION, STORY_USER } from "@library/storybook/storyData";
import { mockAPI } from "@library/__tests__/utility";
import { act } from "react-dom/test-utils";
import { IUser } from "@library/@types/api/users";
import { ToastContext } from "@library/features/toaster/ToastContext";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";

const mockUser: IUser = {
    ...STORY_USER,
    name: "Test user",
    userID: 368,
};

const baseDiscussion: IDiscussion = {
    ...STORY_DISCUSSION,
    discussionID: 100,
    insertUser: {
        ...mockUser,
    },
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

const mockApi = mockAPI();

const mockToastProps = {
    toasts: [],
    addToast: jest.fn(),
    updateToast: jest.fn(),
    removeToast: jest.fn(),
};

const renderInProvider = async () => {
    await act(async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <TestReduxProvider>
                    <ToastContext.Provider value={mockToastProps}>
                        <ChangeAuthorForm discussion={baseDiscussion} onCancel={() => null} />
                    </ToastContext.Provider>
                </TestReduxProvider>
            </QueryClientProvider>,
        );
    });
};

describe("ChangeAuthorForm", () => {
    beforeEach(() => {
        mockApi.onGet(/users\/by-name/).reply(200, [
            {
                ...mockUser,
            },
            {
                ...mockUser,
                name: "Test user 2",
                userID: 369,
            },
        ]);
    });
    it("Renders selected author", async () => {
        await renderInProvider();
        expect(screen.getByText(mockUser.name)).toBeInTheDocument();
    });
    it("Updates to new author", async () => {
        mockApi.onGet(/discussions.*/).reply(200, [
            {
                ...baseDiscussion,
                insertUser: {
                    ...mockUser,
                    name: "Test user 2",
                    userID: 369,
                },
            },
        ]);
        mockApi.onPatch(/discussions\/.*/).reply(200, {});
        await renderInProvider();
        const textInput = screen.getByRole("textbox", { name: "Author" });
        await act(async () => {
            await fireEvent.change(textInput, { target: { value: "Test user 2" } });
        });
        await waitFor(() => screen.getByText("Test user 2"));
        await act(async () => {
            fireEvent.submit(screen.getByRole("button", { name: "Save" }));
        });
        expect(mockApi.history.patch.length).toBe(1);
    });
    it("Displays an error when there is a request error", async () => {
        mockApi.onPatch(/discussions\/.*/).networkError();
        await renderInProvider();
        await act(async () => {
            fireEvent.submit(screen.getByRole("button", { name: "Save" }));
        });
        expect(mockToastProps.addToast.mock.calls[0][0].body.props.children).toContain(
            "There was an error changing the author",
        );
    });
});

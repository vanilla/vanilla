/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ComponentProps } from "react";
import { fireEvent, render, waitFor, screen } from "@testing-library/react";
import TagDiscussionForm from "@library/features/discussions/forms/TagDiscussionForm";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import userEvent from "@testing-library/user-event";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { TagFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Tag.Fixture";
import { DiscussionFixture } from "@vanilla/addon-vanilla/posts/__fixtures__/Discussion.Fixture";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: false,
        },
    },
});

function WrappedTagDiscussionForm(props: Partial<ComponentProps<typeof TagDiscussionForm>>) {
    return (
        <QueryClientProvider client={queryClient}>
            <PermissionsFixtures.AllPermissions>
                <TagDiscussionForm onCancel={vi.fn()} discussion={DiscussionFixture.mockDiscussion} {...props} />
            </PermissionsFixtures.AllPermissions>
        </QueryClientProvider>
    );
}

describe("TagDiscussionForm", () => {
    let api: MockAdapter;

    beforeEach(() => {
        api = mockAPI();
        api.onGet(/tags.*/).reply(200, TagFixture.getMockTags(5));
    });

    it("Renders popular tags", async () => {
        render(<WrappedTagDiscussionForm />);
        await vi.dynamicImportSettled();
        await waitFor(() => {
            expect(screen.getByText("Popular Tags")).toBeInTheDocument();
            expect(screen.getAllByText("Mock Tag 0")[0]).toBeInTheDocument();
            expect(screen.getAllByText("Mock Tag 1")[0]).toBeInTheDocument();
            expect(screen.getAllByText("Mock Tag 2")[0]).toBeInTheDocument();
        });
    });

    it("Adds popular tags", async () => {
        api.onPut(`/discussions/${DiscussionFixture.mockDiscussion["discussionID"]}/tags/`).reply(
            200,
            DiscussionFixture.mockDiscussion,
        );

        const mockSuccess = vi.fn();

        render(<WrappedTagDiscussionForm onSuccess={mockSuccess} />);
        await vi.dynamicImportSettled();
        await waitFor(() => {
            expect(screen.getByText("Popular Tags")).toBeInTheDocument();
            expect(screen.getAllByText("Mock Tag 0")[0]).toBeInTheDocument();
        });
        fireEvent.click(screen.getAllByText("Mock Tag 0")[0]);
        fireEvent.click(screen.getByText("Save"));
        await waitFor(() => {
            expect(api.history.put.length).toBe(1);
        });
    });

    it("Creates new tags", async () => {
        api.onPost(`/tags/`).reply(200, { ...TagFixture.mockTag, name: "New Tag" });

        render(<WrappedTagDiscussionForm />);
        await vi.dynamicImportSettled();
        await waitFor(() => {
            expect(screen.getByText("Popular Tags")).toBeInTheDocument();
        });

        const input = screen.getByRole("textbox");
        input.focus();
        await userEvent.keyboard("New Tag");

        const newTag = screen.getByRole("menuitem", { name: "New Tag" });
        expect(newTag).toHaveClass("highlighted");

        await userEvent.keyboard("{Enter}");

        fireEvent.click(screen.getByText("Save"));

        await waitFor(() => {
            expect(api.history.post.length).toBe(1);
        });
    });
});

/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, waitFor, screen, within } from "@testing-library/react";
import { TagDropdown } from "@library/forms/nestedSelect/presets/TagDropdown";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import MockAdapter from "axios-mock-adapter";
import { mockAPI } from "@library/__tests__/utility";
import userEvent from "@testing-library/user-event";
import { ApiV2Context } from "@library/apiv2";

function renderComponent() {
    const queryClient = new QueryClient();
    render(
        <QueryClientProvider client={queryClient}>
            <ApiV2Context>
                <TagDropdown onChange={() => null} />
            </ApiV2Context>
        </QueryClientProvider>,
    );
}

const MOCK_TAG_ALL = [
    {
        tagID: 1,
        id: 1,
        name: "crochet",
        type: "User",
        url: "http://test.com/discussions/tagged/crochet",
        urlcode: "crochet",
        countDiscussions: 1,
        urlCode: "crochet",
    },
    {
        tagID: 2,
        id: 2,
        name: "beginner",
        type: "User",
        url: "http://test.com/discussions/tagged/beginner",
        urlcode: "beginner",
        countDiscussions: 0,
        urlCode: "beginner",
    },
    {
        tagID: 3,
        id: 3,
        name: "intermediate",
        type: "User",
        url: "http://test.com/discussions/tagged/beginner",
        urlcode: "beginner",
        countDiscussions: 0,
        urlCode: "beginner",
    },
    {
        tagID: 4,
        id: 4,
        name: "advanced",
        type: "User",
        url: "http://test.com/discussions/tagged/beginner",
        urlcode: "beginner",
        countDiscussions: 0,
        urlCode: "beginner",
    },
];

const MOCK_TAG_SEARCH = [
    {
        tagID: 2,
        id: 2,
        name: "beginner",
        type: "User",
        url: "http://test.com/discussions/tagged/beginner",
        urlcode: "beginner",
        countDiscussions: 0,
        urlCode: "beginner",
    },
];

describe("TagDropdown", () => {
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Get tag list", async () => {
        mockAdapter.onGet("/tags?type=User&query=").reply(200, MOCK_TAG_ALL);
        renderComponent();

        const input = screen.getByTestId("inputContainer");
        await userEvent.click(input);

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        const crochet = within(menu).getByRole("menuitem", { name: "crochet" });
        expect(crochet).toBeInTheDocument();

        const intermediate = within(menu).getByRole("menuitem", { name: "intermediate" });
        expect(intermediate).toBeInTheDocument();
    });

    it("Get filtered tag list when searching", async () => {
        mockAdapter.onGet("/tags?type=User&query=beg").reply(200, MOCK_TAG_SEARCH);
        renderComponent();

        const input = screen.getByRole("textbox");
        await userEvent.click(input);
        await userEvent.keyboard("beg");

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        const items = await within(menu).findAllByRole("menuitem");
        expect(items.length).toEqual(1);

        expect(items[0]).toHaveTextContent("beginner");
    });
});

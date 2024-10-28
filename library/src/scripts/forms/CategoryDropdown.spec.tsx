/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { fireEvent, render, waitFor, screen, within } from "@testing-library/react";
import { CategoryDropdown } from "@library/forms/CategoryDropdown";
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
                <CategoryDropdown onChange={() => null} />
            </ApiV2Context>
        </QueryClientProvider>,
    );
}

const MOCK_CATEGORY_ALL = [
    {
        categoryID: 7,
        name: "Crochet",
        parentCategoryID: null,
        displayAs: "discussions",
        sort: 12,
    },
    {
        categoryID: 20,
        name: "Amigurumi",
        parentCategoryID: 7,
        displayAs: "categories",
        sort: 13,
    },
    {
        categoryID: 26,
        name: "Help",
        parentCategoryID: 20,
        displayAs: "discussions",
        sort: 14,
    },
    {
        categoryID: 28,
        name: "Pattern",
        parentCategoryID: 26,
        displayAs: "discussions",
        sort: 15,
    },
    {
        categoryID: 27,
        name: "Assembly",
        parentCategoryID: 26,
        displayAs: "discussions",
        sort: 17,
    },
    {
        categoryID: 25,
        name: "Projects",
        parentCategoryID: 20,
        displayAs: "discussions",
        sort: 20,
    },
    {
        categoryID: 21,
        name: "Patterns",
        parentCategoryID: 20,
        displayAs: "heading",
        sort: 22,
    },
    {
        categoryID: 22,
        name: "Easy",
        parentCategoryID: 21,
        displayAs: "discussions",
        sort: 23,
    },
    {
        categoryID: 23,
        name: "Medium",
        parentCategoryID: 21,
        displayAs: "discussions",
        sort: 25,
    },
    {
        categoryID: 24,
        name: "Hard",
        parentCategoryID: 21,
        displayAs: "discussions",
        sort: 27,
    },
    {
        categoryID: 11,
        name: "Stitches",
        parentCategoryID: 7,
        displayAs: "heading",
        sort: 31,
    },
    {
        categoryID: 13,
        name: "Tunisian",
        parentCategoryID: 11,
        displayAs: "discussions",
        sort: 32,
    },
    {
        categoryID: 14,
        name: "Beginner",
        parentCategoryID: 13,
        displayAs: "discussions",
        sort: 33,
    },
    {
        categoryID: 15,
        name: "Intermediate",
        parentCategoryID: 13,
        displayAs: "discussions",
        sort: 35,
    },
    {
        categoryID: 16,
        name: "Advanced",
        parentCategoryID: 13,
        displayAs: "discussions",
        sort: 37,
    },
    {
        categoryID: 12,
        name: "Traditional",
        parentCategoryID: 11,
        displayAs: "discussions",
        sort: 40,
    },
    {
        categoryID: 17,
        name: "Beginner",
        parentCategoryID: 12,
        displayAs: "discussions",
        sort: 41,
    },
    {
        categoryID: 18,
        name: "Intermediate",
        parentCategoryID: 12,
        displayAs: "discussions",
        sort: 43,
    },
    {
        categoryID: 19,
        name: "Advanced",
        parentCategoryID: 12,
        displayAs: "discussions",
        sort: 45,
    },
];

const MOCK_CATEGORY_SEARCH = [
    {
        categoryID: 14,
        name: "Beginner",
        parentCategoryID: 13,
        displayAs: "discussions",
        breadcrumbs: [
            {
                name: "Home",
                url: "https://dev.vanilla.localhost/",
            },
            {
                name: "Crochet",
                url: "https://dev.vanilla.localhost/categories/crochet",
            },
            {
                name: "Tunisian",
                url: "https://dev.vanilla.localhost/categories/tunisian",
            },
            {
                name: "Beginner",
                url: "https://dev.vanilla.localhost/categories/tunisian-beginner",
            },
        ],
    },
    {
        categoryID: 17,
        name: "Beginner",
        parentCategoryID: 12,
        displayAs: "discussions",
        breadcrumbs: [
            {
                name: "Home",
                url: "https://dev.vanilla.localhost/",
            },
            {
                name: "Crochet",
                url: "https://dev.vanilla.localhost/categories/crochet",
            },
            {
                name: "Traditional",
                url: "https://dev.vanilla.localhost/categories/traditional",
            },
            {
                name: "Beginner",
                url: "https://dev.vanilla.localhost/categories/traditional-beginner",
            },
        ],
    },
];

describe("CategoryDropdown", () => {
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Get default category list nested", async () => {
        mockAdapter.onGet("/categories?outputFormat=flat&limit=50").reply(200, MOCK_CATEGORY_ALL);
        renderComponent();

        const input = screen.getByTestId("inputContainer");
        await userEvent.click(input);

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        const amigurumi = within(menu).getByRole("heading", { name: "Amigurumi" });
        expect(amigurumi).toBeInTheDocument();

        const tunisian = within(menu).getByRole("menuitem", { name: "Tunisian" });
        expect(tunisian).toBeInTheDocument();
    });

    it("Skips heading when using keyboard navigation", async () => {
        mockAdapter.onGet("/categories?outputFormat=flat&limit=50").reply(200, MOCK_CATEGORY_ALL);
        renderComponent();

        const input = screen.getByRole("textbox");
        await userEvent.click(input);

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        await userEvent.keyboard("{ArrowDown}");
        const help = within(menu).getByRole("menuitem", { name: "Help" });
        expect(help).toBeInTheDocument();
        expect(help).toHaveClass("highlighted");
    });

    it("Get filtered category list when searching", async () => {
        mockAdapter.onGet("/categories/search?query=beg").reply(200, MOCK_CATEGORY_SEARCH);
        renderComponent();

        const input = screen.getByRole("textbox");
        await userEvent.click(input);
        await userEvent.keyboard("beg");

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        const items = await within(menu).findAllByRole("menuitem");
        expect(items.length).toEqual(2);

        expect(items[0]).toHaveTextContent("Crochet > Tunisian Beginner");
        expect(items[1]).toHaveTextContent("Crochet > Traditional Beginner");
    });
});

/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { render, screen, fireEvent, waitFor, act, within } from "@testing-library/react";
import SearchBar from "@library/features/search/SearchBar";
import IndependentSearch from "@library/features/search/IndependentSearch";
import { MemoryRouter } from "react-router";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import SearchContext from "@library/contexts/SearchContext";
import { vitest } from "vitest";

const searchBarMockProps = {
    value: "",
    onChange: vitest.fn(),
    onSearch: vitest.fn(),
};

describe("SearchBar", () => {
    it("Displays a scope dropdown", () => {
        const searchBarScope = {
            value: {
                name: "Everywhere",
                value: "every-where",
            },
            optionsItems: [
                { name: "scope 1", value: "scope1" },
                { name: "Everywhere", value: "every-where" },
            ],
        };

        render(<SearchBar {...searchBarMockProps} scope={searchBarScope} />);

        const button = screen.getByRole("button", { name: "Everywhere" });
        expect(button).toBeInTheDocument();
        fireEvent.click(button);
        const list = screen.getByRole("list");
        expect(list).toBeInTheDocument();
        const firstOption = within(list).getByText(/scope 1/, { exact: false });
        expect(firstOption).toBeInTheDocument();
        const secondOption = within(list).getByText(/Everywhere/, { exact: false });
        expect(secondOption).toBeInTheDocument();
    });

    it("Displays a search button", () => {
        const { getByTitle } = render(<SearchBar {...searchBarMockProps} />);
        expect.assertions(2);
        const button = getByTitle("Search");
        expect(button).toBeInTheDocument();
        fireEvent.click(button);
        expect(searchBarMockProps.onSearch).toHaveBeenCalled();
    });

    it("Clears search text by clicking the clear button", () => {
        const { getByRole } = render(<StateFullSearchBar />);
        const input = getByRole("textbox", { name: "Search Text" });
        expect(input).toHaveValue("initial value");
        const button = getByRole("button", { name: "Clear Search" });
        expect(button).toBeInTheDocument();
        fireEvent.click(button);
        expect(input).toHaveValue("");
        expect(button).not.toBeInTheDocument();
    });

    const assertSearchResults = async (externalSearch?: { query: string; resultsInNewTab: boolean }) => {
        document.body.innerHTML = "";
        const { getByRole, container } = render(
            <SearchContext.Provider
                value={{ searchOptionProvider: new MockSearchData(), externalSearch: externalSearch }}
            >
                <MemoryRouter>
                    <IndependentSearch initialQuery="my search term" />
                </MemoryRouter>
            </SearchContext.Provider>,
        );
        await vi.dynamicImportSettled();
        const input = getByRole("textbox", { name: "Search Text" });
        expect(input).toHaveValue("my search term");
        fireEvent.change(input, { target: { value: "new search term" } });
        const results = container.querySelector(".search-results");
        expect(results).toBeInTheDocument();

        if (externalSearch?.query) {
            expect(results).toHaveTextContent("");
        } else {
            expect(results).toHaveTextContent("Search for new search term");
        }
    };

    it("Search results  population (autocomplete), will depend on external search configuration", async () => {
        await assertSearchResults();
        await assertSearchResults({ query: "#test", resultsInNewTab: false });
    });
});

function StateFullSearchBar() {
    const [value, setValue] = useState("initial value");
    return <SearchBar {...searchBarMockProps} value={value} onChange={setValue} />;
}

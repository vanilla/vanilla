/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { render, screen, fireEvent, waitFor, act, within } from "@testing-library/react";
import SearchBar from "@library/features/search/SearchBar";

jest.setTimeout(20000);

const searchBarMockProps = {
    value: "",
    onChange: jest.fn(),
    onSearch: jest.fn(),
};

describe("SearchBar", () => {
    it("Displays a scope dropdown", async () => {
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

        const { findByRole } = render(<SearchBar {...searchBarMockProps} scope={searchBarScope} />);

        const button = await findByRole("button", { name: "Everywhere" });
        expect(button).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(button);
        });
        const list = await screen.findByRole("list");
        expect(list).toBeInTheDocument();
        const firstOption = await within(list).findByText(/scope 1/, { exact: false });
        expect(firstOption).toBeInTheDocument();
        const secondOption = await within(list).findByText(/Everywhere/, { exact: false });
        expect(secondOption).toBeInTheDocument();
    });

    it("Displays a search button", async () => {
        const { findByTitle } = render(<SearchBar {...searchBarMockProps} />);
        expect.assertions(2);
        const button = await findByTitle("Search");
        expect(button).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(button);
        });
        expect(searchBarMockProps.onSearch).toHaveBeenCalled();
    });

    it("Clears search text by clicking the clear button", async () => {
        const { findByRole } = render(<SearchBar {...searchBarMockProps} value="hello starshine" />);
        const input = await findByRole("textbox", { name: "Search Text" });
        expect(input).toHaveValue("hello starshine");
        const button = await findByRole("button", { name: "Clear Search" });
        expect(button).toBeInTheDocument();
        await act(async () => {
            fireEvent.click(button);
        });
        waitFor(() => {
            expect(input).toHaveValue("");
            expect(button).not.toBeInTheDocument();
        });
    });

    it("Tabs to the clear button", async () => {
        const { findByRole } = render(<SearchBar {...searchBarMockProps} value="hello starshine" />);
        const input = await findByRole("textbox", { name: "Search Text" });
        await act(async () => {
            fireEvent.focus(input);
            fireEvent.keyPress(input, { key: "Tab" });
        });
        const button = await findByRole("button", { name: "Clear Search" });
        waitFor(() => {
            expect(button).toHaveFocus();
        });
    });
});

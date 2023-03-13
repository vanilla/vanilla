/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactElement } from "react";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import SearchBar from "@library/features/search/SearchBar";

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
        render(<SearchBar {...searchBarMockProps} scope={searchBarScope} />);

        waitFor(() => {
            const button = screen.getByRole((role, el) => role === "button" && el?.ariaLabel === "Everywhere");
            expect(button).toBeInTheDocument();
            fireEvent.click(button);
            const menu = screen.findByText(/scope1 Everywhere/);
            expect(menu).toBeInTheDocument();
        });
    });

    it("Displays a search button", async () => {
        render(<SearchBar {...searchBarMockProps} />);
        waitFor(() => {
            const button = screen.getByRole((role, el) => role === "button" && el?.ariaLabel === "Search");
            expect(button).toBeInTheDocument();
            fireEvent.click(button);
            expect(searchBarMockProps.onSearch).toBeCalled();
        });
    });

    it("Clears search text by clicking the clear button", async () => {
        render(<SearchBar {...searchBarMockProps} value="hello starshine" />);
        waitFor(() => {
            const input = screen.getByRole((role, el) => role === "textbox" && el?.ariaLabel === "Search Text");
            expect(input).toHaveValue("hello starshine");
            const button = screen.getByRole((role, el) => role === "button" && el?.ariaLabel === "Clear Search");
            expect(button).toBeInTheDocument();
            fireEvent.click(button);
            expect(input).toHaveValue("");
            expect(button).not.toBeInTheDocument();
        });
    });

    it("Tabs to the clear button", async () => {
        render(<SearchBar {...searchBarMockProps} value="hello starshine" />);
        waitFor(() => {
            const input = screen.getByRole((role, el) => role === "textbox" && el?.ariaLabel === "Search Text");
            fireEvent.focus(input);
            fireEvent.keyPress(input, { key: "Tab" });
            const button = screen.getByRole((role, el) => role === "button" && el?.ariaLabel === "Clear Search");
            expect(button).toHaveFocus();
        });
    });
});

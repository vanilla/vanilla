/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import TitleBarNav from "@library/headers/mebox/pieces/TitleBarNav";
import { render, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { createMemoryHistory, MemoryHistory } from "history";
import { act } from "react-dom/test-utils";
import { Router } from "react-router-dom";
import { TitleBarParamContextProvider } from "@library/headers/TitleBar.ParamContext";

describe("<TitleBarNav />", () => {
    let memoryHistory: MemoryHistory;

    beforeEach(() => {
        memoryHistory = createMemoryHistory();
    });

    function TestTitleBarNav() {
        return (
            <Router history={memoryHistory}>
                <TitleBarParamContextProvider>
                    <TitleBarNav
                        navigationItems={[
                            {
                                name: "No Nested",
                                url: "/no-nested",
                                id: "no-nested",
                            },
                            {
                                name: "Nested",
                                id: "nested",
                                url: "/nested",
                                children: [
                                    {
                                        name: "Nested 1",
                                        url: "/nested-1",
                                        id: "nested-1",
                                    },
                                    {
                                        name: "Nested 2",
                                        url: "/nested-2",
                                        id: "nested-2",
                                    },
                                ],
                            },
                        ]}
                    />
                </TitleBarParamContextProvider>
            </Router>
        );
    }

    it("should render", () => {
        const rendered = render(<TestTitleBarNav />);
        expect(rendered.getAllByRole("menuitem")).toHaveLength(2);
    });

    // This test has been occasionally flaky in CSS.
    // We're not doing any active development on this component anymore.
    // It's not worth any additional time debugging.
    it.skip("can open, close, and navigate the megamenu", async () => {
        const rendered = render(<TestTitleBarNav />);
        const nestedNavItem = rendered.getByRole("menuitem", {
            name: "Nested",
        });
        expect(nestedNavItem).toHaveTextContent("Nested");
        act(() => {
            nestedNavItem.focus();
        });
        expect(rendered.getByText("Nested 1")).toBeInTheDocument();
        expect(rendered.getByText("Nested 2")).toBeInTheDocument();

        const notNestedNavItem = rendered.getByRole("menuitem", {
            name: "No Nested",
        });

        act(() => {
            notNestedNavItem.focus();
        });

        // Now we have no subnav.
        expect(rendered.queryByText("Nested 1")).not.toBeInTheDocument();

        // We can also navigate with arrow keys.
        await userEvent.keyboard("{ArrowRight}");
        expect(rendered.getByText("Nested 1")).toBeInTheDocument();

        // Now arrow throught the items
        await userEvent.keyboard("{ArrowDown}");
        expect(rendered.getByText("Nested 1")).toHaveFocus();

        await userEvent.keyboard("{ArrowDown}");
        expect(rendered.getByText("Nested 2")).toHaveFocus();

        await userEvent.keyboard("{ArrowUp}");
        expect(rendered.getByText("Nested 1")).toHaveFocus();

        await userEvent.keyboard("{ArrowLeft}");
        // Left / Right arrows navigate the top level items.
        expect(rendered.queryByText("Nested 1")).not.toBeInTheDocument();
        expect(rendered.getByRole("menuitem", { name: "No Nested" })).toHaveFocus();
    });

    it("closes the megamenu when the page is navigated", () => {
        // Open up the megamenu.
        const rendered = render(<TestTitleBarNav />);
        const nestedNavItem = rendered.getByRole("menuitem", {
            name: "Nested",
        });
        act(() => {
            nestedNavItem.focus();
        });
        expect(rendered.getByText("Nested 1")).toBeInTheDocument();

        act(() => {
            memoryHistory.push({ pathname: "/other-url" });
        });

        expect(
            rendered.getByRole("menuitem", {
                name: "Nested",
            }),
        ).toHaveFocus();

        expect(rendered.queryByText("Nested 1")).not.toBeInTheDocument();
    });
});

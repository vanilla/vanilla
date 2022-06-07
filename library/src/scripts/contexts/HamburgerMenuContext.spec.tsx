/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { DynamicComponentTypes, HamburgerMenuContextProvider } from "@library/contexts/HamburgerMenuContext";
import { act, fireEvent, render, screen } from "@testing-library/react";
import { HamburgerWithComponents } from "@library/contexts/__fixtures__/HamburgerContextFixture";

describe("Hamburger Context", () => {
    it("Renders an added node component", () => {
        render(
            <HamburgerMenuContextProvider>
                <HamburgerWithComponents
                    componentsAddedToContext={[
                        {
                            title: "nodeTest",
                            type: DynamicComponentTypes.node,
                            node: <span>Node Test</span>,
                        },
                    ]}
                />
            </HamburgerMenuContextProvider>,
        );
        expect(screen.findByText(/Node Test/)).toBeTruthy();
    });

    it("Renders multiple added node components", () => {
        render(
            <HamburgerMenuContextProvider>
                <HamburgerWithComponents
                    componentsAddedToContext={[
                        {
                            title: "nodeTestOne",
                            type: DynamicComponentTypes.node,
                            node: <span>Node Test One</span>,
                        },
                        {
                            title: "nodeTestTwo",
                            type: DynamicComponentTypes.node,
                            node: <span>Node Test Two</span>,
                        },
                    ]}
                />
            </HamburgerMenuContextProvider>,
        );
        expect(screen.findByText(/Node Test One/)).toBeTruthy();
        expect(screen.findByText(/Node Test Two/)).toBeTruthy();
    });

    it("Renders a added tree component", () => {
        render(
            <HamburgerMenuContextProvider>
                <HamburgerWithComponents
                    componentsAddedToContext={[
                        {
                            title: "testTree",
                            type: DynamicComponentTypes.tree,
                            tree: [
                                {
                                    name: "Test Menu Item",
                                    parentID: 0,
                                    recordID: 1,
                                    sort: 1,
                                    recordType: "menuItem",
                                    isLink: false,
                                    children: [],
                                },
                            ],
                        },
                    ]}
                />
            </HamburgerMenuContextProvider>,
        );
        expect(screen.findByText(/Test Menu Item/)).toBeTruthy();
    });

    it("Renders a sub tree", async () => {
        render(
            <HamburgerMenuContextProvider>
                <HamburgerWithComponents
                    componentsAddedToContext={[
                        {
                            title: "testTree",
                            type: DynamicComponentTypes.tree,
                            tree: [
                                {
                                    name: "Test Parent Item",
                                    parentID: 0,
                                    recordID: 1,
                                    sort: 1,
                                    recordType: "parentItem",
                                    isLink: false,
                                    children: [
                                        {
                                            name: "Test Child Item",
                                            parentID: 1,
                                            recordID: 2,
                                            sort: 1,
                                            recordType: "childItem",
                                            isLink: false,
                                            children: [],
                                        },
                                    ],
                                },
                            ],
                        },
                    ]}
                />
            </HamburgerMenuContextProvider>,
        );
        fireEvent.click(await screen.findByText(/Test Parent Item/));
        expect(screen.findByText(/Test Child Item/)).toBeTruthy();
    });
});

/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import {
    DynamicComponentTypes,
    HamburgerMenuContextProvider,
    useHamburgerMenuContext,
} from "@library/contexts/HamburgerMenuContext";
import { fireEvent, render, screen } from "@testing-library/react";
import { HamburgerWithComponents } from "@library/contexts/__fixtures__/HamburgerContextFixture";
import { renderHook, act } from "@testing-library/react-hooks";

describe("Hamburger Context", () => {
    it("Renders an added node component", async () => {
        await act(async () => {
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
        });
        expect(await screen.findByText(/Node Test/)).toBeTruthy();
    });

    it("Renders multiple added node components", async () => {
        await act(async () => {
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
        });
        expect(await screen.findByText(/Node Test One/)).toBeTruthy();
        expect(await screen.findByText(/Node Test Two/)).toBeTruthy();
    });

    it("Renders a added tree component", async () => {
        await act(async () => {
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
        });
        expect(await screen.findByText(/Test Menu Item/)).toBeTruthy();
    });

    it("Renders a sub tree", async () => {
        await act(async () => {
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
        });

        await act(async () => {
            const parentItem = await screen.findByText(/Test Parent Item/);
            fireEvent.click(parentItem);
        });

        expect(await screen.findByText(/Test Child Item/)).toBeTruthy();
    });

    it("Hook has initial methods", async () => {
        const { result } = renderHook(() => useHamburgerMenuContext());
        let id;
        await act(async () => {
            id = result.current.addComponent({
                title: "addTest",
                type: DynamicComponentTypes.node,
                node: <span>Add Test</span>,
            });
        });
        expect(id).toBe(-1);
        await act(async () => {
            result.current.removeComponentByID(id);
        });
    });

    it("Add component adds to the dynamicComponent list", async () => {
        const wrapper = ({ children }) => <HamburgerMenuContextProvider>{children}</HamburgerMenuContextProvider>;
        const { result } = renderHook(() => useHamburgerMenuContext(), { wrapper });
        await act(async () => {
            result.current.addComponent({
                title: "addTest",
                type: DynamicComponentTypes.node,
                node: <span>Add Test</span>,
            });
        });

        // await act(async () => {

        expect(Object.keys(result.current.dynamicComponents ?? {}).length).toEqual(1);
        // });
    });

    it("Remove component removes dynamicComponent entry", async () => {
        const wrapper = ({ children }) => <HamburgerMenuContextProvider>{children}</HamburgerMenuContextProvider>;
        const { result, rerender } = renderHook(() => useHamburgerMenuContext(), { wrapper });

        const testNodeOne = <span>Add Test One</span>;

        let id;

        // First render
        await act(async () => {
            id = result.current.addComponent({
                title: "addTest",
                type: DynamicComponentTypes.node,
                node: testNodeOne,
            });
        });
        expect(result.current.dynamicComponents?.[id].component).toEqual(testNodeOne);

        // Second render
        await act(async () => {
            result.current.removeComponentByID(id);
        });

        expect(result.current.dynamicComponents?.[id]).toBeFalsy();
    });
});

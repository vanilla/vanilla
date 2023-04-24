/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MenuBar } from "@library/MenuBar/MenuBar";
import { TestMenuBarFlat, TestMenuBarNested } from "@library/MenuBar/MenuBar.fixtures";
import { IMenuBarContext } from "@library/MenuBar/MenuBarContext";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import { fireEvent, render, screen, act, waitFor } from "@testing-library/react";
import React from "react";

describe("<MenuBar />", () => {
    // don't forget test for disabled items.

    it("can navigate menu items with the keyboard", async () => {
        render(<TestMenuBarFlat />);
        const item1 = await screen.findByTitle("menuitem1");
        const item2 = await screen.findByTitle("menuitem2");
        const item3 = await screen.findByTitle("menuitem3");
        const item4 = await screen.findByTitle("menuitem4");
        const item5 = await screen.findByTitle("menuitem5"); // This one is disabled.

        await act(async () => {
            // Focus item 1.
            item1.focus();
            fireEvent.keyDown(item1, {
                key: "ArrowRight",
            });
        });

        expect(item2).toHaveFocus();

        await act(async () => {
            // We don't have nested children so arrowdown also moves right.
            fireEvent.keyDown(item2, {
                key: "ArrowDown",
            });
        });

        expect(item3).toHaveFocus();

        await act(async () => {
            // We can move back the other way.
            fireEvent.keyDown(item1, {
                key: "ArrowLeft",
            });
        });

        expect(item2).toHaveFocus();
        fireEvent.keyDown(item2, {
            key: "ArrowUp",
        });
        expect(item1).toHaveFocus();

        await act(async () => {
            // We cycle through and skip disabled elements.
            fireEvent.keyDown(item2, {
                key: "ArrowLeft",
            });
        });

        expect(item4).toHaveFocus();
    });

    describe("items can be activated", () => {
        const spy = jest.fn();
        beforeEach(() => {
            spy.mockReset();
            render(
                <MenuBar>
                    <MenuBarItem accessibleLabel="item" icon={""} onActivate={spy} />
                </MenuBar>,
            );
        });

        it("can activate with click", async () => {
            const item = screen.getByTitle("item");
            await act(async () => {
                fireEvent.click(item);
            });
            expect(item).toHaveFocus();
            expect(spy).toHaveBeenCalledTimes(1);
        });

        it("can activate with enter", async () => {
            const item = screen.getByTitle("item");
            await act(async () => {
                item.focus();
                fireEvent.keyDown(item, { key: "Enter" });
            });
            expect(item).toHaveFocus();
            expect(spy).toHaveBeenCalledTimes(1);
        });
        it("can activate with space", async () => {
            const item = screen.getByTitle("item");
            await act(async () => {
                item.focus();
                fireEvent.keyDown(item, { key: " " });
            });
            expect(item).toHaveFocus();
            expect(spy).toHaveBeenCalledTimes(1);
        });
    });

    // For the menu items and submenu items. The whole menu should only ever have 1 focusable element.
    it("implements roving tab index", async () => {
        let context: IMenuBarContext | null = null;

        const { container } = render(
            <MenuBar ref={(_context) => (context = _context)}>
                <MenuBarItem accessibleLabel="item0" icon={""} />
                <MenuBarItem accessibleLabel="item1" icon={""}>
                    <MenuBarSubMenuItem>sub1</MenuBarSubMenuItem>
                    <MenuBarSubMenuItem>sub2</MenuBarSubMenuItem>
                </MenuBarItem>
            </MenuBar>,
        );

        const item0 = await screen.findByTitle("item0");
        const item1 = await screen.findByTitle("item1");

        async function assertOnlyElementHasTabIndex(expectedElement: Element) {
            return waitFor(() => {
                const tabIndex0Elements = Array.from(container.querySelectorAll("[tabindex='0']"));
                expect(tabIndex0Elements).toHaveLength(1);
                expect(tabIndex0Elements[0]).toBe(expectedElement);
            });
        }

        await assertOnlyElementHasTabIndex(item0);

        act(() => {
            context!.setCurrentItemIndex(1);
        });

        await assertOnlyElementHasTabIndex(item1);

        act(() => {
            context!.setSubMenuOpen(true);
            context!.focusActiveSubMenu();
        });

        // FIXME! determine why this test fails
        // const sub1 = await screen.findByTitle("sub1");
        // await assertOnlyElementHasTabIndex(sub1);
    });

    describe("Submenu Behaviour", () => {
        function assertSubMenuOpen(withItemTitle: string) {
            const expected = screen.queryByTitle(withItemTitle);
            expect(expected, `Expected to find an item with the title ${withItemTitle}. `).not.toBeNull();
        }

        function assertSubMenuClosed(withoutItemTitle: string) {
            const expected = screen.queryByTitle(withoutItemTitle);
            expect(expected, `Expected not to find an item with the title '${withoutItemTitle}.`).toBeNull();
        }
        it("can navigate submenus with a mouse", async () => {
            await act(async () => {
                render(<TestMenuBarNested />);
            });
            const item1 = await screen.findByTitle("Item 1");
            const item2 = await screen.findByTitle("Item 2");

            assertSubMenuClosed("SubItem 1.0");

            // Click an item opens a submenu.
            await act(async () => {
                fireEvent.click(item1);
            });
            // Should open and these will be be visible.
            assertSubMenuOpen("SubItem 1.0");
            expect(item1).toHaveFocus();

            // Click on another submenu will open it and close the current one.
            await act(async () => {
                fireEvent.click(item2);
            });
            assertSubMenuOpen("SubItem 2.0");
            assertSubMenuClosed("SubItem 1.0"); // This one got closed.
            expect(item2).toHaveFocus();

            // Click on the item again closes it.
            await act(async () => {
                fireEvent.click(item2);
            });
            assertSubMenuClosed("SubItem 2.0");
            assertSubMenuClosed("SubItem 1.0");
            expect(item2, "test").toHaveFocus();
        });

        it("can open & close submenus with the keyboard", async () => {
            await act(async () => {
                render(<TestMenuBarNested />);
            });
            const item0 = await screen.findByTitle("Item 0");
            const item1 = await screen.findByTitle("Item 1");
            const item2 = await screen.findByTitle("Item 2");

            assertSubMenuClosed("SubItem 1.0");

            // Move over to item1
            await act(async () => {
                fireEvent.keyDown(item0, { key: "ArrowRight" });
            });

            // Click an item opens a submenu.
            item1.focus();
            await act(async () => {
                fireEvent.keyDown(item1, { key: "ArrowDown" });
            });

            // Should open and these will be be visible.
            assertSubMenuOpen("SubItem 1.0");
            expect(item1).toHaveFocus();

            // Going right will move to the next item, but keep the menu open.
            await act(async () => {
                fireEvent.keyDown(item1, { key: "ArrowRight" });
            });
            // Menu 2 should be open.
            assertSubMenuOpen("SubItem 2.0");
            expect(item2).toHaveFocus();

            // We can navigate into the menu.
            await act(async () => {
                fireEvent.keyDown(item2, { key: "ArrowDown" });
            });

            // Menu 2 should be open and the first item focused.
            const sub20 = screen.getByTitle("SubItem 2.0");
            // sub2.1 is disabled.
            const sub22 = screen.getByTitle("SubItem 2.2");
            const sub24 = screen.getByTitle("SubItem 2.4");

            expect(sub20).toHaveFocus();
            // We cycle through things.
            await act(async () => {
                fireEvent.keyDown(sub20, { key: "ArrowUp" });
            });
            expect(sub24).toHaveFocus();
            await act(async () => {
                fireEvent.keyDown(sub24, { key: "ArrowDown" });
            });
            expect(sub20).toHaveFocus();
            await act(async () => {
                fireEvent.keyDown(sub20, { key: "ArrowDown" });
            });

            // We can close the menu with escape.
            await act(async () => {
                fireEvent.keyDown(sub20, { key: "Escape" });
            });
            expect(item2).toHaveFocus();
            assertSubMenuClosed("SubItem 2.0");
        });
    });
});

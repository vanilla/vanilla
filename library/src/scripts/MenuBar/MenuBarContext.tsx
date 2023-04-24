/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import { MenuBarSubMenuItemGroup } from "@library/MenuBar/MenuBarSubMenuItemGroup";
import React, { useContext, useImperativeHandle, useRef, useState } from "react";

/**
 * Common interface for items (and submenu items).
 */
export interface IMenuBarContextItem {
    disabled?: boolean;
    active?: boolean;
    itemIndex?: number;
    itemRef?: (ref: IMenuBarItemRef) => void;
}

// Types of menubar context children.
type MenuBarItemChildType = React.ReactComponentElement<typeof MenuBarItem>;
type MenuBarSubMenuGroupChildType = React.ReactComponentElement<typeof MenuBarSubMenuItemGroup>;
export type MenuBarContextChildren =
    | MenuBarItemChildType
    | MenuBarSubMenuGroupChildType
    | Array<MenuBarItemChildType | MenuBarSubMenuGroupChildType | null | undefined | false>;

/**
 * Context values.
 */
export interface IMenuBarContext {
    /**
     * Set the index of the selected menubar item.
     */
    setCurrentItemIndex(currentIndex: number | null): void;

    /**
     * State: Index of the currently selected menubar itme.
     */
    currentItemIndex: number | null;

    /**
     * Increment the current menubar item. Skips disabled items and rolls over the end.
     */
    incrementItemIndex(): void;

    /**
     * Decrement the current menubar item. Skips disabled items and rolls over the beginning.
     */
    decrementItemIndex(): void;

    /**
     * State: Is the submenu currently open.
     */
    isSubMenuOpen: boolean;

    /**
     * Set if the submenu is currently open.
     */
    setSubMenuOpen(isOpen: boolean): void;

    /**
     * Move foucs into the submenu of the currently active menu item (if it has one).
     */
    focusActiveSubMenu(): void;
}

/**
 * A reference to a a single menu bar item.
 */
export interface IMenuBarItemRef {
    /**
     * Index of the item in the context.
     */
    itemIndex: number;

    /**
     * See if the item is currently disabled.
     */
    isDisabled: boolean;

    /**
     * Focus the item.
     */
    focus(): void;

    /**
     * Focus the submenu of the item if it has one.
     */
    focusSubMenu(): void;
}

// Actual react context.
const RealMenuBarContext = React.createContext<IMenuBarContext>({} as any);

/**
 * Get the closest menu bar context.
 */
export function useMenuBarContext() {
    return useContext(RealMenuBarContext);
}

/**
 * Props for a menubar context.
 */
interface IMenuBarContextProps {
    /** Children should be <MenuBarItem /> or <MenuBarSubMenuItem> or <MenuBarSubMenuItemGroup> */
    children: MenuBarContextChildren;

    /** When inside of a nested context, tells if the parent item is active. */
    isSelected?: boolean;

    /** An initial item index to use. Defaults to 0, but null can be used if no items in the tab order. */
    initialItemIndex: number | null;
}

export const MenuBarContext = React.forwardRef(function MenuBarContext(
    props: IMenuBarContextProps,
    ownRef: React.Ref<IMenuBarContext | null>,
) {
    let isSelected = props.isSelected ?? true;

    const { itemChildren, itemRefs, countItemChildren, itemDisabledValues } = useItemChildren(props.children);

    ///
    /// Manage the current item index.
    ///
    const [currentItemIndex, _setCurrentItemIndex] = useState<number | null>(props.initialItemIndex);
    const setCurrentItemIndex = (itemIndex: number | null) => {
        _setCurrentItemIndex(itemIndex);
        // Now focus the item.
        if (itemIndex !== null) {
            itemRefs.current[itemIndex]?.focus();
        }
    };

    const [isSubMenuOpen, setSubMenuOpen] = useState(false);

    const maxItemIndex = countItemChildren - 1;
    const minItemIndex = 0;

    const isAnyEnabled = itemDisabledValues.filter((item) => !item).length > 0;
    const isItemIndexEnabled = (itemIndex: number): boolean => {
        return !(itemDisabledValues[itemIndex] ?? false);
    };

    const incrementItemIndex = (fromIndex = currentItemIndex ?? 0) => {
        let newIndex = fromIndex + 1;
        if (newIndex > maxItemIndex) {
            newIndex = 0;
        }

        if (!isItemIndexEnabled(newIndex) && isAnyEnabled) {
            // Try the next one.
            incrementItemIndex(newIndex);
        } else {
            // This one is valid.
            setCurrentItemIndex(newIndex);
        }
    };

    const decrementItemIndex = (fromIndex = currentItemIndex ?? 0) => {
        let newIndex = fromIndex - 1;
        if (newIndex < minItemIndex) {
            newIndex = maxItemIndex;
        }

        if (!isItemIndexEnabled(newIndex) && isAnyEnabled) {
            // Try the next one.
            decrementItemIndex(newIndex);
        } else {
            // This one is valid.
            setCurrentItemIndex(newIndex);
        }
    };

    const focusActiveSubMenu = () => {
        if (currentItemIndex === null) {
            return;
        }
        itemRefs.current[currentItemIndex]?.focusSubMenu();
    };

    const contextValue: IMenuBarContext = {
        currentItemIndex: isSelected ? currentItemIndex : null,
        setCurrentItemIndex,
        incrementItemIndex,
        decrementItemIndex,
        setSubMenuOpen,
        isSubMenuOpen,
        focusActiveSubMenu,
    };

    // Push the context value into our ref.
    useImperativeHandle(ownRef, () => contextValue);

    return <RealMenuBarContext.Provider value={contextValue}>{itemChildren}</RealMenuBarContext.Provider>;
});

/**
 * Hook to manipulate our children and gets refs to them.
 *
 * - Loops through children (and submenugroup children) to find all item children.
 * - We generate a ioncrementing index over the potentially nested children.
 * - Inject an items index into it's props.
 * - Get refs to items by their index.
 * - Grab the disabled states of all children.
 */
function useItemChildren(children: MenuBarContextChildren) {
    let itemRefs = useRef<Array<IMenuBarItemRef | null>>([]);
    let countItemChildren = 0;
    const itemDisabledValues: boolean[] = [];

    let lastItemIndex = 0;
    const mapMenuItemChildren = (children: MenuBarContextChildren) => {
        return React.Children.map(children, (child) => {
            if (!child) {
                return null;
            }
            switch (child.type) {
                case MenuBarItem:
                case MenuBarSubMenuItem:
                    const itemIndex = lastItemIndex;
                    countItemChildren++;
                    lastItemIndex++;
                    itemDisabledValues[itemIndex] = child.props.disabled ?? false;
                    // We have a menubar item. Inject
                    return React.cloneElement(child as any, {
                        itemIndex,
                        itemRef: (ref: IMenuBarItemRef | null) => {
                            itemRefs.current[itemIndex] = ref;
                        },
                    });
                default:
                    if (child.props.children) {
                        return React.cloneElement(child as any, {
                            children: mapMenuItemChildren(child.props.children as MenuBarContextChildren),
                        });
                    } else {
                        return child;
                    }
            }
        });
    };

    const itemChildren = mapMenuItemChildren(children);
    return {
        itemRefs,
        itemChildren,
        countItemChildren,
        itemDisabledValues,
    };
}

export function useMenuBarContextItem<T extends HTMLElement = any>(
    props: IMenuBarContextItem,
    subMenuRef?: React.RefObject<IMenuBarContext | null>,
) {
    const menuBarContext = useMenuBarContext();
    const isSelected = menuBarContext.currentItemIndex !== null && props.itemIndex === menuBarContext.currentItemIndex;
    const ownRef = React.useRef<T>(null);
    const hasSubMenu = !!subMenuRef;
    const isOwnSubMenuOpen = hasSubMenu && isSelected && menuBarContext.isSubMenuOpen;
    const isTabbable = isSelected && !(hasSubMenu && isOwnSubMenuOpen);

    useImperativeHandle(props.itemRef, () => ({
        isDisabled: props.disabled ?? false,
        itemIndex: props.itemIndex ?? 0,
        focus: () => {
            ownRef.current?.focus();
        },
        focusSubMenu: () => {
            subMenuRef?.current?.setCurrentItemIndex(0);
        },
    }));

    return {
        isSelected,
        contextProps: {
            ref: ownRef,
            tabIndex: isTabbable ? 0 : -1,
            "aria-disabled": props.disabled,
        },
    };
}

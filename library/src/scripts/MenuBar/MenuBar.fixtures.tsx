/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { EditIcon } from "@library/icons/common";
import {
    BoldIcon,
    ItalicIcon,
    StrikeIcon,
    CodeIcon,
    ListOrderedIcon,
    ListUnorderedIcon,
    IndentIcon,
    OutdentIcon,
} from "@library/icons/editorIcons";
import { MenuBar } from "@library/MenuBar/MenuBar";
import { IMenuBarContext } from "@library/MenuBar/MenuBarContext";
import { MenuBarItem } from "@library/MenuBar/MenuBarItem";
import { MenuBarSubMenuItem } from "@library/MenuBar/MenuBarSubMenuItem";
import { MenuBarSubMenuItemGroup } from "@library/MenuBar/MenuBarSubMenuItemGroup";
import React, { useEffect, useRef } from "react";

export function TestMenuBarFlat() {
    return (
        <MenuBar>
            <MenuBarItem accessibleLabel="menuitem1" icon={<BoldIcon />} />
            <MenuBarItem accessibleLabel="menuitem2" icon={<ItalicIcon />} />
            <MenuBarItem accessibleLabel="menuitem3" icon={<StrikeIcon />} />
            <MenuBarItem accessibleLabel="menuitem4" icon={<CodeIcon />} />
            <MenuBarItem accessibleLabel="menuitem5" disabled icon={<EditIcon />} />
        </MenuBar>
    );
}

export const TestMenuBarNested = function TestMenuBarNested(props: { autoOpen?: boolean }) {
    const withSubNavRef = useRef<IMenuBarContext>(null);

    useEffect(() => {
        if (props.autoOpen) {
            const ctx = withSubNavRef.current!;
            ctx.setCurrentItemIndex(1);
            ctx.setSubMenuOpen(true);
        }
    }, [props.autoOpen]);
    return (
        <MenuBar ref={withSubNavRef}>
            <MenuBarItem accessibleLabel="Item 0" icon={<BoldIcon />} />
            <MenuBarItem accessibleLabel="Item 1" icon={<ItalicIcon />}>
                <MenuBarSubMenuItem>SubItem 1.0</MenuBarSubMenuItem>
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem icon={<ListOrderedIcon />}>SubItem 1.1</MenuBarSubMenuItem>
                    <MenuBarSubMenuItem disabled icon={<ListUnorderedIcon />}>
                        SubItem 1.2
                    </MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem icon={<IndentIcon />}>SubItem 1.3</MenuBarSubMenuItem>
                    <MenuBarSubMenuItem icon={<OutdentIcon />}>SubItem 1.4</MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
            </MenuBarItem>
            <MenuBarItem accessibleLabel="Item 2" icon={<StrikeIcon />}>
                <MenuBarSubMenuItemGroup>
                    <MenuBarSubMenuItem>SubItem 2.0</MenuBarSubMenuItem>
                    <MenuBarSubMenuItem disabled>SubItem 2.1</MenuBarSubMenuItem>
                    <MenuBarSubMenuItem>SubItem 2.2</MenuBarSubMenuItem>
                </MenuBarSubMenuItemGroup>
                <MenuBarSubMenuItem>SubItem 2.3</MenuBarSubMenuItem>
                <MenuBarSubMenuItem>SubItem 2.4</MenuBarSubMenuItem>
            </MenuBarItem>
            <MenuBarItem accessibleLabel="Item 3" disabled icon={<CodeIcon />} />
            <MenuBarItem accessibleLabel="Item 4" icon={<EditIcon />}>
                <MenuBarSubMenuItem>This is much longer than the other</MenuBarSubMenuItem>
                <MenuBarSubMenuItem>Way way way longer than the other subnavs</MenuBarSubMenuItem>
                <MenuBarSubMenuItem>Long3.3</MenuBarSubMenuItem>
            </MenuBarItem>
        </MenuBar>
    );
};

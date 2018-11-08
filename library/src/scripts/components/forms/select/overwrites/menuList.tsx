/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { MenuList, MenuListComponentProps } from "react-select/lib/components/Menu";

/**
 * Overwrite for the input menuList component in React Select
 * Note that this is NOT a true react component and gets called within the react select plugin
 * @param props - props for menuList
 */
export default function menuList(props: MenuListComponentProps<any>) {
    return (
        <MenuList {...props}>
            <ul className="suggestedTextInput-menuItems">{props.children}</ul>
        </MenuList>
    );
}

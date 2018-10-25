/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { components } from "react-select";

export default function MenuList ({ children, ...props}: any) {
    return (
        <components.MenuList {...props} styles={{}} className={`${props.prefix}-menuList`}>
            {props.children}
        </components.MenuList>
    );
}

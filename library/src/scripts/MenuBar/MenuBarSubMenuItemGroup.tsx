/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContextItem, useMenuBarContext, useMenuBarContextItem } from "@library/MenuBar/MenuBarContext";
import { cx } from "@library/styles/styleShim";
import React, { useEffect, useImperativeHandle, useRef } from "react";

interface IProps extends IMenuBarContextItem {
    children: React.ReactNode;
}

export function MenuBarSubMenuItemGroup(props: IProps) {
    const classes = menuBarClasses();
    return (
        <div tabIndex={-1} role="none" className={cx(classes.subMenuGroup, "subMenuGroup")}>
            <div role="group">{props.children}</div>
        </div>
    );
}

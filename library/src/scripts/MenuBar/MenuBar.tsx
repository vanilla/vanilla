/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContext, MenuBarContext, MenuBarContextChildren } from "@library/MenuBar/MenuBarContext";
import { MenuBarSubMenuContainer, MenuBarSubMenuContext } from "@library/MenuBar/MenuBarSubMenu";
import { cx } from "@library/styles/styleShim";
import { useFocusWatcher, useMeasure } from "@vanilla/react-utils";
import React, { useImperativeHandle, useRef } from "react";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: MenuBarContextChildren;
    className?: string;
}

/**
 * Component for creating accessible menu bars following the WCAG spec.
 *
 * https://www.w3.org/WAI/ARIA/apg/example-index/menubar/menubar-editor.html
 *
 *
 */
export const MenuBar = React.forwardRef(function MenuBar(props: IProps, ref: React.Ref<IMenuBarContext | null>) {
    const { className, children, ...restProps } = props;
    const classes = menuBarClasses();
    const rootRef = useRef<HTMLDivElement>(null);
    const contextRef = useRef<IMenuBarContext>(null);

    // Allow getting a ref of our context so we can be controlled externally.
    useImperativeHandle(ref, () => contextRef.current);

    // If there's a way to do this in just CSS, I'd love to know it.
    // Essentially I want the first items to make the width of the container
    // And the submenu to match that width without setting a fixed width.
    // Eg. The submenus should never be wider than the parent.
    const itemContainerRef = useRef<HTMLDivElement>(null);
    const itemContainerMeasure = useMeasure(itemContainerRef);

    // Close submenus when we lose focus.
    useFocusWatcher(rootRef, (hasFocus) => {
        if (!hasFocus) {
            contextRef.current?.setSubMenuOpen(false);
        }
    });
    return (
        <MenuBarSubMenuContext>
            <div {...restProps} ref={rootRef} className={cx(classes.root, className)}>
                <div ref={itemContainerRef} className={classes.menuItemsList} role="menubar">
                    <MenuBarContext ref={contextRef} initialItemIndex={0}>
                        {props.children}
                    </MenuBarContext>
                </div>
                <div style={{ width: itemContainerMeasure.width }} className={classes.subMenuContainer}>
                    <MenuBarSubMenuContainer />
                </div>
            </div>
        </MenuBarSubMenuContext>
    );
});

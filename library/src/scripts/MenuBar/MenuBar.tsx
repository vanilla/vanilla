/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContext, MenuBarContext, MenuBarContextChildren } from "@library/MenuBar/MenuBarContext";
import { MenuBarSubMenuContainer, MenuBarSubMenuContext } from "@library/MenuBar/MenuBarSubMenu";
import { cx } from "@library/styles/styleShim";
import { useFocusWatcher, useMeasure } from "@vanilla/react-utils";
import React, { useImperativeHandle, useRef } from "react";
import Popover, { positionDefault } from "@reach/popover";

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    children: MenuBarContextChildren;
    className?: string;
    rootRef?: React.RefObject<HTMLDivElement>;
    asPopover?: boolean;
}

/**
 * Component for creating accessible menu bars following the WCAG spec.
 *
 * https://www.w3.org/WAI/ARIA/apg/example-index/menubar/menubar-editor.html
 *
 *
 */
export const MenuBar = React.forwardRef(function MenuBar(props: IProps, ref: React.Ref<IMenuBarContext | null>) {
    const { className, children, rootRef: _rootRef, asPopover, ...restProps } = props;
    const classes = menuBarClasses.useAsHook();
    const ownRef = useRef<HTMLDivElement>(null);
    const rootRef = _rootRef ?? ownRef;
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

    const content = (
        <>
            <div ref={itemContainerRef} className={classes.menuItemsList} role="menubar">
                <MenuBarContext ref={contextRef} initialItemIndex={0}>
                    {props.children}
                </MenuBarContext>
            </div>
            <div
                style={{ width: itemContainerMeasure.width ? itemContainerMeasure.width : undefined }}
                className={classes.subMenuContainer}
            >
                <MenuBarSubMenuContainer />
            </div>
        </>
    );
    return (
        <MenuBarSubMenuContext>
            {asPopover && props.rootRef ? (
                <Popover targetRef={rootRef} className={cx(classes.root, className)} position={centeredTopPosition}>
                    {content}
                </Popover>
            ) : (
                <div {...restProps} ref={rootRef} className={cx(classes.root, className)}>
                    {content}
                </div>
            )}
        </MenuBarSubMenuContext>
    );
});

/**
 * Centered top position for the popover, default is below left and reposition to top if there is collision
 * So we center it and stick it on top even if there is collision when scrolling
 */
function centeredTopPosition(targetRect?: DOMRect | null, popoverRect?: DOMRect | null): React.CSSProperties {
    const position = positionDefault(targetRect, popoverRect);

    const isAlreadyOnTop = (parseFloat(position.top as string) ?? 0) < (targetRect?.top ?? 0) + window.scrollY;
    const adjustValueForTop = isAlreadyOnTop || !targetRect?.height ? -(popoverRect?.height ?? 0) : targetRect?.height;

    const adjustedTop =
        targetRect && targetRect.height ? (parseFloat(position.top as string) ?? 0) - adjustValueForTop : position.top;
    const adjustedLeft =
        targetRect && targetRect.width && popoverRect && popoverRect.width
            ? (parseFloat(position.left as string) ?? 0) + targetRect.width / 2
            : position.left;

    return {
        ...position,
        top: adjustedTop,
        left: adjustedLeft,
    };
}

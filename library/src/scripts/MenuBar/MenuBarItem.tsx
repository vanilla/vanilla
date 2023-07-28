/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import {
    IMenuBarContext,
    IMenuBarContextItem,
    MenuBarContext,
    MenuBarContextChildren,
    useMenuBarContext,
    useMenuBarContextItem,
} from "@library/MenuBar/MenuBarContext";
import { useMenuBarSubMenuContext } from "@library/MenuBar/MenuBarSubMenu";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useRef } from "react";

type IProps = IMenuBarContextItem & {
    accessibleLabel: string;
    onActivate?: (event: React.SyntheticEvent<HTMLSpanElement>) => void;
    children?: MenuBarContextChildren;
    buttonType?: ButtonTypes;
    className?: string;
} & (
        | {
              icon: React.ReactNode;
          }
        | {
              textContent: React.ReactNode;
          }
    );

export const MenuBarItem = function MenuBarItem(props: IProps) {
    const menuBarContext = useMenuBarContext();
    const { isSubMenuOpen } = menuBarContext;
    const subMenuContext = useMenuBarSubMenuContext();
    const subMenuRef = useRef<IMenuBarContext | null>(null);
    const { isSelected, contextProps } = useMenuBarContextItem(props, subMenuRef);
    const hasSubMenu = !!props.children;
    const classes = menuBarClasses();
    const submenuID = useUniqueID("submenu");

    const handleActivate = (event: React.SyntheticEvent<HTMLSpanElement>) => {
        event.preventDefault();
        event.stopPropagation();
        event.nativeEvent.stopImmediatePropagation();
        menuBarContext.setCurrentItemIndex(props.itemIndex ?? null);
        if (hasSubMenu) {
            if (isSelected && isSubMenuOpen) {
                menuBarContext.setSubMenuOpen(false);
            } else {
                menuBarContext.setSubMenuOpen(true);
            }
        }
        props.onActivate?.(event);
    };

    return (
        <div
            className={cx(classes.menuItem, props.className)}
            onKeyDown={(event) => {
                switch (event.key) {
                    case "ArrowRight":
                        menuBarContext.incrementItemIndex();
                        break;
                    case "ArrowDown":
                        if (hasSubMenu) {
                            // First down opens the sub-menu, second navigates into it.
                            if (!isSubMenuOpen) {
                                menuBarContext.setSubMenuOpen(true);
                            } else {
                                menuBarContext.focusActiveSubMenu();
                            }
                        } else {
                            menuBarContext.incrementItemIndex();
                        }
                        break;
                    case "ArrowLeft":
                        menuBarContext.decrementItemIndex();
                        break;
                    case "ArrowUp":
                        if (hasSubMenu) {
                            if (isSubMenuOpen) {
                                // Close the submenu if it's open.
                                // If we're inside the submenu, the submenu will be handling the arrowUp
                                // So to close it we use the escape key.
                                menuBarContext.setSubMenuOpen(false);
                                contextProps.ref.current?.focus();
                            }
                        } else {
                            menuBarContext.decrementItemIndex();
                        }
                        break;
                    case "Escape":
                        if (!hasSubMenu || !isSubMenuOpen) {
                            return;
                        }
                        // Close the submenu.
                        menuBarContext.setSubMenuOpen(false);
                        // Then focus the current item.
                        menuBarContext.setCurrentItemIndex(menuBarContext.currentItemIndex);
                        break;

                    default:
                        return;
                }

                // We handled the event.
                event.stopPropagation();
                event.preventDefault();
            }}
        >
            <span>
                <span
                    {...contextProps}
                    className={cx("icon" in props ? classes.menuItemIconContent : classes.menuItemTextContent, {
                        active: props.active,
                    })}
                    ///
                    /// Accessibility
                    ///
                    title={props.accessibleLabel}
                    aria-label={props.accessibleLabel}
                    role="menuitem"
                    // Establish ownership of the portaled submenu if it exists.
                    aria-owns={hasSubMenu ? submenuID : undefined}
                    // State if we have a submenu or not.
                    aria-haspopup={hasSubMenu}
                    // State if the submenu is open or not.
                    aria-expanded={hasSubMenu && isSubMenuOpen}
                    onKeyDown={(event) => {
                        if (event.key === " " || event.key === "Enter") {
                            handleActivate(event);
                        }
                    }}
                    onClick={(event) => {
                        handleActivate(event);
                    }}
                >
                    {"icon" in props ? props.icon : props.textContent}
                </span>
            </span>

            {isSelected &&
                isSubMenuOpen &&
                props.children != null &&
                subMenuContext.renderSubMenu(
                    <div
                        // We're rendering in a portal, so this should be owned by the menuitem.
                        id={submenuID}
                        role="menu"
                        className={classes.subMenuItemsList}
                    >
                        <MenuBarContext
                            // These items are not focusable until we activate them.
                            initialItemIndex={null}
                            // Don't allow them to be focused until they are activated.
                            isSelected={isSelected}
                            // Grab a reference to
                            ref={subMenuRef}
                        >
                            {props.children}
                        </MenuBarContext>
                    </div>,
                )}
        </div>
    );
};

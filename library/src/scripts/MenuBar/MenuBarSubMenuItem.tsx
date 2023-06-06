/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContextItem, useMenuBarContext, useMenuBarContextItem } from "@library/MenuBar/MenuBarContext";
import React from "react";

interface IProps extends IMenuBarContextItem {
    children: React.ReactNode;
    icon?: React.ReactNode;
    onActivate?: () => void;
}

export function MenuBarSubMenuItem(props: IProps) {
    const { contextProps } = useMenuBarContextItem(props);
    const classes = menuBarClasses();
    const menuBarContext = useMenuBarContext();

    const handleClick = (event: React.SyntheticEvent) => {
        event.preventDefault();
        event.stopPropagation();
        props.onActivate?.();
    };

    return (
        <div
            role="none"
            title={typeof props.children === "string" ? props.children : undefined}
            {...contextProps}
            className={cx(classes.subMenuItem, "subMenuItem", { active: props.active })}
            onKeyDown={(event) => {
                switch (event.key) {
                    case "ArrowDown":
                        menuBarContext.incrementItemIndex();
                        break;
                    case "ArrowUp":
                        menuBarContext.decrementItemIndex();
                        break;
                    case " ":
                    case "Enter":
                        handleClick(event);
                        break;
                    default:
                        return;
                }

                // We handled the event.
                event.stopPropagation();
                event.preventDefault();
            }}
            onClick={handleClick}
        >
            {props.icon && (
                <span aria-hidden={true} className={classes.subMenuItemIcon}>
                    {props.icon}
                </span>
            )}
            <span className={classes.subMenuItemText}>{props.children}</span>
        </div>
    );
}

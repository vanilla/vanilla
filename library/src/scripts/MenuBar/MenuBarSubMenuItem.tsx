/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContextItem, useMenuBarContext, useMenuBarContextItem } from "@library/MenuBar/MenuBarContext";
import React from "react";

export namespace MenuBarSubMenuItem {
    export type SubMenuRendererProps = {
        ref: React.RefObject<any>;
        tabIndex: number;
        onKeyDown: (event: any) => void;
        active?: boolean;
        "aria-disabled"?: boolean;
    };
    export type SubMenuRenderer = React.ComponentType<SubMenuRendererProps>;
    export type Props = IMenuBarContextItem & {
        children?: React.ReactNode;
        icon?: React.ReactNode;
        onActivate?: () => void;
        isInline?: boolean;
        accessibleLabel?: string;

        // if we have this, we'll pass our context to it and the rest is renderer's responsibility
        subMenuItemRenderer?: SubMenuRenderer;
    };
}

export function MenuBarSubMenuItem(props: MenuBarSubMenuItem.Props) {
    const { contextProps } = useMenuBarContextItem(props);
    const classes = menuBarClasses();
    const menuBarContext = useMenuBarContext();

    const handleClick = (event: React.SyntheticEvent) => {
        event.preventDefault();
        event.stopPropagation();
        props.onActivate?.();
    };

    const accessibleLabel = props.accessibleLabel
        ? props.accessibleLabel
        : typeof props.children === "string"
        ? props.children
        : undefined;

    const keyDownEventHandler = (event) => {
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
    };

    const SubMenuItemRenderer = props.subMenuItemRenderer;

    if (SubMenuItemRenderer) {
        return <SubMenuItemRenderer {...contextProps} onKeyDown={keyDownEventHandler} active={props.active} />;
    }

    return (
        <div
            role="button"
            title={accessibleLabel}
            aria-label={accessibleLabel}
            {...contextProps}
            className={cx(
                classes.subMenuItem,
                "subMenuItem",
                { active: props.active },
                { ["isInline"]: props.isInline },
            )}
            onKeyDown={keyDownEventHandler}
            onClick={handleClick}
        >
            {props.icon && (
                <span
                    aria-hidden={true}
                    className={cx(classes.subMenuItemIcon, {
                        [classes.menuItemIconContent]: props.isInline,
                    })}
                >
                    {props.icon}
                </span>
            )}
            <span className={classes.subMenuItemText}>{props.children}</span>
        </div>
    );
}

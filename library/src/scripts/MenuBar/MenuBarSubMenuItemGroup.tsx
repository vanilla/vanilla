/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { Row } from "@library/layout/Row";
import { menuBarClasses } from "@library/MenuBar/MenuBar.classes";
import { IMenuBarContextItem } from "@library/MenuBar/MenuBarContext";
import { cx } from "@library/styles/styleShim";
import React from "react";

interface IProps extends IMenuBarContextItem {
    children: React.ReactNode;
    hasInlineSubMenuItems?: boolean;
    groupTitle?: string;
    className?: string;
}

export function MenuBarSubMenuItemGroup(props: IProps) {
    const { groupTitle, hasInlineSubMenuItems } = props;
    const classes = menuBarClasses();
    return (
        <div tabIndex={-1} role="none" className={cx(classes.subMenuGroup, "subMenuGroup")}>
            {hasInlineSubMenuItems && groupTitle && <div className={classes.subMenuGroupTitle}>{groupTitle}</div>}
            {hasInlineSubMenuItems ? (
                <Row role="group" tag="div" className={cx(classes.inlineSubMenuItemsWrapper, props.className)}>
                    {props.children}
                </Row>
            ) : (
                <div role="group" className={props.className}>
                    {props.children}
                </div>
            )}
        </div>
    );
}

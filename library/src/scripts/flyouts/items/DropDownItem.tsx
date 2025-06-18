/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

interface IDropDownItemProps {
    children: React.ReactNode;
    className?: string;
}

/**
 * Generic wrap for items in DropDownMenu
 */
export default function DropDownItem(props: IDropDownItemProps) {
    const classes = dropDownClasses.useAsHook();
    return (
        <li className={classNames(props.className, classes.item)} role="menuitem">
            {props.children}
        </li>
    );
}

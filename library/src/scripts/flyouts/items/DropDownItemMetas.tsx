/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default function DropDownItemMetas(props: IProps) {
    const classes = dropDownClasses.useAsHook();
    return <DropDownItem className={classNames(classes.metaItems, props.className)}>{props.children}</DropDownItem>;
}

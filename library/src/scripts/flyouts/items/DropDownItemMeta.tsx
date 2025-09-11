/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default function DropDownItemMeta(props: IProps) {
    const classes = dropDownClasses.useAsHook();
    return <div className={classNames(props.className, classes.metaItem)}>{props.children}</div>;
}

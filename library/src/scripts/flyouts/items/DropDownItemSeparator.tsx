/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

interface IDropDownSeparatorProps {
    className?: string;
}

/**
 * Implements line separator type of item for DropDownMenu
 */
export default function DropDownItemSeparator(props: IDropDownSeparatorProps) {
    const classes = dropDownClasses.useAsHook();
    return <li role="separator" className={classNames(props.className, classes.separator)} />;
}

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { dropDownClasses } from "@library/styles/dropDownStyles";

interface IProps {
    className?: string;
    children: React.ReactNode;
}

/**
 * Implements meta type of item for DropDownMenu
 */
export default class DropDownItemMeta extends React.Component<IProps> {
    public render() {
        const classes = dropDownClasses();
        return <div className={classNames(this.props.className, classes.metaItem)}>{this.props.children}</div>;
    }
}

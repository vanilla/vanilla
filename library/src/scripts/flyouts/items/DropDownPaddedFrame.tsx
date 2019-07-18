/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";

export interface IProps {
    children: React.ReactNode;
    className?: string;
}

/**
 * Generic wrap for items in DropDownMenu
 */
export default class DropDownPaddedFrame extends React.Component<IProps> {
    public render() {
        const classes = dropDownClasses();
        return (
            <li
                onClick={e => {
                    e.stopPropagation();
                }}
                className={classNames(this.props.className, classes.paddedFrame)}
            >
                {this.props.children}
            </li>
        );
    }
}

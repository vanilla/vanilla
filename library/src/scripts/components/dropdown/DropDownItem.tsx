/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";

export interface IProps {
    className?: string;
    children: React.ReactNode;
}

export default class DropDownItem extends React.Component<IProps> {
    public render() {
        return (
            <li className={classNames("dropDownItem", this.props.className)}>
                {this.props.children}
            </li>
        );
    }
}

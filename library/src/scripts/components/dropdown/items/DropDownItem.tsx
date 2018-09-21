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
            <DropDownItem className={this.props.className}>
                {this.props.children}
            </DropDownItem>
        );
    }
}

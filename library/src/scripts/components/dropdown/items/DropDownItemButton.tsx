/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";


export interface IDropDownItemButton {
    name: string;
    children: React.ReactNode | string;
    onClick: any;
    className?: string;
}

export default class DropDownItemButton extends React.Component<IDropDownItemButton> {
    public render() {
        return (
            <DropDownItem className={this.props.className}>
                <button type="button" title={this.props.name} onClick={this.props.onClick} className={this.props.className}>
                    {this.props.children}
                </button>
            </DropDownItem>
        );
    }
}

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { NavLink } from "react-router-dom";
import DropDownItem from "@library/components/dropdown/items/DropDownItem";
import classNames from "classnames";

export interface IDropDownItemButton {
    name: string;
    className?: string;
    children?: React.ReactNode;
    onClick: any;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default class DropDownItemButton extends React.Component<IDropDownItemButton> {
    public render() {
        const buttonContent = this.props.children ? this.props.children : this.props.name;
        return (
            <DropDownItem className={classNames("dropDown-buttonItem", this.props.className)}>
                <button
                    type="button"
                    title={this.props.name}
                    onClick={this.props.onClick}
                    className={classNames("dropDownItem-button", this.props.className)}
                >
                    {buttonContent}
                </button>
            </DropDownItem>
        );
    }
}

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownItem from "./DropDownItem";
import Button from "@library/components/forms/Button";

export interface IDropDownItemButton {
    name: string;
    className?: string;
    children?: React.ReactNode;
    onClick: any;
    disabled?: boolean;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default class DropDownItemButton extends React.Component<IDropDownItemButton> {
    public static defaultProps = {
        disabled: false,
    };

    public render() {
        const buttonContent = this.props.children ? this.props.children : this.props.name;
        return (
            <DropDownItem className={classNames("dropDown-buttonItem", this.props.className)}>
                <Button
                    type="button"
                    title={this.props.name}
                    onClick={this.props.onClick}
                    className={classNames("dropDownItem-button", this.props.className)}
                    disabled={this.props.disabled}
                >
                    {buttonContent}
                </Button>
            </DropDownItem>
        );
    }
}

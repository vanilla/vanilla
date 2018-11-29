/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownItem from "./DropDownItem";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { ISelectBoxItem } from "@library/components/SelectBox";

export interface IDropDownItemButton {
    name: string;
    className?: string;
    buttonClassName?: string;
    children?: React.ReactNode;
    disabled?: boolean;
    onClick: any;
    clickData?: ISelectBoxItem;
    index?: number;
    current?: boolean;
    lang?: string;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default class DropDownItemButton extends React.Component<IDropDownItemButton> {
    public static defaultProps = {
        disabled: false,
        buttonClassName: "dropDownItem-button",
    };

    public render() {
        const buttonContent = this.props.children ? this.props.children : this.props.name;
        return (
            <DropDownItem className={this.props.className}>
                <Button
                    type="button"
                    title={this.props.name}
                    onClick={this.props.onClick.bind(this, this.props.clickData, this.props.index)}
                    className={this.props.buttonClassName}
                    baseClass={ButtonBaseClass.CUSTOM}
                    disabled={this.props.disabled}
                    aria-current={this.props.current ? "true" : "false"}
                    lang={this.props.lang}
                >
                    {buttonContent}
                </Button>
            </DropDownItem>
        );
    }
}

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";

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
        buttonClassName: classNames("dropDownItem-button", dropDownClasses().action),
    };

    public render() {
        const { clickData, index, children, name } = this.props;
        const buttonContent = children ? children : name;
        const classesDropDown = dropDownClasses();
        const buttonClick = () => {
            this.props.onClick(clickData, index);
        };
        return (
            <DropDownItem className={classNames(this.props.className, classesDropDown.item)}>
                <Button
                    title={this.props.name}
                    onClick={buttonClick}
                    className={classNames(this.props.buttonClassName, classesDropDown.action)}
                    baseClass={ButtonTypes.CUSTOM}
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

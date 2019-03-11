/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import DropDownItem from "./DropDownItem";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { ISelectBoxItem } from "@library/components/SelectBox";
import { dropDownClasses } from "@library/styles/dropDownStyles";

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
                    type="button"
                    title={this.props.name}
                    onClick={buttonClick}
                    className={classNames(this.props.buttonClassName, classesDropDown.itemAction)}
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

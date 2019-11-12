/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";

export interface IDropDownItemButton {
    name?: string;
    className?: string;
    buttonClassName?: string;
    children?: React.ReactNode;
    disabled?: boolean;
    onClick: any;
    current?: boolean;
    lang?: string;
    buttonRef?: React.RefObject<HTMLButtonElement>;
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
        const { children, name } = this.props;
        const buttonContent = children ? children : name;
        const classesDropDown = dropDownClasses();
        return (
            <DropDownItem className={classNames(this.props.className)}>
                <Button
                    buttonRef={this.props.buttonRef}
                    title={this.props.name}
                    onClick={this.props.onClick}
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

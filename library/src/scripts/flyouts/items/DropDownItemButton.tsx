/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";

interface IProps {
    name?: string;
    className?: string;
    buttonClassName?: string;
    children?: React.ReactNode;
    disabled?: boolean;
    onClick: any;
    current?: boolean;
    lang?: string;
    isActive?: boolean;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    role?: string;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default function DropDownItemButton(props: IProps) {
    const { children, name, disabled = false } = props;
    const buttonContent = children ? children : name;
    const classes = dropDownClasses();
    const defaultButtonClass = dropDownClasses().action;
    const buttonClassName = classNames("dropDownItem-button", props.buttonClassName ?? defaultButtonClass);
    return (
        <DropDownItem className={classNames(props.className)}>
            <Button
                buttonRef={props.buttonRef}
                title={props.name}
                onClick={props.onClick}
                className={classNames(buttonClassName, classes.action, props.isActive && classes.actionActive)}
                baseClass={ButtonTypes.CUSTOM}
                disabled={disabled}
                aria-current={props.current ? "true" : "false"}
                lang={props.lang}
                role={props.role}
            >
                {buttonContent}
            </Button>
        </DropDownItem>
    );
}

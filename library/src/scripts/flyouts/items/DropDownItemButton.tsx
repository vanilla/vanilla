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
import { useLayout } from "@library/layout/LayoutContext";

export interface IDropDownItemButton {
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
export default function DropDownItemButton(props: IDropDownItemButton) {
    const { mediaQueries } = useLayout();
    const classes = dropDownClasses({ mediaQueries });
    const {
        children,
        name,
        disabled = false,
        buttonClassName = classNames("dropDownItem-button", classes.action),
        className,
        buttonRef,
        isActive,
        current,
        onClick,
        lang,
        role,
    } = props;
    const buttonContent = children ? children : name;
    return (
        <DropDownItem className={className}>
            <Button
                buttonRef={buttonRef}
                title={name}
                onClick={onClick}
                className={classNames(buttonClassName, classes.action, isActive && classes.actionActive)}
                baseClass={ButtonTypes.CUSTOM}
                disabled={disabled}
                aria-current={current ? "true" : "false"}
                lang={lang}
                role={role}
            >
                {buttonContent}
            </Button>
        </DropDownItem>
    );
}

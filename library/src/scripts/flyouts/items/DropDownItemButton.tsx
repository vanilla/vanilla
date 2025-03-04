/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import ButtonLoader from "@library/loaders/ButtonLoader";
import type { UseMutationResult, UseQueryResult } from "@tanstack/react-query";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import { extractErrorMessage } from "@library/errorPages/CoreErrorMessages";

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
    isLoading?: boolean;
    mutation?: UseMutationResult;
    query?: UseQueryResult;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default function DropDownItemButton(props: IProps) {
    const { children, name, query, mutation, disabled } = props;
    const buttonContent = children ? children : name;
    const classes = dropDownClasses();
    const defaultButtonClass = dropDownClasses().action;
    const buttonClassName = classNames("dropDownItem-button", props.buttonClassName ?? defaultButtonClass);

    const isLoading = props.isLoading || query?.isLoading || mutation?.isLoading;

    return (
        <DropDownItem className={classNames(props.className)}>
            <Button
                buttonRef={props.buttonRef}
                title={props.name}
                onClick={props.onClick}
                className={classNames(buttonClassName, classes.action, props.isActive && classes.actionActive)}
                buttonType={ButtonTypes.CUSTOM}
                disabled={disabled ?? (query?.isLoading || query?.isError || mutation?.isLoading)}
                aria-current={props.current ? "true" : "false"}
                lang={props.lang}
                role={props.role}
            >
                <span className={classes.text}>{buttonContent}</span>

                {query?.error || mutation?.error ? (
                    <ToolTip label={extractErrorMessage(query?.error ?? mutation?.error)}>
                        <ToolTipIcon>
                            <Icon icon="status-alert" />
                        </ToolTipIcon>
                    </ToolTip>
                ) : undefined}
                {isLoading && <ButtonLoader className={classes.loader} />}
            </Button>
        </DropDownItem>
    );
}

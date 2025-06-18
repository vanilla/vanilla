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
import { cx } from "@emotion/css";

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
    shortcut?: string;
}

/**
 * Implements button type of item for DropDownMenu
 */
export default function DropDownItemButton(props: IProps) {
    const { children, name, query, mutation, disabled } = props;
    const buttonContent = children ? children : name;
    const classes = dropDownClasses.useAsHook();
    const defaultButtonClass = dropDownClasses.useAsHook().action;
    const buttonClassName = cx("dropDownItem-button", props.buttonClassName ?? defaultButtonClass);

    const isLoading = props.isLoading || query?.isLoading || mutation?.isLoading;

    return (
        <DropDownItem className={props.className}>
            <Button
                buttonRef={props.buttonRef}
                title={props.name}
                onClick={props.onClick}
                className={cx(buttonClassName, classes.action, props.isActive && classes.actionActive)}
                buttonType={ButtonTypes.CUSTOM}
                disabled={disabled ?? (query?.isLoading || query?.isError || mutation?.isLoading)}
                aria-current={props.current ? "true" : "false"}
                lang={props.lang}
                role={props.role}
            >
                <span className={classes.text}>{buttonContent}</span>

                {props.shortcut && <span>{props.shortcut}</span>}
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

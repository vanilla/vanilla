/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { IRadioGroupProps, withRadioGroup } from "@library/forms/radioAsButtons/RadioGroupContext";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpersVisibility";
import ButtonLoader from "@library/loaders/ButtonLoader";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { LiveMessage } from "react-aria-live";
import { sprintf } from "sprintf-js";
import { RecordID } from "@vanilla/utils";

export interface IBaseRadioProps {
    label: string;
    data: RecordID;
    className?: string;
    disabled?: boolean;
    isLoading?: boolean;
    icon?: React.ReactNode;
    active?: boolean;
    buttonAutoMinWidth?: boolean;
}

export interface IRadioInputAsButtonClasses {
    root: string;
    items: string;
    item: string;
    label: string;
    input: string;
    labelWrap?: string;
    leftTab?: string;
    rightTab?: string;
    iconWrap?: string;
    buttonAutoMinWidth?: string;
}

interface IRadioInputAsButtonInGroup extends IBaseRadioProps, IRadioGroupProps {}

/**
 * Implement what looks like buttons, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
export function RadioInputAsButton(props: IRadioInputAsButtonInGroup) {
    const { data, icon, buttonAutoMinWidth = false } = props;
    const activeItem = props["activeItem"];
    const classes = props["classes"] || { item: null, input: null, label: null };

    const buttonAutoMinWidthClass = classes.buttonAutoMinWidth || "";

    const onClick = (event) => {
        props.setData(props.data);
    };

    const handleOnChange = (event) => {
        return;
    };

    const onKeyDown = (event) => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                props.setData(props.data);
                break;
        }
    };

    const active = props.active ?? (activeItem !== undefined ? activeItem === data : false);
    const disabled = props.disabled || props.isLoading;

    return (
        <label className={classNames(props.className, classes.item)}>
            <input
                className={classNames(visibility().visuallyHidden, classes.input)}
                type="radio"
                onClick={onClick}
                onKeyDown={onKeyDown}
                onChange={handleOnChange}
                checked={active}
                name={props.groupID}
                value={props.label}
                disabled={disabled}
            />
            <span
                className={classNames(
                    { isDisabled: props.disabled || props.isLoading },
                    classes.label,
                    active ? classNames(props["buttonActiveClass"], "isActive") : props["buttonClass"],
                    { [buttonAutoMinWidthClass]: buttonAutoMinWidth },
                )}
            >
                {props.isLoading ? (
                    <ButtonLoader />
                ) : (
                    <>
                        {icon && (
                            <span aria-hidden={true} className={classes["iconWrap"] ?? undefined}>
                                {icon}
                            </span>
                        )}
                        <ConditionalWrap condition={!!icon} className={classes["labelWrap"]}>
                            {props.label}
                        </ConditionalWrap>
                    </>
                )}
            </span>
            {active && (
                <LiveMessage
                    clearOnUnmount={true}
                    message={sprintf('Search for: "%s"', props.label)}
                    aria-live="polite"
                />
            )}
        </label>
    );
}

export default withRadioGroup<IRadioInputAsButtonInGroup>(RadioInputAsButton);

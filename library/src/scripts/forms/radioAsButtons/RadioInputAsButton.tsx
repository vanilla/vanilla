/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { IRadioGroupProps, withRadioGroup } from "@library/forms/radioAsButtons/RadioGroupContext";
import {
    IRadioInputAsButtonClasses,
    radioInputAsButtonClasses,
} from "@library/forms/radioAsButtons/radioInputAsButtons.styles";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpersVisibility";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { buttonClasses } from "@library/forms/buttonStyles";

export interface IBaseRadioProps {
    label: string;
    data: string | number;
    className?: string;
    classes?: IRadioInputAsButtonClasses;
    disabled?: boolean;
    isLoading?: boolean;
    icon?: JSX.Element;
    active?: boolean;
}

interface IRadioInputAsButtonInGroup extends IBaseRadioProps, IRadioGroupProps {}

/**
 * Implement what looks like buttons, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
export function RadioInputAsButton(props: Omit<IRadioInputAsButtonInGroup, "classes">) {
    const { data, icon = null } = props;
    const activeItem = props["activeItem"];
    const classes = radioInputAsButtonClasses();
    const classesButtons = buttonClasses();
    const onClick = event => {
        props.setData(props.data);
    };

    const handleOnChange = event => {
        return;
    };

    const onKeyDown = event => {
        switch (event.key) {
            case "Enter":
            case "Spacebar":
            case " ":
                props.setData(props.data);
                break;
        }
    };

    const active = props.active ?? (activeItem !== undefined ? activeItem === data : false);

    return (
        <label className={classNames(props.className, classes.item)}>
            <input
                className={classNames(visibility().srOnly, classes.input)}
                type="radio"
                onClick={onClick}
                onKeyDown={onKeyDown}
                onChange={handleOnChange}
                checked={active}
                name={props.groupID}
                value={props.label}
                disabled={props.disabled}
            />
            <span
                className={classNames(
                    { isDisabled: props.disabled },
                    classes.label,
                    active ? props["buttonActiveClass"] : props["buttonClass"],
                )}
            >
                {props.isLoading ? (
                    <ButtonLoader />
                ) : (
                    <>
                        {icon}
                        {props.label}
                    </>
                )}
            </span>
        </label>
    );
}

export default withRadioGroup<IRadioInputAsButtonInGroup>(RadioInputAsButton);

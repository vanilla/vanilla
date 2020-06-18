/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { ITabContext, withTabs } from "@library/contexts/TabContext";
import { radioTabClasses } from "@library/forms/radioTabs/radioTabStyles";
import { IRadioTabClasses } from "@library/forms/radioTabs/RadioTabs";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { IRadioGroupProps } from "@library/forms/radioAsButtons/RadioGroupContext";
import { IRadioInputAsButtonClasses } from "@library/forms/radioAsButtons/radioInputAsButtonsStyles";
import { visibility } from "@library/styles/styleHelpersVisibility";

export interface IBaseTabProps {
    label: string;
    data: string | number;
    className?: string;
    position?: "left" | "right";
    classes?: IRadioTabClasses | IRadioInputAsButtonClasses;
    customTabActiveClass?: string;
    customTabInactiveClass?: string;
    disabled?: boolean;
    isLoading?: boolean;
    icon?: JSX.Element;
    active: boolean;
    labelClasses?: string;
}

export interface ITabProps extends IBaseTabProps, ITabContext {}
export interface IButtonProps extends IBaseTabProps, IRadioGroupProps {}

/**
 * Implement what looks like a tab, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
function RadioAsButton(props: ITabProps | IButtonProps) {
    const classes = props.classes ?? radioTabClasses();
    const { icon = null, active, labelClasses } = props;

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
                    {
                        "radioButtonsAsTabs-label": !props.customTabActiveClass && !props.customTabInactiveClass,
                        [`${props.customTabActiveClass}`]: props.customTabActiveClass && active,
                        [`${props.customTabInactiveClass}`]: props.customTabInactiveClass && !active,
                    },
                    labelClasses,
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

export default withTabs<ITabProps>(RadioAsButton);

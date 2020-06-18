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
import { IRadioGroupProps, withRadioGroup } from "@library/forms/radioAsButtons/RadioGroupContext";

export interface IBaseTabProps {
    label: string;
    data: string | number;
    className?: string;
    position?: "left" | "right";
    classes?: IRadioTabClasses;
    customTabActiveClass?: string;
    customTabInactiveClass?: string;
    disabled?: boolean;
    isLoading?: boolean;
    icon?: JSX.Element;
    active: boolean;
}

export interface ITabProps extends IBaseTabProps, ITabContext {}

/**
 * Implement what looks like a tab, but what is semantically radio button. To be used in the RadioButtonsAsTabs component
 */
function RadioAsButton(props: ITabProps | IRadioGroupProps) {
    const classes = props.classes ?? radioTabClasses();
    const { icon = null, active } = props;

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
        <label
            className={classNames("radioButtonsAsTabs-tab", props["childClass"] ?? false, props.className, classes.tab)}
        >
            <input
                className={classNames("radioButtonsAsTabs-input", "sr-only", classes.input)}
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
                    props.position === "left" ? classes.leftTab : undefined,
                    props.position === "right" ? classes.rightTab : undefined,
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
export const RadioAsButtonWithGroup = withRadioGroup<IRadioGroupProps>(RadioAsButton);

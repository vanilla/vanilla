/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { slugify } from "@vanilla/utils";
import classNames from "classnames";
import React, { useCallback, useState } from "react";
import { inputClasses } from "@library/forms/inputStyles";

export enum InputStyle {
    DASHBOARD = "dashboard",
    MODERN = "modern",
}

export enum InputValidationFilter {
    SLUG = "slug",
    NUMBER = "number",
}

interface IProps extends React.InputHTMLAttributes<HTMLInputElement> {
    inputStyle?: InputStyle; // Optionally a style for the input.
    value?: string; // A value if you want a controlled component. Be sure to use onChange too.
    validationFilter?: InputValidationFilter; // A live filter to apply.
    type?: never; // The type is controlled by the component and validationFilter.
    inputRef?: React.RefObject<HTMLInputElement>;
}

/**
 * Text input component.
 *
 * Can optionaly take validation filters that will live filter the input.
 */
export function TextInput(props: IProps) {
    const { inputStyle, validationFilter, className, onChange, value, defaultValue, inputRef, ...rest } = props;
    const [ownValue, setOwnValue] = useState(defaultValue);

    // Get our change handler together.
    // This will either be a self contained, controlled component (Eg. {@see mountInputs()}).
    // Or this should be fully controlled by the outer component (takes value and onChange).
    let actualValue = ownValue;
    let actualOnChange: React.ChangeEventHandler<HTMLInputElement> | undefined = useCallback(
        event => {
            const filterMethod = getFilterMethodForMode(validationFilter);
            setOwnValue(filterMethod(event.target.value));
        },
        [setOwnValue, validationFilter],
    );
    if ("value" in props) {
        actualValue = props.value;
        actualOnChange = props.onChange;
    }

    // Input type is determined by strictly by the validation filter.
    const inputType = getInputType(validationFilter);
    const classes = classNames(
        className,
        props.inputStyle === InputStyle.DASHBOARD ? "form-control" : inputClasses().text,
    );

    return (
        <input
            {...rest}
            ref={inputRef}
            type={inputType}
            onChange={actualOnChange}
            value={actualValue}
            className={classes}
        />
    );
}

function getInputType(filterMode?: InputValidationFilter): string {
    switch (filterMode) {
        case InputValidationFilter.NUMBER:
            return "number";
        default:
            return "text";
    }
}

type FilterValidator = (value: string) => string;
/**
 * Get a filtering function from react.
 *
 * @param filterMode
 */
function getFilterMethodForMode(filterMode?: InputValidationFilter): FilterValidator {
    switch (filterMode) {
        case InputValidationFilter.NUMBER:
            return (value: string) => value.replace(/[^0-9]/g, "");
        case InputValidationFilter.SLUG:
            return (value: string) => slugify(value, { allowMultipleDashes: true });
        default:
            return (value: string) => value;
    }
}

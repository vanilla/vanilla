/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import classNames from "classnames";
import SelectOne, { IMenuPlacement } from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useField } from "formik";

import InputHidden from "@library/forms/themeEditor/InputHidden";
import { IFieldError } from "@library/@types/api/core";
import Select from "react-select";

interface IProps extends IMenuPlacement {
    variableID: string; // If it exists, it will behave like a regular input. If not, the value(s) need to be handled manually with hidden input type.
    inputID: string;
    labelID: string;
    options: IComboBoxOption[];
    value?: IComboBoxOption;
    inputClassName?: string;
    disabled?: boolean;
    isClearable?: boolean;
    errors?: IFieldError[];
    selectRef?: React.RefObject<Select>;
    defaultValue?: string;
    selectedIndex?: number;
}

export function ThemeDropDown(props: IProps) {
    const [value, valueMeta, valueHelpers] = useField(props.variableID);

    let defaultValue;

    if (
        props.selectedIndex !== undefined &&
        Number.isInteger(props.selectedIndex) &&
        props.selectedIndex <= props.options.length
    ) {
        defaultValue = props.options[props.selectedIndex];
    } else if (!value.value && props.options && props.options.length > 0 && props.defaultValue) {
        props.options.forEach(option => {
            if (!defaultValue && option.value === props.defaultValue) {
                defaultValue = option;
            }
        });
    }

    const [currentOption, setCurrentOption] = useState(defaultValue);

    const onChange = (option: IComboBoxOption | undefined) => {
        const newValue = option ? option.value.toString() : undefined;
        valueHelpers.setValue(newValue);
        setCurrentOption(option as any);
    };

    return (
        <div className={classNames("input-wrap-right")}>
            <SelectOne
                label={null}
                labelID={props.labelID}
                inputID={props.inputID}
                options={props.options}
                value={currentOption as any}
                inputClassName={classNames("form-control", props.inputClassName)}
                disabled={props.disabled ?? props.options.length === 1}
                menuPlacement={props.menuPlacement}
                isClearable={props.isClearable ?? false}
                selectRef={props.selectRef}
                onChange={onChange}
            />
            <InputHidden
                variableID={props.variableID}
                value={currentOption && currentOption.value ? currentOption.value : undefined}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
}

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import classNames from "classnames";
import SelectOne from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useField } from "formik";
import { IInputDropDownPart } from "@library/forms/themeEditor/pieces/InputDropDownPart";
import InputHidden from "@library/forms/themeEditor/InputHidden";

export interface IInputDropDown extends Omit<IInputDropDownPart, "onChange"> {
    variableID: string;
    defaultValue?: string;
}

export const InputDropDown: React.FC<IInputDropDown> = (props: IInputDropDown) => {
    const [value, valueMeta, valueHelpers] = useField(props.variableID);

    let defaultValue;
    if (!value.value && props.options && props.options.length > 0 && props.defaultValue) {
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
                disabled={props.disabled}
                menuPlacement={props.menuPlacement}
                isClearable={props.isClearable}
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
};

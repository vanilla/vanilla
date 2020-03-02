/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import SelectOne from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useField } from "formik";
import { IInputDropDownPart } from "@library/forms/themeEditor/pieces/InputDropDownPart";

export interface IInputDropDown extends Omit<IInputDropDownPart, "onChange"> {
    variableID: string;
    defaultValue?: string;
}

export const InputDropDown: React.FC<IInputDropDown> = (props: IInputDropDown) => {
    const [value, valueMeta, valueHelpers] = useField(props.variableID);

    const onChange = (option: IComboBoxOption | undefined) => {
        const newValue = option ? option.value.toString() : undefined;
        valueHelpers.setValue(newValue);
    };

    let currentValue;

    props.options.forEach(option => {
        if (!currentValue) {
            if (option.value === props.defaultValue) {
                currentValue = option;
            }
        }
    });

    return (
        <div className={classNames("input-wrap-right")}>
            <SelectOne
                label={null}
                labelID={props.labelID}
                inputID={props.inputID}
                options={props.options}
                value={currentValue}
                inputClassName={classNames("form-control", props.inputClassName)}
                disabled={props.disabled}
                menuPlacement={props.menuPlacement}
                isClearable={props.isClearable}
                selectRef={props.selectRef}
                onChange={onChange}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
};

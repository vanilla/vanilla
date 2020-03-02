/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import classNames from "classnames";
import SelectOne, { IMenuPlacement } from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { IFieldError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import Select from "react-select";

export interface IInputDropDownPart extends IMenuPlacement {
    variableID?: string; // If it exists, it will behave like a regular input. If not, the value(s) need to be handled manually with hidden input type.
    inputID: string;
    labelID: string;
    options: IComboBoxOption[];
    onChange: (newValue: IComboBoxOption | null) => void;
    value?: IComboBoxOption;
    inputClassName?: string;
    disabled?: boolean;
    isClearable?: boolean;
    errors?: IFieldError[];
    selectRef?: React.RefObject<Select>;
}

// This component is meant to be extended, because it may or may not be using formik directly.
export const InputDropDownPart: React.FC<IInputDropDownPart> = (props: IInputDropDownPart) => {
    return (
        <div className={classNames("input-wrap-right")}>
            <SelectOne
                label={null}
                labelID={props.labelID}
                inputID={props.inputID}
                options={props.options}
                value={props.value}
                onChange={props.onChange}
                inputClassName={classNames("form-control", props.inputClassName)}
                disabled={props.disabled}
                menuPlacement={props.menuPlacement}
                isClearable={props.isClearable}
                selectRef={props.selectRef}
            />
            {props.errors && <ErrorMessages errors={props.errors} />}
        </div>
    );
};

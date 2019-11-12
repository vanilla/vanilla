/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import SelectOne, { IMenuPlacement } from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";

interface IProps extends IMenuPlacement {
    options: IComboBoxOption[];
    onChange: (newValue: IComboBoxOption | null) => void;
    value?: IComboBoxOption;
    clas?: string;
    inputClassName?: string;
    disabled?: boolean;
}

export const DashboardSelect: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType, labelID } = useFormGroup();
    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";
    return (
        <div className={classNames(rootClass)}>
            <SelectOne
                label={null}
                labelID={labelID}
                inputID={inputID}
                options={props.options}
                value={props.value}
                onChange={props.onChange}
                inputClassName={classNames("form-control", props.inputClassName)}
                disabled={props.disabled}
                menuPlacement={props.menuPlacement}
            />
        </div>
    );
};

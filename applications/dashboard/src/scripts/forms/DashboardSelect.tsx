/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroup";
import classNames from "classnames";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import SelectOne from "@library/forms/select/SelectOne";
import { IComboBoxOption } from "@library/features/search/SearchBar";

interface IProps {
    options: IComboBoxOption[];
    onChange: (newValue: IComboBoxOption) => void;
    value?: IComboBoxOption;
    className?: string;
    disabled?: boolean;
}

export const DashboardSelect: React.FC<IProps> = (props: IProps) => {
    const { inputID, labelType, labelID } = useFormGroup();
    const classes = classNames("form-control", props.className);

    const rootClass = labelType === DashboardLabelType.WIDE ? "input-wrap-right" : "input-wrap";

    return (
        <div className={rootClass}>
            <SelectOne
                label={null}
                labelID={labelID}
                inputID={inputID}
                options={props.options}
                value={props.value}
                onChange={props.onChange}
                inputClassName={classes}
                disabled={props.disabled}
            />
        </div>
    );
};

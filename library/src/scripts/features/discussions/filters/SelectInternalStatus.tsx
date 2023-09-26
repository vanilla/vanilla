/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useStatusOptions } from "@library/features/discussions/filters/discussionListFilterHooks";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import CheckBox from "@library/forms/Checkbox";
import { useConfigsByKeys } from "@library/config/configHooks";

export interface ISelectInternalProps {
    value: number[];
    onChange: (newValue: number[]) => void;
    label?: string;
}

export function SelectInternalStatus(props: ISelectInternalProps) {
    const { value, onChange, label } = props;
    const options = useStatusOptions(true) as IComboBoxOption[];
    const resolvedEnabled = useConfigsByKeys(["plugins.resolved"]);

    const handleChange = (data, evt) => {
        const {
            currentTarget: { checked },
        } = evt;

        let tmpValue = [...value];
        const optionValues = options.map((opt) => opt.value as number);

        if (tmpValue.length === 0 && !checked) {
            tmpValue = optionValues.filter((val) => val !== data.value);
        } else if (tmpValue.length === 1 && checked) {
            tmpValue = [];
        }

        onChange(tmpValue);
    };

    if (options.length === 0 || !resolvedEnabled) {
        return null;
    }

    return (
        <CheckboxGroup legend={label}>
            {options.map((opt) => (
                <CheckBox
                    key={opt.value}
                    label={opt.label}
                    onChange={(evt) => handleChange(opt, evt)}
                    checked={value.length === 0 || value.indexOf(opt.value as number) > -1}
                />
            ))}
        </CheckboxGroup>
    );
}

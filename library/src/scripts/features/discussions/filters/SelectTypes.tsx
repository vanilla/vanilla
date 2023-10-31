/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { Tokens } from "@library/forms/select/Tokens";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useTypeOptions } from "@library/features/discussions/filters/discussionListFilterHooks";

export interface ISelectTypesProps {
    value: string[];
    onChange: (newValue: string[]) => void;
    label?: string;
    inModal?: boolean;
}

export function SelectTypes(props: ISelectTypesProps) {
    const { value: valueFromProps, onChange, label, inModal } = props;
    const options = useTypeOptions();

    const value = useMemo<IComboBoxOption[]>(() => {
        return valueFromProps
            .map((val) => options.find((opt) => opt.value === val))
            .filter((opt) => Boolean(opt)) as IComboBoxOption[];
    }, [valueFromProps, options]);

    const handleChange = (newValue: IComboBoxOption[] = []) => {
        const valueArray = newValue.map((opt) => opt.value as string);
        onChange(valueArray);
    };

    if (options.length === 1) {
        return null;
    }

    return (
        <Tokens value={value} label={label} inModal={inModal} onChange={handleChange} options={options} showIndicator />
    );
}

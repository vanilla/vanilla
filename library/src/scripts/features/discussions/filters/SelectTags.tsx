/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forum Inc
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { Tokens } from "@library/forms/select/Tokens";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useTagOptions } from "@library/features/discussions/filters/discussionListFilterHooks";

export interface ISelectTagsProps {
    value: string;
    onChange: (newValue: string) => void;
    label?: string;
    inModal?: boolean;
}

export function SelectTags(props: ISelectTagsProps) {
    const { value: valueFromProps, onChange, label, inModal } = props;
    const options = useTagOptions();

    const value = useMemo<IComboBoxOption[]>(() => {
        return valueFromProps
            .split(",")
            .map((val) => {
                return options.find((opt) => opt.value === val);
            })
            .filter((opt) => Boolean(opt)) as IComboBoxOption[];
    }, [valueFromProps, options]);

    const handleChange = (newValue: IComboBoxOption[] = []) => {
        const valueArray = newValue.map((opt) => opt.value);
        onChange(valueArray.join(","));
    };

    return (
        <Tokens value={value} label={label} inModal={inModal} onChange={handleChange} options={options} showIndicator />
    );
}

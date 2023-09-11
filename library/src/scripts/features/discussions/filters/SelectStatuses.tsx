/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { Tokens } from "@library/forms/select/Tokens";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useStatusOptions } from "@library/features/discussions/filters/discussionListFilterHooks";
import flatten from "lodash/flatten";
import { IGroupOption } from "@library/forms/select/Tokens.loadable";

export interface ISelectStatusesProps {
    value: number[];
    onChange: (newValue: number[]) => void;
    label?: string;
    inModal?: boolean;
    types?: string[];
}

export function SelectStatuses(props: ISelectStatusesProps) {
    const { value: valueFromProps, onChange, label, inModal, types = [] } = props;
    const groups = useStatusOptions();

    const options = useMemo<IGroupOption[]>(() => {
        let tmpGroups: IGroupOption[] = [...(groups as IGroupOption[])];

        if (types.length > 0) {
            tmpGroups = tmpGroups.filter(({ label }) => {
                if (label === "Q & A" && types.includes("Question")) {
                    return true;
                }
                if (label === "Ideas" && types.includes("Idea")) {
                    return true;
                }
                return false;
            });
        }

        return tmpGroups;
    }, [groups, types]);

    const value = useMemo<IComboBoxOption[]>(() => {
        if (options) {
            const flatOptions = flatten(options.map((group) => group.options));

            return valueFromProps
                .map((val) => flatOptions.find((opt) => opt.value === val))
                .filter((opt) => Boolean(opt)) as IComboBoxOption[];
        }
        return [];
    }, [valueFromProps, options]);

    const handleChange = (newValue: IComboBoxOption[]) => {
        const valueArray = newValue.map(({ value }) => parseInt(value as string));
        onChange(valueArray);
    };

    if (options.length === 0) {
        return null;
    }

    return (
        <Tokens value={value} options={options} onChange={handleChange} label={label} inModal={inModal} showIndicator />
    );
}

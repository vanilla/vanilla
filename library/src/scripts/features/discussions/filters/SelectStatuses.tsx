/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { Tokens } from "@library/forms/select/Tokens";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { useStatusOptions } from "@library/features/discussions/filters/discussionListFilterHooks";
import flatten from "lodash-es/flatten";
import { IGroupOption } from "@library/forms/select/Tokens.loadable";
import { useIsInModal } from "@library/modal/Modal.context";
import { t } from "@vanilla/i18n";

export interface ISelectStatusesProps {
    value: number[];
    onChange: (newValue: number[]) => void;
    label?: string;
    types?: string[];
}

export function SelectStatuses(props: ISelectStatusesProps) {
    const isInModal = useIsInModal();
    const { value: valueFromProps, onChange, label, types = [] } = props;
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

        // Workaround fix for screen readers, can remove after we upgrade react-select
        return tmpGroups.map((group) => {
            if (group?.label === "Q & A") {
                return {
                    ...group,
                    options: group?.options?.map((option) => ({ ...option, label: `${t("Q & A")}: ${option.label}` })),
                };
            }

            if (group?.label === "Ideas") {
                return {
                    ...group,
                    options: group?.options?.map((option) => ({ ...option, label: `${t("Ideas")}: ${option.label}` })),
                };
            }

            return group;
        });
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

    const formatOptionLabel: React.ComponentProps<typeof Tokens>["formatOptionLabel"] = ({ label }) => {
        const formattedLabel = label.startsWith(`${t("Q & A")}: `)
            ? label.replace(`${t("Q & A")}: `, "")
            : label.startsWith(`${t("Ideas")}: `)
            ? label.replace(`${t("Ideas")}: `, "")
            : label;

        return (
            <>
                <span className="sr-only">{label}</span>
                {formattedLabel}
            </>
        );
    };

    return (
        <Tokens
            value={value}
            options={options}
            onChange={handleChange}
            label={label}
            inModal={isInModal}
            showIndicator
            formatOptionLabel={formatOptionLabel}
        />
    );
}

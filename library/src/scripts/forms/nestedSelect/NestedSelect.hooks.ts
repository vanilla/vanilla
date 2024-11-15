/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INestedOptionsState, INestedSelectOptionProps } from "@library/forms/nestedSelect";
import { t } from "@library/utility/appUtils";
import { useQuery } from "@tanstack/react-query";
import get from "lodash-es/get";
import { useCallback, useMemo } from "react";
import type { Select } from "@vanilla/json-schema-forms";
import { useApiContext } from "@vanilla/ui";
import { RecordID } from "@vanilla/utils";

// Get the options to display in the dropdown
export function useNestedOptions(params: {
    searchQuery?: string;
    options?: Select.Option[];
    optionsLookup?: Select.LookupApi;
    createable?: boolean;
    createdOptions?: Select.Option[];
    initialValues?: RecordID | RecordID[];
}): INestedOptionsState & { isSuccess?: boolean } {
    const { searchQuery = "", options, optionsLookup, createable = false, createdOptions } = params;

    const fetchOptions = useFetchOptions();
    const { data, isSuccess } = useQuery({
        queryKey: ["nested-options-lookup", optionsLookup, searchQuery],
        queryFn: fetchOptions,
        enabled: Boolean(optionsLookup),
    });

    const optionsState = useMemo<INestedOptionsState>(() => {
        const processedOptions =
            options && optionsLookup?.processOptions ? optionsLookup.processOptions(options) : options ?? [];
        const processedData = data ?? [];
        const initialOptions = [...processedOptions, ...processedData, ...(createdOptions ?? [])];
        return getOptionsState(initialOptions, searchQuery, createable);
    }, [options, data, searchQuery, createable, createdOptions]);

    return {
        ...optionsState,
        ...(optionsLookup && { isSuccess }),
    };
}

// Fetch the nested options from an API lookup and transform into a nested options tree
export function useFetchOptions() {
    const api = useApiContext();

    const fetchOptions = useCallback(
        async function fetchOptions({ queryKey }): Promise<Select.Option[]> {
            const [_, lookup, query]: [never, Select.LookupApi, string] = queryKey;
            const notSearching = query === "";

            if (notSearching && lookup.initialOptions?.length) {
                return lookup.initialOptions;
            }

            const apiUrl = query
                ? lookup.searchUrl.replace("%s", query)
                : lookup.defaultListUrl ?? lookup.searchUrl.replace("%s", "");
            const response = await api.get(apiUrl);
            const rawData: any[] = lookup.resultsKey ? get(response.data, lookup.resultsKey, []) : response.data;

            let options: Select.Option[] = rawData.map((data) => {
                const label = get(data, lookup.labelKey, t("Untitled"));
                const value = lookup.valueKey ? get(data, lookup.valueKey, label) : label;
                const extraLabel = lookup.extraLabelKey ? get(data, lookup.extraLabelKey) : undefined;
                return {
                    value,
                    label,
                    extraLabel,
                    data,
                };
            });

            if (lookup.excludeLookups) {
                options = options.filter(({ value }) => value && !lookup.excludeLookups?.includes(value));
            }

            if (lookup.processOptions) {
                options = lookup.processOptions(options);
            }

            return options;
        },
        [api],
    );
    return fetchOptions;
}

// Flatten the nested options and return it along with options by value and group
function getOptionsState(
    initialOptions: Select.Option[] = [],
    searchQuery?: string,
    createable?: boolean,
): INestedOptionsState {
    const options = flattenOptions(initialOptions);

    if (
        createable &&
        searchQuery &&
        searchQuery.trim().length > 0 &&
        !options.find(
            (opt) => opt.value?.toString()?.trim() === searchQuery.trim() || opt.label.trim() === searchQuery.trim(),
        )
    ) {
        options.push({ label: searchQuery, value: searchQuery, data: { createable: true } });
    }

    const optionsByValue: INestedOptionsState["optionsByValue"] = {};
    const optionsByGroup: INestedOptionsState["optionsByGroup"] = {};

    options.forEach((option) => {
        if (option.value !== undefined) {
            optionsByValue[option.value] = option;
        }

        if (option.group) {
            if (!optionsByGroup[option.group]) {
                optionsByGroup[option.group] = [];
            }
            optionsByGroup[option.group].push(option);
        }
    });

    // When searching, return only clickable options that match as a simple list
    if (searchQuery) {
        const filteredOptions = options.filter(
            ({ label, isHeader }) => getSearchMatch(label, searchQuery).isMatch && !isHeader,
        );
        return getOptionsState(filteredOptions);
    }

    return {
        options,
        optionsByValue,
        optionsByGroup,
    };
}

export function flattenOptions(
    initialOptions: Select.Option[],
    params: { group?: string; depth?: number } = {},
): INestedSelectOptionProps[] {
    let options: INestedSelectOptionProps[] = [];

    initialOptions.forEach(({ children, ...item }) => {
        const isHeader = item.value === undefined;
        const option: INestedSelectOptionProps = {
            ...item,
            isHeader,
            group: params.group,
            depth: params.depth ?? 0,
        };
        if (!option.tooltip && params.group) {
            option.tooltip = normalizeGroupList(params.group).join(" > ");
        }

        options.push(option);

        if (children?.length) {
            let groupTree: string[] = [];
            if (params.group) {
                groupTree = normalizeGroupList(params.group);
            }
            groupTree.push(item.label);
            const group = groupTree.map((item) => `[${item}]`).join(">");

            const depth = groupTree.length === 1 && isHeader ? 0 : option.depth! + 1;

            options = options.concat(flattenOptions(children, { group, depth }));
        }
    });

    return options;
}

function normalizeGroupList(value: string) {
    const regex = /(\[)(.*)(\])/;
    return value.split(">").map((item) => item.replace(regex, (m, p1, p2) => p2));
}

// Highlight the matched search query string in the label
export function getSearchMatch(value: string, query: string) {
    const regex = new RegExp(`(.*)(${query})(.*)`, "gi");
    const match = value.match(regex);
    let parts: string[] = [value];

    if (!match) {
        return {
            isMatch: false,
            parts,
        };
    }

    try {
        parts = JSON.parse(value.replace(regex, (_, pt1, pt2, pt3) => JSON.stringify([pt1, pt2, pt3])));
        return {
            isMatch: true,
            parts,
        };
    } catch (err) {
        return {
            isMatch: false,
            parts,
        };
    }
}

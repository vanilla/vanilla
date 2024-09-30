/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import {
    INestedLookupApi,
    INestedOptionsState,
    INestedSelectOption,
    INestedSelectOptionProps,
} from "@library/forms/nestedSelect";
import { t } from "@library/utility/appUtils";
import { useQuery } from "@tanstack/react-query";
import get from "lodash-es/get";
import { useMemo } from "react";

// Get the options to display in the dropdown
export function useNestedOptions(params: {
    searchQuery?: string;
    options?: INestedSelectOption[];
    optionsLookup?: INestedLookupApi;
}): INestedOptionsState & { isSuccess?: boolean } {
    const { searchQuery = "", options, optionsLookup } = params;

    const { data, isSuccess } = useQuery({
        queryKey: ["nested-options-lookup", optionsLookup, searchQuery],
        queryFn: fetchOptions,
        enabled: Boolean(optionsLookup),
    });

    const optionsState = useMemo<INestedOptionsState>(() => {
        const initialOptions = options ?? data ?? [];
        return getOptionsState(initialOptions, searchQuery);
    }, [options, data, searchQuery]);

    return {
        ...optionsState,
        ...(optionsLookup && { isSuccess }),
    };
}

// Fetch the nested options from an API lookup and transform into a nested options tree
async function fetchOptions({ queryKey }): Promise<INestedSelectOption[]> {
    const [_, lookup, query]: [never, INestedLookupApi, string] = queryKey;
    const notSearching = query === "";

    if (notSearching && lookup.initialOptions?.length) {
        return lookup.initialOptions;
    }

    const apiUrl = query
        ? lookup.searchUrl.replace("%s", query)
        : lookup.defaultListUrl ?? lookup.searchUrl.replace("%s", "");
    const response = await apiv2.get(apiUrl);
    const rawData: any[] = lookup.resultsKey ? get(response.data, lookup.resultsKey, []) : response.data;

    let options: INestedSelectOption[] = rawData.map((data) => {
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
}

// Flatten the nested options and return it along with options by value and group
function getOptionsState(initialOptions: INestedSelectOption[] = [], searchQuery?: string): INestedOptionsState {
    const options = flattenOptions(initialOptions);
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
    initialOptions: INestedSelectOption[],
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

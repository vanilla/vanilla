/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INestedSelectProps, NestedSelect } from "@library/forms/nestedSelect";
import { Select } from "@vanilla/json-schema-forms";
import get from "lodash-es/get";
import set from "lodash-es/set";
import { useState } from "react";

interface IProps extends Omit<INestedSelectProps, "options" | "optionsLookup"> {}

export function CategoryDropdown(_props: IProps) {
    const { prefix = "categories", onInputChange, ...props } = _props;
    const [inputValue, setInputValue] = useState<string>(props.inputValue ?? "");

    const optionsLookup: Select.LookupApi = {
        searchUrl: "/categories/search?query=%s",
        singleUrl: "/categories/%s",
        defaultListUrl: "/categories?outputFormat=flat&limit=50",
        labelKey: "name",
        valueKey: "categoryID",
        processOptions: inputValue.length ? getFilteredNestedOptions : getDefaultNestedOptions,
    };

    const handleInputChange: INestedSelectProps["onInputChange"] = (newValue = "") => {
        setInputValue(newValue);
        onInputChange?.(newValue);
    };

    return (
        <NestedSelect {...props} prefix="categories" optionsLookup={optionsLookup} onInputChange={handleInputChange} />
    );
}

// Transform the default list from a flat format into a nested format without worrying about guessing depth
function getDefaultNestedOptions(initialOptions: Select.Option[]): Select.Option[] {
    const mapping: Record<string, any> = {};

    initialOptions
        .map(({ value, ...option }) => {
            if (option.data.displayAs === "discussions") {
                return {
                    ...option,
                    value,
                    isHeader: false,
                };
            }
            return {
                ...option,
                isHeader: true,
            };
        })
        .forEach((option) => {
            const {
                data: { parentCategoryID, categoryID },
            } = option;
            set(mapping, categoryID, option);
            if (parentCategoryID) {
                const parent = get(mapping, parentCategoryID, {
                    label: "",
                    children: {},
                });
                set(parent, `children.${categoryID}`, option);
                set(mapping, parentCategoryID, parent);
            }
        });

    const options = Object.values(mapping)
        .filter(({ data }) => !data.parentCategoryID)
        .sort(sortList)
        .map(transformChildren);

    return options;
}

// Transform the filtered categories into a nested options list using the category's breadcrumbs
function getFilteredNestedOptions(initialOptions: Select.Option[]): Select.Option[] {
    const mapping: Record<string, Select.Option> = {};

    initialOptions.forEach((option) => {
        const {
            data: { breadcrumbs },
        } = option;
        const group = breadcrumbs.slice(1, breadcrumbs.length).map(({ name }) => name);
        set(mapping, `${group.join(".")}.option`, option);
    });

    const options = Object.entries(mapping).map(getLabelFromKey);

    return options;
}

// The default list should be sorted in the order they are set in the dashboard
function sortList(a, b) {
    if (a.data.sort > b.data.sort) return 1;
    if (a.data.sort < b.data.sort) return -1;
    return 0;
}

// Convert the mapped children into arrays
function transformChildren(option: Select.Option): Select.Option {
    return {
        ...option,
        children: option.children ? Object.values(option.children).sort(sortList).map(transformChildren) : undefined,
    };
}

// Filtered list is getting it's nesting from the breadcrumbs that need to be turned into label properties
function getLabelFromKey(entry: [string, any]): Select.Option {
    const [key, value] = entry;
    return {
        label: value.option?.label ?? key,
        ...(value.option ?? {
            children: Object.entries(value).map(getLabelFromKey),
        }),
    };
}

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import apiv2 from "@library/apiv2";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne, { ISelectOneProps } from "@library/forms/select/SelectOne";
import { t } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";
import debounce from "lodash/debounce";
import get from "lodash/get";
import React, { useCallback, useDebugValue, useEffect, useRef, useState } from "react";

export interface ILookupApi {
    searchUrl: string;
    singleUrl: string;
    valueKey: string;
    labelKey: string;
    excludeLookups?: string[];
    processOptions?: (values: IComboBoxOption[]) => IComboBoxOption[];
}

export interface ISelectLookupProps extends Omit<ISelectOneProps, "options" | "value"> {
    api: ILookupApi;
    value: any;
    onInitialValueLoaded?: (option: IComboBoxOption) => void;
}

/**
 * Form component for searching/selecting a category.
 */
export function SelectLookup(props: ISelectLookupProps) {
    const { value, api } = props;
    const [ownQuery, setQuery] = useState<string | number>("");
    const [initialValue] = useState(value);
    const [options, currentOption] = useApiLookup(api, value, ownQuery, initialValue, props.onInitialValueLoaded);
    const isLoading = (!!initialValue && !currentOption) || options === null;
    return (
        <>
            <SelectOne
                isLoading={isLoading}
                {...props}
                value={currentOption ?? undefined}
                options={options ?? []}
                onInputChange={setQuery}
            />
        </>
    );
}

const apiCaches = new Map<string, any>();

function useApiLookup(
    api: ILookupApi,
    currentValue: string,
    currentInputValue: string | number,
    initialValue: any,
    onInitialValueLoaded?: (option: IComboBoxOption) => void,
): [IComboBoxOption[] | null, IComboBoxOption | null] {
    const [options, setOptions] = useState<IComboBoxOption[] | null>(null);
    const [initialOption, setInitialOption] = useState<IComboBoxOption | null>(null);
    const { searchUrl, singleUrl, labelKey, valueKey, processOptions, excludeLookups } = api;

    useDebugValue({
        options,
        api,
        apiCaches,
    });

    const transformApiToOption = useCallback(
        (result: any): IComboBoxOption => {
            const label = get(result, labelKey, t("(Untitled)"));
            const value = get(result, valueKey, "");
            return {
                label,
                value,
                data: result,
            };
        },
        [labelKey, valueKey],
    );

    // Loading of initial option.
    useEffect(() => {
        if (initialValue && !(excludeLookups ?? []).includes(initialValue)) {
            const actualApiUrl = singleUrl.replace("/api/v2", "").replace("%s", initialValue);
            apiv2.get(actualApiUrl).then((response) => {
                if (response.data) {
                    const option = transformApiToOption(response.data);
                    onInitialValueLoaded?.(option);
                    setInitialOption(option);
                }
            });
        }
    }, []);

    const updateOptions = useCallback(
        debounce((inputValue: string | number) => {
            const actualSearchUrl = searchUrl.replace("/api/v2", "").replace("%s", inputValue.toString());

            const cached = apiCaches.get(actualSearchUrl);
            if (cached) {
                setOptions(cached);
                return;
            }

            // Fetch from API
            apiv2.get(actualSearchUrl).then((response) => {
                const { data } = response;
                let options: IComboBoxOption[] = data.map(transformApiToOption);
                if (processOptions) {
                    options = processOptions(options);
                }
                apiCaches.set(actualSearchUrl, options);
                setOptions(options);
            });
        }, 200),
        [searchUrl, singleUrl, processOptions],
    );

    useEffect(() => {
        updateOptions(currentInputValue);
    }, [updateOptions, currentInputValue]);

    const currentOption =
        [initialOption, ...(options ? options : [])].filter(notEmpty).find((option) => {
            return option.value === currentValue;
        }) ?? null;

    return [options, currentOption];
}

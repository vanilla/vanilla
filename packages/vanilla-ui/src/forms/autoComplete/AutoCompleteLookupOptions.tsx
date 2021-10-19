/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useContext, useDebugValue, useEffect, useMemo, useState } from "react";
import { AxiosInstance } from "axios";
import get from "lodash/get";
import debounce from "lodash/debounce";
import { t } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";
import { AutoCompleteOption, IAutoCompleteOption, IAutoCompleteOptionProps } from "./AutoCompleteOption";
import { IAutoCompleteProps } from "./AutoComplete";
import { AutoCompleteContext } from "./AutoCompleteContext";
import { useApiContext } from "../../ApiContext";

export interface ILookupApi {
    searchUrl: string;
    singleUrl: string;
    valueKey?: string;
    labelKey?: string;
    resultsKey?: string;
    excludeLookups?: string[];
    processOptions?: (options: IAutoCompleteOption[]) => IAutoCompleteOptionProps[];
}

interface IAutoCompleteLookupRenderProps extends Pick<IAutoCompleteProps, "onSearch" | "children" | "value"> {}

interface IAutoCompleteLookupProps {
    lookup: ILookupApi;
    api?: AxiosInstance;
}

/**
 * This is a local cache of query urls and the response results
 */
const apiCaches = new Map<string, any>();

/**
 * This component is used to declaratively configure an API lookup.
 * It will read the input values from the Autocomplete Context and write the appropriate
 * options to the context to be made available for selection.
 *
 * This component does not return any DOM elements.
 */
export function AutoCompleteLookupOptions(props: IAutoCompleteLookupProps) {
    const { lookup } = props;
    const contextApi = useApiContext();
    const api = props.api ?? contextApi;
    const { inputState, value, setOptions, setInputState, multiple } = useContext(AutoCompleteContext);
    const [ownQuery, setQuery] = useState<string | number>(value ?? "");
    const [initialValue] = useState(value);
    const [options, currentOption] = useApiLookup(lookup, api, value, ownQuery, initialValue);
    const isLoading = (!!initialValue && !currentOption) || options === null;

    const displayValue = multiple ? "" : currentOption?.label ?? currentOption?.value ?? "";

    useEffect(() => {
        if (inputState.status !== "suggesting") {
            setInputState({ status: "selected", value: displayValue });
        }
    }, [displayValue, inputState.status]);

    useEffect(() => {
        if (inputState.status === "suggesting") {
            setQuery(inputState.value !== undefined ? inputState.value : "");
        }
        // This handles clearing an input to default the available options back to the initial
        if (inputState.status === "selected" && inputState.value === "") {
            setQuery("");
        }
    }, [inputState]);

    useEffect(() => {
        if (!isLoading && options && setOptions) {
            setOptions(options);
        }
    }, [isLoading, setOptions, options]);

    return null;
}

/**
 * This hook is used to fetch and process search results
 */
function useApiLookup(
    lookup: ILookupApi,
    api: AxiosInstance,
    currentValue: string,
    currentInputValue: string | number,
    initialValue: any,
): [IAutoCompleteOption[] | null, IAutoCompleteOption | null] {
    const [options, setOptions] = useState<IAutoCompleteOption[] | null>(null);
    const [initialOption, setInitialOption] = useState<IAutoCompleteOption | null>(null);
    const {
        searchUrl,
        singleUrl,
        resultsKey = ".",
        labelKey = "name",
        valueKey = "name",
        processOptions,
        excludeLookups,
    } = lookup;

    useDebugValue({
        options,
        api: lookup,
        apiCaches,
    });

    const transformApiToOption = useCallback(
        (result: any): IAutoCompleteOption => {
            const label = String(get(result, labelKey, t("(Untitled)")));
            const value = valueKey === "." ? result : get(result, valueKey, "");
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
            api.get(actualApiUrl).then((response) => {
                if (response.data) {
                    let options = [transformApiToOption(response.data)];
                    if (processOptions) {
                        options = processOptions(options);
                    }
                    apiCaches.set(actualApiUrl, options);
                    setInitialOption(options[0]);
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
            api.get(actualSearchUrl).then((response) => {
                const { data } = response;
                const results = resultsKey === "." ? data : get(data, resultsKey, "[]");
                let options: IAutoCompleteOption[] = results.map(transformApiToOption);
                if (processOptions) {
                    options = processOptions(options);
                }
                apiCaches.set(actualSearchUrl, options);
                setOptions(options);
            });
        }, 200),
        [searchUrl, singleUrl, processOptions],
    );

    // This hook will update the available options whenever the input has changed
    useEffect(() => {
        updateOptions(currentInputValue);
    }, [updateOptions, currentInputValue]);

    const currentOption =
        [initialOption, ...(options ? options : [])].filter(notEmpty).find((option) => {
            return option.value === currentValue;
        }) ?? null;

    return [options, currentOption];
}

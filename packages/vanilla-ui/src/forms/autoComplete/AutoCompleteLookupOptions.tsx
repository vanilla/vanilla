/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useCallback, useContext, useDebugValue, useEffect, useMemo, useState } from "react";
import { AxiosInstance } from "axios";
import get from "lodash/get";
import debounce from "lodash/debounce";
import { t } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";
import { IAutoCompleteOption, IAutoCompleteOptionProps } from "./AutoCompleteOption";
import { AutoCompleteContext } from "./AutoCompleteContext";
import { useApiContext } from "../../ApiContext";
import { useIsMounted } from "@vanilla/react-utils";

export interface ILookupApi {
    searchUrl: string;
    singleUrl: string;
    valueKey?: string;
    labelKey?: string;
    extraLabelKey?: string;
    resultsKey?: string;
    excludeLookups?: string[];
    processOptions?: (options: IAutoCompleteOption[]) => IAutoCompleteOptionProps[];
}

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
 * - No value, when opened will perform an empty lookup
 * - When text is updated, lookup again with that text
 * - When initially loading with a value, perform the single item lookup, not the list.
 *
 * This component does not return any DOM elements.
 */
export function AutoCompleteLookupOptions(props: IAutoCompleteLookupProps) {
    const { lookup } = props;
    const contextApi = useApiContext();
    const api = props.api ?? contextApi;
    const { inputState, value, setOptions, setInputState, multiple } = useContext(AutoCompleteContext);
    const [ownQuery, setQuery] = useState<string | number | Array<string | number>>("");
    const [initialValue] = useState(value);
    const [options, currentOptionOrOptions] = useApiLookup(lookup, api, value ?? "", ownQuery, initialValue);
    const isLoading = (!!initialValue && !currentOptionOrOptions) || options === null;

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
    currentValue: string | number | Array<string | number>,
    currentInputValue: string | number | Array<string | number>,
    initialValue: any,
): [IAutoCompleteOption[] | null, IAutoCompleteOption[] | null] {
    const isMounted = useIsMounted();

    const [options, _setOptions] = useState<IAutoCompleteOption[] | null>(null);
    const [initialOptionsOrOption, _setInitialOptionsOrOption] = useState<
        IAutoCompleteOption | IAutoCompleteOption[] | null
    >(null);

    function setOptions(opts: typeof options) {
        if (!isMounted()) {
            return;
        }
        _setOptions(opts);
    }

    function setInitialOptionsOrOption(opts: typeof initialOptionsOrOption) {
        if (!isMounted()) {
            return;
        }
        _setInitialOptionsOrOption(opts);
    }

    const {
        searchUrl,
        singleUrl,
        resultsKey = ".",
        labelKey = "name",
        extraLabelKey = "name",
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
            const extraLabel = get(result, extraLabelKey) ? String(get(result, extraLabelKey)) : undefined;
            const value = valueKey === "." ? result : get(result, valueKey, "");
            return {
                label,
                extraLabel,
                value,
                data: result,
            };
        },
        [labelKey, extraLabelKey, valueKey],
    );

    // Loading of initial option.
    useEffect(() => {
        if (initialValue && !(excludeLookups ?? []).includes(initialValue)) {
            if ([initialValue].flat().length <= 1) {
                const actualApiUrl = singleUrl.replace("/api/v2", "").replace("%s", initialValue);
                api.get(actualApiUrl).then((response) => {
                    if (!isMounted()) {
                        return;
                    }
                    if (response.data) {
                        let options = [transformApiToOption(response.data)];
                        if (processOptions) {
                            options = processOptions(options);
                        }
                        apiCaches.set(actualApiUrl, options);

                        setInitialOptionsOrOption(options[0]);
                    }
                });
            } else {
                // query api for all options
                const actualSearchUrl = searchUrl.replace("/api/v2", "").replace("%s", ""); //just get the options

                const cached = apiCaches.get(actualSearchUrl);
                if (cached) {
                    setOptions(cached);
                    return;
                }

                // Fetch from API
                api.get(actualSearchUrl).then((response) => {
                    if (!isMounted()) {
                        return;
                    }
                    const { data } = response;
                    const results = resultsKey === "." ? data : get(data, resultsKey, "[]");
                    let options: IAutoCompleteOption[] = results.map(transformApiToOption);
                    if (processOptions) {
                        options = processOptions(options);
                    }
                    apiCaches.set(actualSearchUrl, options);
                    // select the current ones from the response
                    setInitialOptionsOrOption(options.filter(({ value }) => initialValue.includes(value)));
                    // fixme: this may be redundant
                    setOptions(options);
                });
            }
        }
    }, []);

    const updateOptions = useCallback(
        debounce((inputValue: string | number | Array<string | number>) => {
            const actualSearchUrl = searchUrl.replace("/api/v2", "").replace("%s", inputValue.toString());

            const cached = apiCaches.get(actualSearchUrl);
            if (cached) {
                setOptions(cached);
                return;
            }

            // Fetch from API
            api.get(actualSearchUrl).then((response) => {
                if (!isMounted()) {
                    return;
                }
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
        if (isMounted()) {
            updateOptions(currentInputValue);
        }
    }, [updateOptions, currentInputValue]);

    const currentOptionOrOptions = useMemo(() => {
        return (
            [...[initialOptionsOrOption].flat(), ...(options ? options : [])].filter(notEmpty).filter(({ value }) => {
                return Array.isArray(currentValue) ? currentValue.includes(value) : value == currentValue;
            }) ?? null
        );
    }, [currentValue, initialOptionsOrOption, options]);

    return [options, currentOptionOrOptions];
}

/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useCallback, useDebugValue, useEffect, useState } from "react";
import { AxiosInstance } from "axios";
import get from "lodash-es/get";
import debounce from "lodash-es/debounce";
import uniqBy from "lodash-es/uniqBy";
import { t } from "@vanilla/i18n";
import { logError, notEmpty } from "@vanilla/utils";
import { IAutoCompleteOption, IAutoCompleteOptionProps } from "./AutoCompleteOption";
import { useAutoCompleteContext } from "@vanilla/ui/src/forms/autoComplete";
import { useApiContext } from "../../ApiContext";
import { useIsMounted } from "@vanilla/react-utils";

export interface ILookupApi {
    searchUrl: string;
    singleUrl: string | null;
    valueKey?: string;
    labelKey?: string;
    extraLabelKey?: string;
    resultsKey?: string;
    excludeLookups?: string[];
    processOptions?: (options: IAutoCompleteOption[]) => IAutoCompleteOptionProps[];
    group?: string; // if this is passed, all options will be grouped under this in dropdown
    initialOptions?: IAutoCompleteOption[] | undefined;
}

interface IAutoCompleteLookupProps {
    lookup: ILookupApi;
    ignoreLookupOnMount?: boolean;
    api?: AxiosInstance;
    handleLookupResults?(result: IAutoCompleteOption[]): void;
    /**
     * Whether the results of the lookup should be added to the context's options.
     * Defaults to `true`.
     * To manage the options list yourself, set this to `false` and pass an implementation of `handleLookupResults`.
     */
    addLookupResultsToOptions?: boolean;
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
    const { lookup, handleLookupResults, ignoreLookupOnMount, addLookupResultsToOptions = true } = props;
    const contextApi = useApiContext();
    const api = props.api ?? contextApi;
    const { options, handleSearch, loadIndividualOptions } = useApiLookup(lookup, api, ignoreLookupOnMount);
    const { inputState, value, setOptions } = useAutoCompleteContext();

    const hasInitialOptions = lookup.initialOptions && lookup.initialOptions?.length > 0;

    // this can't be done only with initial values, because many forms are mounted before the initial values are set
    useEffect(() => {
        const values = [value ?? undefined]
            .flat()
            .filter(notEmpty)
            .map((value) => `${value}`);
        if (values.filter((value) => value !== "").length > 0 && !ignoreLookupOnMount && !hasInitialOptions) {
            loadIndividualOptions(values);
        }
    }, [value, hasInitialOptions]);

    useEffect(() => {
        if (hasInitialOptions) {
            setOptions(lookup.initialOptions!);
        }
    }, [hasInitialOptions]);

    const debouncedHandleSearch = debounce(handleSearch, 200);

    useEffect(() => {
        if (inputState.status !== "IDLE") {
            // search once with no value.
            // the result will be cached, preventing subsequent duplicate api calls
            handleSearch("");
            const stringValue = `${inputState.value}`;
            if (stringValue !== "") {
                debouncedHandleSearch(stringValue);
            }
        }
    }, [inputState.status, inputState.value]);

    useEffect(() => {
        const isLoading = options.length === 0;
        if (!isLoading) {
            if (addLookupResultsToOptions) {
                setOptions((oldOptions) => {
                    return uniqBy([...(oldOptions ?? []), ...options], "value");
                });
            }
            try {
                handleLookupResults?.(options);
            } catch (err) {
                logError("Failed to lookup autocomplete options", err);
            }
        }
    }, [handleLookupResults, setOptions, options]);

    return null;
}

/**
 * This hook is used to fetch and process search results
 */
export function useApiLookup(
    lookup: ILookupApi,
    api: AxiosInstance,
    ignoreLookupOnMount?: boolean,
): {
    options: IAutoCompleteOption[];
    handleSearch: (value: string) => Promise<void>;
    loadIndividualOptions: (values: string[]) => Promise<void>;
} {
    const isMounted = useIsMounted();

    const [options, _setOptions] = useState<IAutoCompleteOption[]>([]);

    function setOptions(opts: typeof options) {
        if (!isMounted()) {
            return;
        }
        _setOptions((prev) => {
            return uniqBy([...prev, ...opts], "value");
        });
    }

    const {
        searchUrl,
        singleUrl,
        resultsKey = ".",
        labelKey = "name",
        extraLabelKey = "",
        valueKey = "name",
        processOptions,
        excludeLookups = [],
        group,
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
                group: group,
            };
        },
        [labelKey, extraLabelKey, valueKey],
    );

    function handleSingleResultApiResponse(response: any) {
        if (!isMounted()) {
            return;
        }
        if (response.data) {
            let options = [transformApiToOption(response.data)];
            if (processOptions) {
                options = processOptions(options);
            }
            return options;
        }
    }

    async function loadIndividualOption(value: string) {
        if ([...excludeLookups, ""].includes(value)) {
            return;
        }

        const existingOption = options.find((option) => `${option.value}` === `${value}`);
        if (existingOption) {
            return;
        }

        if (ignoreLookupOnMount) {
            return;
        }

        if (singleUrl === null) {
            handleSearch("");
            return;
        }

        const url = singleUrl.replace("/api/v2", "").replace("%s", value);
        const cachedByUrl = apiCaches.get(url);

        if (cachedByUrl) {
            setOptions(cachedByUrl);
            return;
        }

        try {
            const response = await api.get(url);
            const options = handleSingleResultApiResponse(response);
            if (options) {
                apiCaches.set(url, options);
                setOptions(options);
            }
        } catch (error) {
            logError(error);
        }
    }

    async function loadIndividualOptions(values: string[]): Promise<void> {
        await Promise.all(values.map(loadIndividualOption));
    }

    async function handleSearch(inputValue: string): Promise<void> {
        const url = searchUrl.replace("/api/v2", "").replace("%s", inputValue);
        if (apiCaches.has(url)) {
            setOptions(apiCaches.get(url));
        } else {
            try {
                const response = await api.get(url);
                if (!isMounted()) {
                    return;
                }
                const { data } = response;
                let options: IAutoCompleteOption[] = [];

                const results = resultsKey === "." ? data : get(data, resultsKey, []);
                options = results.map(transformApiToOption);

                if (processOptions) {
                    options = processOptions(options);
                }
                apiCaches.set(url, options);
                setOptions(options);
            } catch (error) {
                logError(error);
            }
        }
    }

    return {
        options,
        loadIndividualOptions,
        handleSearch,
    };
}

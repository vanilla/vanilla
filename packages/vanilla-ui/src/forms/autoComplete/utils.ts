/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ChangeEvent, useState, useEffect, useMemo } from "react";

type FilteredOptions = [string[], React.HTMLAttributes<HTMLInputElement>];
type FilteredOptionsState =
    | { state: "selected"; searchValue?: undefined }
    | { state: "searching"; searchValue: string };

/**
 * Filters AutoComplete options.
 * Returns filtered options and props to pass to AutoCompleteInput.
 * This will also make sure the autocomplete resets to the selected value on blur.
 * Filtering is disabled when the search term matches the selected value.
 * @param options The list of options to filter.
 * @param selected The currently selected value.
 * @returns A filtered list of options.
 */
export function useFilteredOptions(options: string[], selected: string = ""): FilteredOptions {
    const [{ state, searchValue }, setData] = useState<FilteredOptionsState>({ state: "selected" });

    const filteredOptions = useMemo(() => {
        const searchStr = state === "selected" ? selected : searchValue ?? "";
        const lowerCaseSearch = searchStr.trim().toLowerCase();
        const terms = lowerCaseSearch.split(/[ +]/);
        const matchedOptions = (options ?? []).map((option) => {
            const lowerCaseOption = option.toLowerCase();
            return { option, matches: terms.filter((term) => lowerCaseOption.includes(term)).length };
        });
        return matchedOptions
            .filter((option) => state === "selected" || option.matches > 0)
            .sort((a, b) => b.matches - a.matches)
            .map((option) => option.option);
    }, [options, state, selected, searchValue]);

    const inputProps = useMemo(
        () => ({
            value: state === "selected" ? selected : searchValue,
            onBlur: () => {
                setData({ state: "selected" });
            },
            onChange: (event: ChangeEvent<HTMLInputElement>) => {
                setData({ state: "searching", searchValue: event.target.value });
            },
        }),
        [searchValue, selected, state],
    );

    return [filteredOptions, inputProps];
}

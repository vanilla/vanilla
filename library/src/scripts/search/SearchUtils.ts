/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { NEW_SEARCH_PAGE_ENABLED } from "@library/search/searchConstants";
import { formatUrl } from "@library/utility/appUtils";
import qs from "qs";

/**
 * This will convert an object with start date string and end date string into string.
 *
 */
export function dateRangeToString(dateRange: { start?: string; end?: string }): string | undefined {
    let { start, end } = dateRange;
    let dateRangeString: string | undefined;
    if (start && end) {
        if (start === end) {
            // Simple equality.
            dateRangeString = start;
        } else {
            // We should only have one comma in the date string (if time also included, we might have a comma)
            start = start.replace(/,/g, "");
            end = end.replace(/,/g, "");

            // Date range
            dateRangeString = `[${start},${end}]`;
        }
    } else if (start) {
        // Only start date
        dateRangeString = `>=${start}`;
    } else if (end) {
        // Only end date.
        dateRangeString = `<=${end}`;
    }
    return dateRangeString;
}

/**
 * This extracts a date string from a string and converts it into an object containing start/end dates.
 *
 */
export function dateStringInUrlToDateRange(dateStringInUrl: string): { start?: string; end?: string } {
    let dateRange: { start?: string; end?: string } = {};
    if (dateStringInUrl.includes("=")) {
        const dateString = dateStringInUrl.match(/[^=]*$/g);
        const hasMatch = dateString && dateString[0];
        if (hasMatch) {
            if (dateStringInUrl.includes(">=")) {
                dateRange.start = dateString[0];
            } else if (dateStringInUrl.includes("<=")) {
                dateRange.end = dateString[0];
            } else {
                dateRange.start = dateString[0];
                dateRange.end = dateString[0];
            }
        }

        // array, two dates are present
    } else if (dateStringInUrl.includes("[")) {
        const dateString = dateStringInUrl.replace(/[[\]']+/g, "");
        const datesArray = dateString.split(",");
        dateRange.start = datesArray[0];
        dateRange.end = datesArray[1];

        // exact date
    } else if (dateStringInUrl.length > 0) {
        dateRange.start = dateStringInUrl;
        dateRange.end = dateStringInUrl;
    }

    return dateRange;
}

export const makeSearchUrl = (query: string, options: Record<string, any>, externalSearchQuery?: string) => {
    const queryParamName = NEW_SEARCH_PAGE_ENABLED ? "query" : "search";
    const queryParams = {
        ...options,
        [queryParamName]: query,
    };

    const url =
        externalSearchQuery && externalSearchQuery !== ""
            ? externalSearchQuery.replace("%s", encodeURI(query))
            : formatUrl(`/search?${qs.stringify(queryParams)}`, true);
    return url;
};

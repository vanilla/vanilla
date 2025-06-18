/**
 * Converts number to formatted string based on site local.
 */

import { getJSLocaleKey } from "./localeStore";

/**
 * Formats a number to a string based on the site locale.
 * @param number
 * @returns string
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export const formatNumber = (number: number): string => {
    return new Intl.NumberFormat(getJSLocaleKey()).format(number);
};

/**
 * Converts large numbers into shorter/compact to formatted string based on site local.
 *
 * @param number
 * @returns string
 *
 * @public
 * @package @vanilla/injectables/Utils
 */
export const formatNumberCompact = (number: number): string => {
    return new Intl.NumberFormat(getJSLocaleKey(), {
        maximumFractionDigits: 1,
        //Looks like notation is not included in ts NumberOptions https://github.com/microsoft/TypeScript/issues/36533
        // @ts-ignore
        notation: "compact",
    }).format(number);
};

export const formatList = (strs: string[] | string): string => {
    if (!Array.isArray(strs)) {
        return strs;
    }
    if (!("ListFormat" in Intl)) {
        return strs.join(", ");
    }
    const formatter = new Intl.ListFormat(getJSLocaleKey());
    return formatter.format(strs);
};

/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export const getValueIfItExists = (haystack: object | undefined, needle: string, fallback?: any, debug = false) => {
    if (haystack && checkIfKeyExistsAndIsDefined(haystack, needle)) {
        return haystack[needle];
    } else {
        return fallback;
    }
};

export const checkIfKeyExistsAndIsDefined = (haystack: object, needle: string) => {
    if (haystack && typeof haystack === "object" && !!needle) {
        return needle in haystack && haystack[needle] !== undefined;
    } else {
        return false;
    }
};

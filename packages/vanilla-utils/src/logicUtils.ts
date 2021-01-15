/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import set from "lodash/set";

export function spaceshipCompare(a, b): number {
    if (a > b) {
        return 1;
    } else if (a < b) {
        return -1;
    } else {
        return 0;
    }
}

export function flattenObject(obj: Record<any, any>, delimiter = "") {
    return Object.keys(obj).reduce((acc, key) => {
        const prefix = delimiter.length ? delimiter + "." : "";
        if (typeof obj[key] === "object") {
            Object.assign(acc, flattenObject(obj[key], prefix + key));
        } else {
            acc[prefix + key] = obj[key];
        }
        return acc;
    }, {});
}

export function unflattenObject(original: Record<any, any>) {
    const result = {};

    for (const [key, value] of Object.entries(original)) {
        set(result, key, value);
    }

    return result;
}

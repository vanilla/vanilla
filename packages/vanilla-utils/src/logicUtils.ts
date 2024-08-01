/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import set from "lodash-es/set";
import { ColorHelper, color } from "csx";

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
        if (typeof obj[key] === "object" && !Array.isArray(obj[key]) && obj[key] !== null) {
            const colorObj = flattenColorObject(obj[key]);
            // Object has color properties, please stand aside for further questioning
            if (colorObj) {
                if (typeof colorObj === "string") {
                    // The flattened color object is string, you may pass
                    acc[prefix + key] = colorObj;
                } else {
                    // The flattened color object has nested colors, let's flatten it
                    Object.assign(acc, flattenObject(colorObj, prefix + key));
                }
            } else {
                // Object is not a color, you may continue flattening
                Object.assign(acc, flattenObject(obj[key], prefix + key));
            }
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

function flattenColorObject(obj: any) {
    // Object is a ColorHelper instance, return the string representation
    if (obj instanceof ColorHelper) {
        return obj.toString();
    }

    // Get the keys of a ColorHelper instance
    const colorHelperKeys = Object.keys(color("#000"));
    // Get the keys that are in the object
    const objKeys = Object.keys(obj);
    // Get any keys that are not part of a ColorHelper instance
    const nonColorKeys = objKeys.filter((key) => !colorHelperKeys.includes(key));
    // Check if the object has properties similar to a ColorHelper instance
    const hasColorHelperKeys = objKeys.filter((key) => colorHelperKeys.includes(key)).length;

    if (hasColorHelperKeys) {
        // Object has keys that are not part of a ColorHelper instance, return nested object
        if (nonColorKeys.length) {
            const newObj = Object.fromEntries(
                nonColorKeys.map((key) => {
                    const childColorObj = flattenColorObject(obj[key]);
                    return [key, childColorObj ?? obj[key]];
                }),
            );

            return flattenObject(newObj);
        }
        // Object has only keys that are part of a ColorHelper instance, return the string representation
        return new ColorHelper(obj.f, obj.r, obj.g, obj.b, obj.a, obj.o).toString();
    }

    return undefined;
}

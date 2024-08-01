/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { important, px } from "csx";
import { isNumeric } from "@vanilla/utils";
import { logError } from "@vanilla/utils";

export function styleUnit(
    val: string | number | undefined,
    options?: { unitFunction?: (value) => string; isImportant?: boolean; debug?: boolean },
) {
    const { unitFunction = px, isImportant = false, debug = false } = options || {};

    if (typeof val === "object") {
        logError(`You cannot pass objects (${JSON.stringify(val)}) to the "unit" function`);
        return undefined;
    }

    if (val === undefined) {
        return undefined;
    }

    const valIsNumeric = isNumeric(val.toString().trim());

    let output;

    if (typeof val === "string" && !valIsNumeric) {
        output = val;
    } else if (val !== undefined && val !== null && valIsNumeric) {
        output = unitFunction(val as number);
    } else {
        output = val;
    }

    if (isImportant) {
        return important(output);
    } else {
        return output;
    }
}

export const importantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = styleUnit(val);
    return withUnit ? important(withUnit.toString()) : withUnit;
};

export const negativeImportantUnit = (val: string | number | undefined, unitFunction = px) => {
    const withUnit = styleUnit(val);
    return withUnit ? important(negative(withUnit).toString()) : withUnit;
};

export const negativeUnit = (val: string | number | undefined, unitFunction = px) => {
    return negative(styleUnit(val));
};

export const negative = (val) => {
    if (typeof val === "string") {
        val = val.trim();
        if (val.startsWith("-")) {
            return val.substring(1, val.length).trim();
        } else {
            return `-${val}`;
        }
    } else if (!!val && !isNaN(val)) {
        return val * -1;
    } else {
        return val;
    }
};

import { important, px } from "csx";
import isNumeric from "validator/lib/isNumeric";
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

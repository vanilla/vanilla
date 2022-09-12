import { Condition, IPtrReference, Path } from "./types";
import get from "lodash/get";
import Ajv from "ajv";

const ajv = new Ajv({
    // Disables strict mode. Makes it possible to add unsupported properties to schemas.
    strict: false,
});

/**
 * Returns invalid conditions.
 * @param conditions
 * @returns
 */
export function validateConditions(conditions: Condition[], rootInstance: any) {
    const invalid = conditions.flatMap((condition) => {
        try {
            const val = get(rootInstance, condition.field, condition.default ?? null);
            if (!ajv.validate(condition, val)) {
                return [condition];
            }
        } catch {
            // Not able to dereference the pointer, assume it is invalid.
            return [condition];
        }
        return [];
    });
    return {
        isValid: !invalid.length,
        conditions: invalid,
    };
}

/**
 * Finds all references recursively in an object and returns their definition.
 *
 * findAllReferences({ item: { priceDisplay: "{price} $", price: 1.99 }});

 * // returns: [{ path: ["item", "priceDisplay"], ref: "price" }]
 * @param anyObj
 * @returns
 */
export function findAllReferences(anyObj: any, path: Array<string | number> = []): IPtrReference[] {
    return Object.entries(anyObj).flatMap(([key, value]) => {
        if (typeof value === "string") {
            return (value.match(/{[^}]+}/g) || []).map((match) => {
                const ref = match.substr(1, match.length - 2).split(".");
                return { path: [...path, key], ref };
            });
        }
        if (typeof value === "object" && value !== null) return findAllReferences(value, [...path, key]);
        return [];
    });
}

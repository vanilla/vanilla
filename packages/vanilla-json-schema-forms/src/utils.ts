import { Condition, IPtrReference, Path } from "./types";
import get from "lodash/get";

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
        if (typeof value === "object") return findAllReferences(value, [...path, key]);
        return [];
    });
}

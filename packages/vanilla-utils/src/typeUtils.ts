/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Draft } from "immer";

interface IClass {
    new (): any;
}

/**
 * Determine if an instance is an instance of any of the listed classes.
 *
 * @param needle The instance.
 * @param haystack The classes to check.
 */
export function isInstanceOfOneOf(needle: any, haystack: IClass[]) {
    for (const classItem of haystack) {
        if (needle instanceof classItem) {
            return true;
        }
    }

    return false;
}

/**
 * Transform an array of objects and an map of objets with a given key.
 *
 * Objects that do not contain the given key are dropped.
 *
 * @param array The array to go through.
 * @param key The key to lookup.
 */
export function indexArrayByKey<T extends object>(
    array: T[],
    key: string,
): {
    [key: string]: T[];
} {
    const object = {};
    for (const item of array) {
        if (key in item) {
            if (!(item[key] in object)) {
                object[item[key]] = [];
            }
            object[item[key]].push(item);
        }
    }
    return object;
}

export function notEmpty<TValue>(value: TValue | null | undefined): value is TValue {
    return value !== null && value !== undefined;
}

export function ensureString(maybeString: any) {
    if (typeof maybeString !== "string") throw new TypeError("Expected maybeString to have type string");
    return maybeString;
}

export function forceInt(value: string | number | undefined | null, fallback: number): number {
    if (typeof value === "number") {
        return value;
    }
    let result = Number.parseInt(value ?? "", 10);
    return Number.isNaN(result) ? fallback : result;
}

export type RecordID = string | number;

/**
 * Coerce a value into a boolean.
 *
 * @param maybeBool
 * @returns
 */
export function forceBool(maybeBool: any): boolean {
    if (typeof maybeBool === "boolean") {
        return maybeBool;
    }

    if (typeof maybeBool === "string") {
        if (maybeBool === "true") {
            return true;
        } else if (maybeBool === "false") {
            return false;
        } else {
            return !!maybeBool;
        }
    }

    return !!maybeBool;
}

/**
 * Can be removed once we update our typescript version.
 */
export function castDraft<T>(item: T): Draft<T> {
    return item as Draft<T>;
}

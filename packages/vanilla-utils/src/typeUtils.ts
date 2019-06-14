/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
    [key: string]: T;
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

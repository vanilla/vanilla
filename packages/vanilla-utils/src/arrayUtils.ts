/**
 * Utilities related to arrays.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Insert a subject (might be anything) at an array index, returns a new array.
 *
 * @param paramArray The array.
 * @param sub The subject to be added.
 * @param index The index the subject should be added.
 * @returns
 */
export function insertAt(arr: any[], sub: any, index: number) {
    return [...arr.slice(0, index), sub, ...arr.slice(index, arr.length)];
}

/**
 * Remove a node at an array index, returns a new array.
 *
 * @param paramArray The array.
 * @param index The index the subject should be added.
 * @returns
 */
export function removeAt(arr: any[], index: number) {
    return [...arr.slice(0, index), ...arr.slice(index + 1, arr.length)];
}

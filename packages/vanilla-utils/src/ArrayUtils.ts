/**
 * Utilities related to arrays.
 *
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export class ArrayUtils {
    /**
     * Insert a subject (might be anything) at an array index, returns a new array.
     *
     * @param arr The array.
     * @param sub The subject to be added.
     * @param index The index the subject should be added.
     * @returns
     */
    public static insertAt<T, U>(arr: T[], sub: U, index: number): Array<T | U> {
        return [...arr.slice(0, index), sub, ...arr.slice(index, arr.length)];
    }

    /**
     * Swap two indexes in an array. Clones the array.
     *
     * @param arr The array to swap values in.
     * @param x The first index to swap.
     * @param y The destination index to swap with.
     * @returns arr A new array with the changes.
     */
    public static swap<T>(arr: T[], x: number, y: number): T[] {
        if (x < 0 || y < 0) {
            throw new Error("Swapping a negative index is unsupported");
        }

        const maxIndex = arr.length - 1;
        if (x > maxIndex || y > maxIndex) {
            throw new Error("Swapping beyond array length is unsupported");
        }

        arr = arr.slice();
        arr[x] = arr.splice(y, 1, arr[x])[0];
        return arr;
    }

    /**
     * Move an element from one index to another.
     *
     * @param arr The array to swap values in.
     * @param sourceIndex The first index to swap.
     * @param destIndex The destination index to swap with.
     * @returns arr A new array with the changes.
     */
    public static move<T>(arr: T[], sourceIndex: number, destIndex: number): T[] {
        if (sourceIndex < 0 || destIndex < 0) {
            throw new Error("Moving a negative index is unsupported");
        }
        var item = arr[sourceIndex];
        var length = arr.length;
        var diff = sourceIndex - destIndex;

        // Comments here use this as an example
        // Example
        if (diff > 0) {
            // Move left
            // move([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 6, 3)
            return [
                ...arr.slice(0, destIndex), // [0, 1, 2]
                item, // 6
                ...arr.slice(destIndex, sourceIndex), // [4, 5]
                ...arr.slice(sourceIndex + 1, length), // [7, 8, 9]
            ];
        } else if (diff < 0) {
            // Move right
            // move([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 3, 6)
            return [
                ...arr.slice(0, sourceIndex), // [0, 1, 2]
                ...arr.slice(sourceIndex + 1, destIndex + 1), // [4, 5]
                item, // 3
                ...arr.slice(destIndex + 1, length), // [6, 7, 8, 9]
            ];
        } else {
            // No change.
            return arr.slice();
        }
    }

    /**
     * Remove a node at an array index, returns a new array.
     *
     * @param arr The array.
     * @param index The index the subject should be added.
     * @returns arr A new array.
     */
    public static removeAt<T>(arr: T[], index: number): T[] {
        return [...arr.slice(0, index), ...arr.slice(index + 1, arr.length)];
    }
}

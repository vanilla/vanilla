/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

export function concatNewRows<T, K>(currentRows: T[], newRows: T[], pluck: (o: T) => K) {
    const keys = new Map<K, T>();
    currentRows.forEach((o) => {
        keys.set(pluck(o), o);
    });
    newRows.forEach((r) => {
        if (!keys.has(pluck(r))) {
            currentRows.push(r);
        }
    });
}

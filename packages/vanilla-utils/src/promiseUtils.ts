/**
 * Utility functions related to promises/async.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

type NormalCallback = (...args: any[]) => any;
type PromiseCallback = (...args: any[]) => Promise<any>;

export type PromiseOrNormalCallback = NormalCallback | PromiseCallback;

/**
 * Resolve an array of functions that return promises sequentially.
 *
 * @param promiseFunctions - The functions to execute.
 *
 * @returns An array of all results in sequential order.
 *
 * @example
 * const urls = ['/url1', '/url2', '/url3']
 * const functions = urls.map(url => () => fetch(url))
 * resolvePromisesSequentially(funcs)
 *   .then(console.log)
 *   .catch(console.error)
 */
export function resolvePromisesSequentially(promiseFunctions: PromiseOrNormalCallback[]): Promise<any[]> {
    if (!Array.isArray(promiseFunctions)) {
        throw new Error("First argument needs to be an array of Promises");
    }

    return new Promise((resolve, reject) => {
        let count = 0;
        let results = [];

        function iterationFunction(previousPromise, currentPromise) {
            return previousPromise
                .then(result => {
                    if (count++ !== 0) {
                        results = results.concat(result);
                    }

                    return currentPromise(result, results, count);
                })
                .catch(err => reject(err));
        }

        promiseFunctions = promiseFunctions.concat(() => Promise.resolve());

        promiseFunctions.reduce(iterationFunction, Promise.resolve(false)).then(() => {
            resolve(results);
        });
    });
}

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { getMeta } from "@core/application";

/**
 * Resolve an array of functions that return promises sequentially.
 *
 * @param {PromiseOrNormalCallback[]} promiseFunctions - The functions to execute.
 *
 * @returns {Promise<any[]>} - An array of all results in sequential order.
 *
 * @example
 * const urls = ['/url1', '/url2', '/url3']
 * const functions = urls.map(url => () => fetch(url))
 * resolvePromisesSequentially(funcs)
 *   .then(console.log)
 *   .catch(console.error)
 */
export function resolvePromisesSequentially(promiseFunctions) {
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

/**
 * Log something to console.
 *
 * This only prints in debug mode.
 *
 * @param {any} value - The value to log.
 */
export function log(...value) {
    if (getMeta("debug", false)) {
        // eslint-disable-next-line no-console
        console.log(...value);
    }
}

/**
 * Log an error to console.
 *
 * @param {any} value - The value to log.
 */
export function logError(...value) {
    // eslint-disable-next-line no-console
    console.error(...value);
}

/**
 * Log a warning to console.
 *
 * @param {any} value - The value to log.
 */
export function logWarning(...value) {
    // eslint-disable-next-line no-console
    console.warn(...value);
}

/**
 * A simple, fast method of hashing a string. Similar to Java's hash function.
 * https://stackoverflow.com/a/7616484/1486603
 *
 * @param {string} str - The string to hash.
 *
 * @returns {number} - The hash code returned.
 */
export function hashString(str) {
    function hashReduce(prevHash, currVal) {
        return (prevHash << 5) - prevHash + currVal.charCodeAt(0);
    }
    return str.split("").reduce(hashReduce, 0);
}

/**
 * Generates a random string of letters and numbers and a few whitelisted characters.
 *
 * @param {number=} length - The lenght of the desired string.
 *
 * @returns {string} - The generated string.
 * @throws {Error} - If you pass a length less than 0.
 */
export function generateRandomString(length = 5) {
    if (length < 0) {
        throw new Error("generateRandomString can only deal with non-negative lengths.");
    }

    if (!Number.isInteger(length)) {
        throw new Error("generateRandomString can only deal with integers.");
    }

    const chars = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%*";
    let result = "";
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

/**
 * Re-exported from sprintf-js https://www.npmjs.com/package/sprintf-js
 */
// export const sprintf = sprintfJs.sprintf;

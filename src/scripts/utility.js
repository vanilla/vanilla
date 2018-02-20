/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import gdn from "@core/gdn";

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
        throw new Error("First argument need to be an array of Promises");
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
 * @param {...any} value - The value to log.
 */
export function log(...value) {
    if (getMeta("debug", false)) {
        // eslint-disable-next-line no-console
        console.log.apply(console, value);
    }
}

/**
 * Log an error to console.
 *
 * @param {...any} value - The value to log.
 */
export function logError(...value) {
    // eslint-disable-next-line no-console
    console.error.apply(console, value);
}

/**
 * Log a warning to console.
 *
 * @param {any} value - The value to log.
 */
export function logWarning(value) {
    // eslint-disable-next-line no-console
    console.warn(value);
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
 * Get a piece of metadata passed from the server.
 *
 * @param {string} key - The key to lookup.
 * @param {any=} defaultValue - A fallback value in case the key cannot be found.
 *
 * @returns {any}
 */
export function getMeta(key, defaultValue = undefined) {
    if (gdn.meta && gdn.meta[key]) {
        return gdn.meta[key];
    }

    return defaultValue;
}

/**
 * Set a piece of metadata. This will override what was passed from the server.
 *
 * @param {string} key - The key to store under.
 * @param {any} value - The value to set.
 */
export function setMeta(key, value) {
    gdn.meta[key] = value;
}

/**
 * Format a URL in the format passed from the controller.
 *
 * @param {string} path - The path to format.
 *
 * @returns {string}
 */
export function formatUrl(path) {
    if (path.indexOf("//") >= 0) {
        return path;
    } // this is an absolute path.

    const urlFormat = getMeta("UrlFormat", "/{Path}");

    if (path.substr(0, 1) === "/") {
        path = path.substr(1);
    }

    if (urlFormat.indexOf("?") >= 0) {
        path = path.replace("?", "&");
    }

    return urlFormat.replace("{Path}", path);
}

/**
 * Translate a string into the current locale.
 *
 * @param {string} str The string to translate.
 * @param {string=} defaultTranslation The default translation to use.
 */
export function translate(str, defaultTranslation) {
    // Codes that begin with @ are considered literals.
    if (str.substr(0, 1) === '@') {
        return str.substr(1);
    }

    if (gdn.translations[str] !== undefined) {
        return gdn.translations[str];
    }

    return defaultTranslation !== undefined ? defaultTranslation : str;
}

/**
 * The t function is an alias for translate.
 *
 * @type {translate}
 */
export const t = translate;

/**
 * Re-exported from sprintf-js https://www.npmjs.com/package/sprintf-js
 */
// export const sprintf = sprintfJs.sprintf;


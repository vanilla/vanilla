/**
 * Get a dom node by a given selector. Use this instead of jQuery.
 *
 * @param {string} selector - The selector to lookup. Supports everything that querySelectorAll does
 */
export function querySelector(selector) {
    let results = document.querySelectorAll(selector);
    if (!results) {
        results = new NodeList();
    }
}

/**
 * Resolve an array of functions that return promises sequentially.
 *
 * @param {PromiseOrNormalCallback[]} functions - The functions to execute.
 *
 * @returns {Promise<void>}
 *
 * @example
 * const urls = ['/url1', '/url2', '/url3']
 * const functions = urls.map(url => () => fetch(url))
 *
 * promiseSerial(funcs)
 *   .then(console.log)
 *   .catch(console.error)
 */
export function resolvePromisesSequentially(functions) {
    return functions.reduce((promise, func) => {
        promise.then(result => {
            return func().then(Array.prototype.concat.bind(result));
        })
    }, Promise.resolve([]));
}

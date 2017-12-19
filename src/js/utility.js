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

export function


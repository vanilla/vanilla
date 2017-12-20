/**
 * Use the browser's built-in functionality to quickly and safely escape a string.
 *
 * @param {string} str - The string to escape.
 *
 * @returns {string} - Escaped HTML.
 */
export function escapeHTML(str) {
    const div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/**
 * Use the browser's built-in functionality to quickly unescape a string.
 * UNSAFE with unsafe strings; only use on previously-escaped ones!
 *
 * @param {string} escapedString - A previously escaped string.
 *
 * @returns {string} - The unescaped string.
 */
export function unescapeHTML(escapedString) {
    const div = document.createElement("div");
    div.innerHTML = escapedString;
    const child = div.childNodes[0];
    return child ? child.nodeValue : "";
}

/**
 * Add the hidden class and aria-hidden attribute to an Element.
 *
 * @param {HTMLElement} element - The DOM Element to modify.
 */
export function hideElement(element) {
    element.classList.add("u-isHidden");
    element.setAttribute("aria-hidden", "true");
}

/**
 * Remove the hidden class and aria-hidden attribute to an Element.
 *
 * @param {HTMLElement} element - The DOM Element to modify.
 */
export function unhideElement(element) {
    element.classList.remove("u-isHidden");
    element.removeAttribute("aria-hidden");
}

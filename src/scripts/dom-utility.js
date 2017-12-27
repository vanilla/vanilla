import * as utility from "@core/utility";

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

/**
 * Check if an element is visible or not.
 *
 * @param {Element} element - The element to check.
 *
 * @returns {boolean} - The visibility.
 */
export function elementIsVisible(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    return element.offsetWidth > 0 && element.offsetHeight > 0;
}

const eventFunctionKeys = [];

/**
 * Create an event listener using event delegation.
 *
 * @param {string} eventName - The Event to listen for.
 * @param {string} filterSelector - A CSS selector to match against.
 * @param {function} callback - The callback function. This gets passed the fired event.
 * @param {string=} scopeSelector - And element to scope the event listener to.
 */
export function delegateEvent(eventName, filterSelector, callback, scopeSelector) {
    let functionKey = eventName + filterSelector + callback.toString();

    /** @type {Document | Element} */
    let scope;

    if (scopeSelector) {
        scope = document.querySelector(scopeSelector);

        if (!scope) {
            return;
        } else {
            functionKey += scopeSelector;
        }
    } else {
        scope = document;
    }

    const eventHash = utility.hashString(functionKey);

    if (!eventFunctionKeys.includes(eventHash)) {
        eventFunctionKeys.push(eventHash);
        scope.addEventListener(eventName, () => {

            // @ts-ignore
            if (event.target.matches(filterSelector)) {
                callback(event);
            }
        });
    }
}

/**
 * Toggle any attribute on an element.
 *
 * @param {Element} element - The element to toggle on.
 * @param {string} attribute - The attribute to toggle.
 */
export function toggleAttribute(element, attribute) {
    const newValue = element.getAttribute(attribute) === "false";
    element.setAttribute(attribute, newValue);
}

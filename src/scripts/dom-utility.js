/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

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
 * @param {HTMLElement} element - The element to check.
 *
 * @returns {boolean} - The visibility.
 */
export function elementIsVisible(element) {
    return !!( element.offsetWidth || element.offsetHeight || element.getClientRects().length );
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

        scope.addEventListener(eventName, (event) => {

            // Get the nearest DOMNode that matches the given selector.
            const match = event.target.closest(filterSelector);

            if (match) {

                // Call the callback with the matching element as the context.
                callback.call(match, event);
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

const dataMap = new WeakMap();

/**
 * Set a piece of data specific to a DOM Element. Similar to `$.data`.
 *
 * @param {Element} element - The DOM Element to assosciate the data with.
 * @param {string} key - The key to assosciate the data with.
 * @param {string} value - The value to store.
 */
export function setData(element, key, value) {
    const initialValue = dataMap.has(element) ? dataMap.get(element) : {};
    initialValue[key] = value;

    dataMap.set(element, initialValue);
}

/**
 * Get a piece of data specific to a DOM Element. Similar to `$.data`.
 *
 * @param {Element} element - The DOM Element to lookup.
 * @param {string} key - The key to lookup.
 * @param {any=} defaultValue - A value to use if the element or key aren't found.
 *
 * @return {any}
 */
export function getData(element, key, defaultValue) {
    if (dataMap.has(element) && dataMap.get(element)[key]) {
        return dataMap.get(element)[key];
    }

    const attributeString = `data-${key}`;

    if (element.hasAttribute(attributeString)) {
        return element.getAttribute(attributeString);
    }

    return defaultValue;
}

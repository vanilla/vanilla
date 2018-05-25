/**
 * Utilities that have a hard dependency on the DOM.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as utility from "@dashboard/utility";
import { logError } from "@dashboard/utility";
import twemoji from "twemoji";
import tabbable from "tabbable";

/**
 * Use the browser's built-in functionality to quickly and safely escape a string.
 *
 * @param str - The string to escape.
 *
 * @returns Escaped HTML.
 */
export function escapeHTML(str: string): string {
    const div = document.createElement("div");
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/**
 * Use the browser's built-in functionality to quickly unescape a string.
 * UNSAFE with unsafe strings; only use on previously-escaped ones!
 *
 * @param escapedString - A previously escaped string.
 *
 * @returns The unescaped string.
 */
export function unescapeHTML(escapedString: string): string {
    const div = document.createElement("div");
    div.innerHTML = escapedString;
    const child = div.childNodes[0];
    return child && child.nodeValue ? child.nodeValue : "";
}

/**
 * Add the hidden class and aria-hidden attribute to an Element.
 *
 * @param element - The DOM Element to modify.
 */
export function hideElement(element: Element) {
    element.classList.add("u-isHidden");
    element.setAttribute("aria-hidden", "true");
}

/**
 * Remove the hidden class and aria-hidden attribute to an Element.
 *
 * @param element - The DOM Element to modify.
 */
export function unhideElement(element: Element) {
    element.classList.remove("u-isHidden");
    element.removeAttribute("aria-hidden");
}

/**
 * Check if an element is visible or not.
 *
 * @param element - The element to check.
 *
 * @returns The visibility.
 */
export function elementIsVisible(element: HTMLElement): boolean {
    return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
}

/**
 * Get the form data out of a form element.
 *
 * @param {Element} formElement - The element to get the data out of.
 *
 * @returns {Object}
 */
export function getFormData(formElement) {
    if (!(formElement instanceof HTMLFormElement)) {
        return {};
    }

    const data = new FormData(formElement);
    const result = {};

    for (const [key, value] of data.entries()) {
        result[key] = value;
    }

    return result;
}

const delegatedEventListeners = {};

/**
 * Create an event listener using event delegation.
 *
 * @param eventName - The Event to listen for.
 * @param filterSelector - A CSS selector to match against.
 * @param callback - The callback function. This gets passed the fired event.
 * @param scopeSelector - And element to scope the event listener to.
 *
 * @returns The hash of the event. Save this to use removeDelegatedEvent().
 */
export function delegateEvent(
    eventName: string,
    filterSelector: string,
    callback: (event: Event, triggeringElement: HTMLElement) => boolean | void,
    scopeSelector?: string | HTMLElement,
): string | undefined {
    let functionKey = eventName + filterSelector + callback.toString();

    let scope;

    if (typeof scopeSelector === "string") {
        scope = document.querySelector(scopeSelector);

        if (!scope) {
            throw new Error(`Unable to find element in the document for scopeSelector: ${scopeSelector}`);
        } else {
            functionKey += scopeSelector;
        }
    } else if (scopeSelector instanceof HTMLElement) {
        scope = scopeSelector;
    } else {
        scope = document;
    }

    const eventHash = utility.hashString(functionKey).toString();

    if (!Object.keys(delegatedEventListeners).includes(eventHash)) {
        const wrappedCallback = event => {
            // Get the nearest DOMNode that matches the given selector.
            const match = filterSelector ? event.target.closest(filterSelector) : event.target;

            if (match) {
                // Call the callback with the matching element as the context.
                return callback.call(match, event, match);
            }
        };

        const listener = scope.addEventListener(eventName, wrappedCallback);
        delegatedEventListeners[eventHash] = {
            scope,
            eventName,
            wrappedCallback,
        };
        return eventHash;
    }
}

/**
 * Remove a delegated event listener.
 *
 * @param eventHash - The event hash passed from delegateEvent().
 */
export function removeDelegatedEvent(eventHash: string) {
    const { scope, eventName, wrappedCallback } = delegatedEventListeners[eventHash];
    scope.removeEventListener(eventName, wrappedCallback);
    delete delegatedEventListeners[eventHash];
}

export function removeAllDelegatedEvents() {
    Object.keys(delegatedEventListeners).forEach(key => {
        removeDelegatedEvent(key);
    });
}

/**
 * Toggle any attribute on an element.
 *
 * @param element - The element to toggle on.
 * @param attribute - The attribute to toggle.
 */
export function toggleAttribute(element: Element, attribute: string) {
    const newValue = element.getAttribute(attribute) === "false";
    element.setAttribute(attribute, newValue);
}

const dataMap = new WeakMap();

/**
 * Set a piece of data specific to a DOM Element. Similar to `$.data`.
 *
 * @param element - The DOM Element to assosciate the data with.
 * @param key - The key to assosciate the data with.
 * @param value - The value to store.
 */
export function setData(element: Element, key: string, value: any) {
    const initialValue = dataMap.has(element) ? dataMap.get(element) : {};
    initialValue[key] = value;

    dataMap.set(element, initialValue);
}

/**
 * Get a piece of data specific to a DOM Element. Similar to `$.data`.
 *
 * @param element - The DOM Element to lookup.
 * @param key - The key to lookup.
 * @param defaultValue - A value to use if the element or key aren't found.
 */
export function getData(element: Element, key: string, defaultValue?: any) {
    if (dataMap.has(element) && dataMap.get(element)[key]) {
        return dataMap.get(element)[key];
    }

    const attributeString = `data-${key}`;

    if (element.hasAttribute(attributeString)) {
        return element.getAttribute(attributeString);
    }

    return defaultValue;
}

/**
 * Get an HTML element from a CSS selector or DOM Node.
 *
 * @param {string|Node} selectorOrElement - A CSS selector or an HTML element.
 *
 * @throws {Error} - If no element was found.
 * @returns {HTMLElement} - An HTMLElement no matter what.
 */
export function ensureHtmlElement(selectorOrElement) {
    if (typeof selectorOrElement === "string") {
        selectorOrElement = document.querySelector(selectorOrElement);
    }

    if (!(selectorOrElement instanceof HTMLElement)) {
        throw new Error(`HTMLElement could not be found for ${selectorOrElement}.`);
    }

    return selectorOrElement;
}

// Test Char for Emoji 5.0
const testChar = "\uD83E\uDD96"; // U+1F996 T-Rex -> update test character with new emoji version support.

let emojiSupportedCache: boolean | null = null;

export function isEmojiSupported() {
    if (emojiSupportedCache !== null) {
        return emojiSupportedCache;
    }

    if (process.env.NODE_ENV !== "test") {
        // Test environment
        const canvas = document.createElement("canvas");
        if (canvas.getContext && canvas.getContext("2d")) {
            const pixelRatio = window.devicePixelRatio || 1;
            const offset = 12 * pixelRatio;
            const ctx = canvas.getContext("2d");
            ctx!.fillStyle = "#f00";
            ctx!.textBaseline = "top";
            ctx!.font = "32px Arial";
            ctx!.fillText(testChar, 0, 0);
            emojiSupportedCache = ctx!.getImageData(offset, offset, 1, 1).data[0] !== 0;
        } else {
            emojiSupportedCache = false;
        }
    } else {
        emojiSupportedCache = true;
    }

    return emojiSupportedCache;
}

const emojiOptions = {
    className: "fallBackEmoji",
    size: "72x72",
};

/**
 * Returns either native emoji or fallback image
 *
 * @param stringOrNode - A DOM Node or string to convert.
 */
export function convertToSafeEmojiCharacters(stringOrNode: string | Node) {
    if (isEmojiSupported()) {
        return stringOrNode;
    }
    return twemoji.parse(stringOrNode, emojiOptions);
}

// A weakmap so we can store multiple load callbacks per script.
const loadEventCallbacks: WeakMap<Node, Array<(event) => void>> = new WeakMap();

/**
 * Dynamically load a javascript file.
 */
export function ensureScript(scriptUrl: string) {
    return new Promise(resolve => {
        const existingScript: HTMLScriptElement | null = document.querySelector(`script[src='${scriptUrl}']`);
        if (existingScript) {
            if (loadEventCallbacks.has(existingScript)) {
                // Add another resolveCallback into the weakmap.
                const callbacks = loadEventCallbacks.get(existingScript);
                callbacks && callbacks.push(resolve);
            } else {
                // Script is already loaded. Resolve immediately.
                resolve();
            }
        } else {
            // The script doesn't exist. Lets create it.
            const head = document.getElementsByTagName("head")[0];
            const script = document.createElement("script");
            script.type = "text/javascript";
            script.src = scriptUrl;

            loadEventCallbacks.set(script, [resolve]);

            script.onload = event => {
                const callbacks = loadEventCallbacks.get(script);
                callbacks && callbacks.forEach(callback => callback(event));
                loadEventCallbacks.delete(script);
            };

            head.appendChild(script);
        }
    });
}

export function getNextTabbableElement(
    rootNode: Node = document,
    goForward: boolean = true,
    element: Node = document.activeElement,
) {
    if (element instanceof HTMLElement) {
        const tabbables = tabbable(rootNode);
        const currentTabIndex = tabbables.indexOf(element);
        let targetIndex = goForward ? currentTabIndex + 1 : currentTabIndex - 1;
        window.console.log("--currentTabIndex: ", currentTabIndex);

        if (currentTabIndex > 0) {
            window.console.log("tabbables: ", tabbables);
            window.console.log("currentTabIndex: ", currentTabIndex);
            window.console.log("targetIndex: ", targetIndex);

            if (tabbables.length > 0) {
                if (targetIndex < 0) {
                    targetIndex = tabbables.length - 1;
                } else if (targetIndex >= tabbables.length) {
                    targetIndex = 0;
                }

                window.console.log("= targetIndex: ", targetIndex);
                window.console.log("=  tabbables[targetIndex]: ", tabbables[targetIndex]);

                return tabbables[targetIndex];
            } else {
                logError("No tabbable elements found in element: ", rootNode);
                logError("currentTabIndex: ", currentTabIndex);
                logError("tabbables: ", tabbables);
                logError("element: ", element);
                logError("targetIndex: ", targetIndex);
            }
        } else {
            logError("Element does not exist in list of tabbable element: ");
            logError("currentTabIndex: ", currentTabIndex);
            logError("tabbables: ", tabbables);
            logError("element: ", element);
            logError("targetIndex: ", targetIndex);
        }
    } else {
        logError("Unable to tab to next element, element given is not valid: ", element);
    }

    return element; // return same element as fallback
}

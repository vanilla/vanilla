/**
 * Utilities that have a hard dependency on the DOM.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import "focus-visible";
import smoothscroll from "smoothscroll-polyfill";
import twemoji from "twemoji";
import { hashString } from "@vanilla/utils";
import React from "react";
import ReactDOM from "react-dom";
import { forceRenderStyles } from "typestyle";

smoothscroll.polyfill();

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

    const data = new FormData(formElement) as any;
    const result = {};

    data.forEach((key, value) => {
        result[key] = value;
    });

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

    const eventHash = hashString(functionKey).toString();

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
    }

    return eventHash;
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
            const ctx = document.createElement("canvas").getContext("2d");
            if (ctx) {
                ctx.fillText("😗", -2, 4);
                emojiSupportedCache = ctx.getImageData(0, 0, 1, 1).data[3] > 0;
            } else {
                emojiSupportedCache = false;
            }
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
const rejectionCache: Map<string, Error> = new Map();

/**
 * Dynamically load a javascript file.
 */
export function ensureScript(scriptUrl: string) {
    return new Promise((resolve, reject) => {
        const existingScript: HTMLScriptElement | null = document.querySelector(`script[src='${scriptUrl}']`);
        if (rejectionCache.has(scriptUrl)) {
            reject(rejectionCache.get(scriptUrl));
        }
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
            script.onerror = (event: ErrorEvent) => {
                const error = new Error("Failed to load a required embed script");
                rejectionCache.set(scriptUrl, error);
                reject(error);
            };

            const timeout = setTimeout(() => {
                const error = new Error(`Loading of the script ${scriptUrl} has timed out.`);
                rejectionCache.set(scriptUrl, error);
                reject(error);
            }, 10000);

            loadEventCallbacks.set(script, [resolve]);

            script.onload = event => {
                clearTimeout(timeout);
                const callbacks = loadEventCallbacks.get(script);
                callbacks && callbacks.forEach(callback => callback(event));
                loadEventCallbacks.delete(script);
            };

            head.appendChild(script);
        }
    });
}

/**
 * Handler for an file being dragged and dropped.
 *
 * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
 */
export function getDraggedFile(event: DragEvent): File | undefined {
    if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
        event.preventDefault();
        const files = Array.from(event.dataTransfer.files);

        // Currently only 1 file is supported.
        const mainFile = files[0];
        return mainFile;
    }
}

/**
 * Handler for an file being pasted.
 *
 * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
 */
export function getPastedFile(event: ClipboardEvent): File | undefined | null {
    if (event.clipboardData && event.clipboardData.items && event.clipboardData.items.length) {
        const files = Array.from(event.clipboardData.items)
            .map((item: any) => (item.getAsFile ? item.getAsFile() : null))
            .filter(Boolean);

        if (files.length > 0) {
            event.preventDefault();
            // Currently only 1 file is supported.
            const mainFile = files[0];
            return mainFile;
        }
    }
}

/**
 * Calculate the height of element with simulated margin collapse.
 *
 * This is ideal for getting the calculate height of a fixed number of items. (not the entire parent).
 *
 * It considers:
 * - Element height
 * - Padding
 * - Borders
 * - Margins
 * - Margin collapsing.
 *
 * @param element - The element to measure
 * @param previousBottomMargin - The bottom margin of the previous element. You can use the returned bottom margin from this function to get this.
 */
export function getElementHeight(
    element: Element,
    previousBottomMargin: number = 0,
): {
    height: number;
    bottomMargin: number;
} {
    const height = element.getBoundingClientRect().height;
    const { marginTop, marginBottom } = window.getComputedStyle(element);

    let topHeight = marginTop ? parseInt(marginTop, 10) : 0;
    // Simulate a margin-collapsed height.
    topHeight = Math.max(topHeight - previousBottomMargin, 0);

    const bottomHeight = marginBottom ? parseInt(marginBottom, 10) : 0;
    const finalHeight = height + topHeight + bottomHeight;

    return {
        height: finalHeight,
        bottomMargin: bottomHeight,
    };
}

/**
 * Determine if the browser is escaping the inner contents of our <noscript /> browser.
 */
function browserEscapesNoScript(): boolean {
    const ns = document.createElement("noscript");
    ns.innerHTML = "<test></test>";
    document.body.append(ns);
    const result = ns.innerHTML.startsWith("&lt;");
    ns.remove();
    return result;
}

/**
 * Prepare an element and it's contents for use in a shadow root.
 *
 * @param element
 * @param cloneElement - If true, clone the element into a `newElementTag`. Preserves CSS classes and IDs.
 *      Particularly useful for when the initial content is inside of a <noscript /> tag.
 * @param newElementTag
 */
export function prepareShadowRoot(element: HTMLElement, cloneElement: boolean = false, newElementTag = "div") {
    let html = element.innerHTML;
    // This is likely a noscript tag.
    if (browserEscapesNoScript()) {
        html = unescapeHTML(html);
    }
    // Safari escapes the contents of the noscript.
    if (cloneElement) {
        const newElement = document.createElement(newElementTag);

        // Clone various attributes.
        newElement.classList.value = element.classList.value;
        newElement.id = element.id;

        // Insert the element & remove the old old.
        element.parentNode!.insertBefore(newElement, element);
        element.remove();
        element = newElement;
    } else {
        // If we aren't making a new real root, we need to empty it out.
        // Otherwise we'll have duplicate contents.
        element.innerHTML = "";
    }

    const shadowHeader = element.attachShadow({ mode: "open" });
    shadowHeader.innerHTML = html;
}

/**
 * Mount a root component of a React tree.
 *
 * - ReactDOM render.
 * - Typestyle render.
 *
 * If the overwrite option is passed this component will replace the components you passed as target.
 *
 * Default Mode:
 * <div><TARGET /></div> -> <div><TARGET><REACT></TARGET><div>
 *
 * Overwrite Mode:
 * <div><TARGET /></div> -> <div><REACT/></div>
 */
export function mountReact(
    component: React.ReactElement,
    target: HTMLElement,
    callback?: () => void,
    options?: { overwrite: true },
) {
    let mountPoint = target;
    let cleanupContainer: HTMLElement | undefined;
    if (options && options.overwrite) {
        const container = document.createElement("span");
        cleanupContainer = container;
        target.parentElement!.insertBefore(container, target);
        mountPoint = container;
    }
    const result = ReactDOM.render(component, mountPoint, () => {
        if (cleanupContainer) {
            target.remove();
            if (cleanupContainer.firstElementChild) {
                cleanupContainer.parentElement!.insertBefore(cleanupContainer.firstElementChild, cleanupContainer);
                cleanupContainer.remove();
                target.remove();
            }
        }
        callback && callback();
    });
    forceRenderStyles();
    return result;
}

/**
 * Utilities that have a hard dependency on the DOM.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { logError, log, hashString } from "@library/utility";
import twemoji from "twemoji";
import tabbable from "tabbable";
import debounce from "lodash/debounce";

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

interface ITabbableOptions {
    root: Element;
    excludedElements: Element[];
    excludedRoots: Element[];
    reverse: boolean;
    fromElement: Element;
    allowLooping: boolean;
}

/**
 * Get the next tabbable item within a given tabindex.
 *
 * WARNING: Performance can be poor if you pass many excluded roots and do not
 * sufficiently narrow the tree your are looking in.
 *
 * @param options
 * @property root - The root element to look in.
 * @property excludedElements - Elements to ignore.
 * @property excludedRoots - These element's children will be ignored.
 * @property reverse - True to get the previous element instead.
 * @property fromElement - The currently focused element.
 * @property allowLooping - Whether or not the focus should loop around from beginning <-> end.
 */
export function getNextTabbableElement(options?: Partial<ITabbableOptions>): HTMLElement | null {
    const defaultTabbableOptions: ITabbableOptions = {
        root: document.documentElement,
        excludedElements: [],
        excludedRoots: [],
        reverse: false,
        fromElement: document.activeElement,
        allowLooping: true,
    };

    // Merge the passed options and the defaults.
    const finalOptions = {
        ...defaultTabbableOptions,
        ...options,
    } as ITabbableOptions;

    if (!(finalOptions.fromElement instanceof HTMLElement)) {
        logError("Unable to tab to next element, `fromElement` given is not valid: ", finalOptions.fromElement);
        return null;
    }

    const tabbables = tabbable(finalOptions.root).filter((tabbableElement: Element) => {
        // We want to excempt items that are the active item or a parent of the active item
        // because otherwise we would not be able to tab away from them.
        const elementIsActiveOrChildOfActive =
            finalOptions.fromElement === tabbableElement || tabbableElement.contains(finalOptions.fromElement);

        if (!elementIsActiveOrChildOfActive) {
            if (finalOptions.excludedElements.includes(tabbableElement)) {
                return false;
            }
            for (const excludedRoot of finalOptions.excludedRoots) {
                if (excludedRoot !== tabbableElement && excludedRoot.contains(tabbableElement)) {
                    return false;
                }
            }
        }

        return true;
    });

    const currentTabIndex = tabbables.indexOf(finalOptions.fromElement);

    if (currentTabIndex < 0) {
        return null;
    }

    let targetIndex = finalOptions.reverse ? currentTabIndex - 1 : currentTabIndex + 1;

    if (finalOptions.allowLooping) {
        // Loop over the beginning and ends
        if (targetIndex < 0) {
            targetIndex = tabbables.length - 1;
        } else if (targetIndex >= tabbables.length) {
            targetIndex = 0;
        }
    }

    return tabbables[targetIndex] || null;
}

function checkDomTreeWasClicked(rootNode: Element | null, clickedElement: Element) {
    return rootNode && clickedElement && (rootNode.contains(clickedElement as Element) || rootNode === clickedElement);
}

/**
 * Determine if the currently focused element is somewhere inside of (or the same as)
 * a given Element.
 *
 * @param rootNode - The root node to look in.
 */
function checkDomTreeHasFocus(rootNode: Element | null, event: FocusEvent, callback: (hasFocus: boolean) => void) {
    setTimeout(() => {
        const possibleTargets = [
            // NEEDS TO COME FIRST, because safari will populate relatedTarget on focusin, and its not what we're looking for.
            document.activeElement, // IE11, Safari.
            event.relatedTarget as Element, // Chrome (The actual standard)
            (event as any).explicitOriginalTarget, // Firefox
        ];

        let activeElement = null;
        for (const target of possibleTargets) {
            if (target && target !== document.body) {
                activeElement = target;
                break;
            }
        }

        if (activeElement !== null) {
            const hasFocus =
                rootNode && activeElement && (activeElement === rootNode || rootNode.contains(activeElement));

            // We will only invalidate based on something actually getting focus.
            callback(!!hasFocus);
        }
    }, 0);
}

/**
 * Register a callback for focusin and focusin out events. The main improvement here over registering
 * the listeners yourself is that the events fire for the whole tree as 1 item instead of as
 * individual notes.
 *
 * This is particularly useful when you want to track focus leaving or enterring a component
 * without caring about the individual contents inside.
 *
 * @param rootNode - The root dom node to watch on.
 * @param callback - A callback for when the tree focuses and blurs.
 */
export function watchFocusInDomTree(rootNode: Element, callback: (hasFocus: boolean) => void) {
    rootNode.addEventListener(
        "focusout",
        (event: FocusEvent) => {
            checkDomTreeHasFocus(rootNode, event, hasFocus => {
                !hasFocus && callback(false);
            });
        },
        true,
    );

    rootNode.addEventListener(
        "focusin",
        (event: FocusEvent) => {
            checkDomTreeHasFocus(rootNode, event, hasFocus => {
                hasFocus && callback(true);
            });
        },
        true,
    );

    document.addEventListener("click", event => {
        const triggeringElement = event.target as Element;
        const wasClicked = checkDomTreeWasClicked(rootNode, triggeringElement);
        if (!wasClicked) {
            callback(false);
        }
    });
}

/**
 * Sticky header handling
 */
function handleStickyHeaderState(element, data) {
    const goingDown = data.lastScrollPos < data.currentScrollPos;
    const isAtTopOfPage = data.currentScrollPos === 0;
    const elementHeight = element.offsetHeight;
    const isPastHeader =
        element.style.position !== "fixed" && element.offsetTop + elementHeight <= data.currentScrollPos;
    const elementTop = element.style.top !== "" ? parseInt(element.style.top, 10) : false;

    element.classList.toggle("isScrollingDown", goingDown);
    element.classList.toggle("isScrollingUp", !goingDown);
    element.classList.toggle("isAtTop", isAtTopOfPage);

    if (goingDown) {
        element.style.position = "";
        if (isPastHeader) {
            element.style.top = `${data.currentScrollPos - elementHeight}px`;
        } else {
            if (!elementTop) {
                element.style.top = `${data.currentScrollPos}px`;
            }
        }
    } else {
        // going UP
        if (data.currentScrollPos <= elementTop) {
            element.style.top = "";
            element.style.position = "fixed";
        }
    }
}

/**
 * Vanilla's default way to handle sticky headers
 */
export function stickyHeader() {
    const header = document.querySelector(".stickyHeader");
    if (header !== null) {
        let currentScrollPos = Math.max(window.scrollY, 0);
        let lastScrollPos = -1;

        handleStickyHeaderState(header, {
            currentScrollPos,
            lastScrollPos,
        });

        window.addEventListener("scroll", e => {
            debounce(
                () => {
                    window.requestAnimationFrame(data => {
                        lastScrollPos = currentScrollPos;
                        currentScrollPos = Math.max(window.scrollY, 0);
                        handleStickyHeaderState(header, {
                            currentScrollPos,
                            lastScrollPos,
                        });
                    });
                },
                100,
                {
                    leading: true,
                },
            )();
        });
    } else {
        log("No sticky header found");
    }
}

/**
 * Handler for an file being dragged and dropped.
 *
 * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
 */
export function getDraggedImage(event: DragEvent): File | undefined {
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
export function getPastedImage(event: ClipboardEvent): File | undefined | null {
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

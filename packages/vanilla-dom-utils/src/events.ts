/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hashString } from "@vanilla/utils";

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
): string {
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

        scope.addEventListener(eventName, wrappedCallback);
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

/**
 * Remove all delegated event listeners that have been registered.
 */
export function removeAllDelegatedEvents() {
    Object.keys(delegatedEventListeners).forEach(key => {
        removeDelegatedEvent(key);
    });
}

/**
 * Handler for an file being dragged and dropped.
 *
 * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
 */
export function getDraggedFile(event: DragEvent): FileList | undefined {
    if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
        event.preventDefault();

        return event.dataTransfer.files;
    }
}

/**
 * Handler for an file being pasted.
 *
 * @param event - https://developer.mozilla.org/en-US/docs/Web/API/DragEvent
 */
export function getPastedFile(event: ClipboardEvent): Array<File> | undefined | null {
    if (event.clipboardData && event.clipboardData.items && event.clipboardData.items.length) {
        const files = Array.from(event.clipboardData.items)
            .map((item: any) => (item.getAsFile ? item.getAsFile() : null))
            .filter(Boolean);

        if (files.length > 0) {
            event.preventDefault();

            return files;
        }
    }
}

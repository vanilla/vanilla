/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { browserEscapesNoScript, unescapeHTML } from "./sanitization";

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

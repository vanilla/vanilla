/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
 * Determine if the browser is escaping the inner contents of our <noscript /> browser.
 */
export function browserEscapesNoScript(): boolean {
    const ns = document.createElement("noscript");
    ns.innerHTML = "<test></test>";
    document.body.append(ns);
    const result = ns.innerHTML.startsWith("&lt;");
    ns.remove();
    return result;
}

/**
 * Get an HTML element from a CSS selector or DOM Node.
 *
 * @param selectorOrElement - A CSS selector or an HTML element.
 *
 * @throws If no element was found.
 * @returns An HTMLElement no matter what.
 */
export function ensureHtmlElement(selectorOrElement: string | Node | null): HTMLElement {
    if (typeof selectorOrElement === "string") {
        selectorOrElement = document.querySelector(selectorOrElement);
    }

    if (!(selectorOrElement instanceof HTMLElement)) {
        throw new Error(`HTMLElement could not be found for ${selectorOrElement}.`);
    }

    return selectorOrElement;
}

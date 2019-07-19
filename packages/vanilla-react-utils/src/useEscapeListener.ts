/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect } from "react";

import { EscapeListener } from "@vanilla/dom-utils";

/**
 * React hook for listening for an escape press on the keyboard inside root element.
 *
 * When escape is pressed, the return element will be focused if provided or the callback will will be called.
 */
export function useEscapeListener({
    root,
    returnElement,
    callback,
}: {
    root?: HTMLElement | null;
    returnElement?: HTMLElement | null;
    callback?: (event: KeyboardEvent) => void;
}) {
    useEffect(() => {
        if (root === null || returnElement === null) {
            // Bail out if these are null. That means we have unfilled refs. Undefined means they were not passed and we should use the defaults.
            return;
        }
        const actualRoot = root || document.documentElement;
        const escapeListener = new EscapeListener(actualRoot, returnElement, callback);
        escapeListener.start();
        return escapeListener.stop;
    }, [root, returnElement, callback]);
}

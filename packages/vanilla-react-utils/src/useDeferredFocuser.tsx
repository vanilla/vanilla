/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FocusWatcher } from "@vanilla/dom-utils";
import { useState, useLayoutEffect } from "react";

export function useDeferredFocuser() {
    const [elementSelector, setElementSelector] = useState<string | null>(null);
    useLayoutEffect(() => {
        if (!elementSelector) {
            return;
        }

        const element = document.querySelector(elementSelector);
        if (element instanceof HTMLElement) {
            setElementSelector(null);
            element.focus();
            FocusWatcher.triggerFocusCheck();
        }
    });

    return {
        focusElementBySelector: setElementSelector,
    };
}

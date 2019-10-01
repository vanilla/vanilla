/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useCallback } from "react";
import { TabHandler } from "@vanilla/dom-utils";

/**
 * React hook for handling tabbing inside of a container with various exclusions.
 *
 * The goal is here is to be able to programatically implement various tabbing behaviours
 * required for accessibility.
 */
export function useTabKeyboardHandler(
    root: Element | null = document.documentElement!,
    excludedElements: Element[] = [],
    excludedRoots: Element[] = [],
): React.KeyboardEventHandler | undefined {
    const makeTabHandler = useCallback(() => {
        if (!root) {
            return;
        }
        return new TabHandler(root, excludedElements, excludedRoots);
    }, [root, excludedElements, excludedRoots]);

    /**
     * Handle shift tab key presses.
     *
     * - Focuses the previous element in the modal.
     * - Loops if we are at the beginning
     *
     * @param event The react event.
     */
    const handleShiftTab = useCallback(
        (event: React.KeyboardEvent) => {
            const tabHandler = makeTabHandler();
            if (!tabHandler) {
                return;
            }
            const nextElement = tabHandler.getNext(undefined, true);
            if (nextElement) {
                event.preventDefault();

                event.stopPropagation();
                nextElement.focus();
            }
        },
        [makeTabHandler],
    );

    /**
     * Handle tab key presses.
     *
     * - Focuses the next element in the modal.
     * - Loops if we are at the end.
     *
     * @param event The react event.
     */
    const handleTab = useCallback(
        (event: React.KeyboardEvent) => {
            const tabHandler = makeTabHandler();
            if (!tabHandler) {
                return;
            }
            const previousElement = tabHandler.getNext();
            if (previousElement) {
                event.preventDefault();
                event.stopPropagation();
                previousElement.focus();
            }
        },
        [makeTabHandler],
    );

    /**
     * Handle tab keyboard presses.
     */
    const handleTabbing = useCallback(
        (event: React.KeyboardEvent) => {
            const tabKey = 9;

            if (event.shiftKey && event.keyCode === tabKey) {
                handleShiftTab(event);
            } else if (!event.shiftKey && event.keyCode === tabKey) {
                handleTab(event);
            }
        },
        [handleShiftTab, handleTab],
    );

    return handleTabbing;
}

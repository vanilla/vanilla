/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Register an keyboard listener for the escape key.
 *
 * @param root - The element to watch for the escape listener in.
 * @param returnElement - The element to return to when escape is pressed.
 * @param callback
 */
export default (
    root: HTMLElement,
    returnElement: HTMLElement,
    callback: (event: KeyboardEvent) => void = () => {
        return;
    },
) => {
    root.addEventListener("keydown", (event: KeyboardEvent) => {
        if (event.key === "Escape") {
            if (root.contains(document.activeElement)) {
                event.preventDefault();
                returnElement.focus();
                callback(event);
            }
        }
    });
};

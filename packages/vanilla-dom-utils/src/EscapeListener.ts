/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Register an keyboard listener for the escape key.
 */
export class EscapeListener {
    /**
     * @param root - The element to watch for the escape listener in.
     * @param returnElement - The element to return to when escape is pressed.
     * @param callback
     */
    public constructor(
        private root: HTMLElement,
        private returnElement?: HTMLElement,
        private callback?: (event: KeyboardEvent) => void,
    ) {}

    /**
     * Start the listeners.
     */
    public start = () => {
        this.root.addEventListener("keydown", this.keydownListener);
    };

    /**
     * Stop the listeners.
     */
    public stop = () => {
        this.root.removeEventListener("keydown", this.keydownListener);
    };

    /**
     * Handler that checks for the key of an keyboard event and:
     *
     * - Returns focous to the element from the constructor.
     * - Calls the optional callback.
     */
    private keydownListener = (event: KeyboardEvent) => {
        if (event.key === "Escape") {
            if (this.root.contains(document.activeElement)) {
                event.preventDefault();
                this.returnElement && this.returnElement.focus();
                this.callback && this.callback(event);
            }
        }
    };
}

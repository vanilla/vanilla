/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useCallback, useEffect } from "react";

/**
 * Hook for triggering a a browser confirmation prompt if the user tries to navigate away or close the page.
 *
 * It is only possible to do this using nature browser UI, which is only a "message" can be provided and not a react component.
 */
export function useRouteChangePrompt(message: string, disabled: boolean = false) {
    const onWindowOrTabClose = useCallback(
        event => {
            if (disabled) {
                return;
            }
            event.preventDefault();
            event.returnValue = message;
            return message;
        },
        [disabled, message],
    );
    useEffect(() => {
        if (disabled) {
            return;
        }
        window.addEventListener("beforeunload", onWindowOrTabClose);
        return () => {
            window.removeEventListener("beforeunload", onWindowOrTabClose);
        };
    }, [disabled, onWindowOrTabClose, message]);
}

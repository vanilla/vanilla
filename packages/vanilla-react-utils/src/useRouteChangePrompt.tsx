/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useCallback, useEffect } from "react";

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

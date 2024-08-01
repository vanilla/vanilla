/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useCallback, useRef } from "react";

export function useDelayedFunction(delay: number) {
    const timeoutId = useRef<number | undefined>();

    const stop = useCallback(() => {
        if (timeoutId.current) {
            clearTimeout(timeoutId.current);
            timeoutId.current = undefined;
        }
    }, []);

    const start = useCallback(
        (handler: () => void) => {
            stop();
            timeoutId.current = window.setTimeout(handler, delay);
        },
        [delay, stop],
    );

    return { start, stop };
}

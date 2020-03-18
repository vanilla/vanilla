/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useRef } from "react";

type Callback = () => void;

export function useInterval(callback: Callback, delay: number | null) {
    const savedCallback = useRef<Callback>();

    useEffect(() => {
        savedCallback.current = callback;
    }, [callback]);

    useEffect(() => {
        const handler = () => {
            savedCallback.current?.();
        };

        if (delay !== null) {
            const id = setInterval(handler, delay);
            return () => {
                clearInterval(id);
            };
        }
    }, [delay]);
}

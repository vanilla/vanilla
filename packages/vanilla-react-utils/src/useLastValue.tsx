/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { useRef, useEffect } from "react";

/**
 * Keep a reference to the previous value of something. (during the previous render).
 *
 * @param value
 */
export function useLastValue<T>(value: T): T | undefined {
    const ref = useRef<T>();
    useEffect(() => void (ref.current = value), [value]);
    return ref.current;
}

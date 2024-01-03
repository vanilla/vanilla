/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { FocusWatcher } from "@vanilla/dom-utils";
import { useLayoutEffect } from "react";
import { useIsMounted } from "./useIsMounted";

/**
 * React hook for creating and destroying a FocusWatcher. See FocusWatcher documentation.
 */
export function useFocusWatcher(
    ref: React.RefObject<Element | null>,
    changeHandler: (hasFocus: boolean, newActiveElement?: Element) => void,
    bypass?: boolean,
) {
    const isMounted = useIsMounted();
    useLayoutEffect(() => {
        if (bypass) {
            return;
        }
        if (ref.current !== null) {
            const focusWatcher = new FocusWatcher(ref.current, (...args) => {
                if (!isMounted()) {
                    return;
                }

                return changeHandler(...args);
            });
            focusWatcher.start();
            return focusWatcher.stop;
        }
    }, [ref, changeHandler, bypass, isMounted]);
}

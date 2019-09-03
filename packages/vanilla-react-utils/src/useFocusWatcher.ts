/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useLayoutEffect } from "react";
import { FocusWatcher } from "@vanilla/dom-utils";

/**
 * React hook for creating and destroying a FocusWatcher. See FocusWatcher documentation.
 */
export function useFocusWatcher(
    ref: React.RefObject<Element | null>,
    changeHandler: (hasFocus: boolean) => void,
    bypass?: boolean,
) {
    useLayoutEffect(() => {
        if (bypass) {
            return;
        }
        if (ref.current !== null) {
            const focusWatcher = new FocusWatcher(ref.current, changeHandler);
            focusWatcher.start();
            return focusWatcher.stop;
        }
    }, [ref, changeHandler, bypass]);
}

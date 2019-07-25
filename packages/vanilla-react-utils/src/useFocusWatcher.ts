/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect } from "react";
import { FocusWatcher } from "@vanilla/dom-utils";

/**
 * React hook for creating and destroying a FocusWatcher. See FocusWatcher documentation.
 */
export function useFocusWatcher(
    rootNode: Element | null,
    changeHandler: (hasFocus: boolean) => void,
    bypass?: boolean,
) {
    useEffect(() => {
        if (bypass) {
            return;
        }
        if (rootNode !== null) {
            const focusWatcher = new FocusWatcher(rootNode, changeHandler);
            focusWatcher.start();
            return focusWatcher.stop;
        }
    }, [rootNode, changeHandler, bypass]);
}

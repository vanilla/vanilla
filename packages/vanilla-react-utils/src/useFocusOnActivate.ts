/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useLayoutEffect } from "react";
import { useLastValue } from "./useLastValue";

/**
 * Hook to focus some ref if we change from not active to active.
 *
 * @param ref The ref.
 * @param isActive The current activation state.
 */
export function useFocusOnActivate(ref: React.RefObject<HTMLElement | null | undefined>, isActive: boolean) {
    const wasActive = useLastValue(isActive);
    useLayoutEffect(() => {
        if (!wasActive && isActive) {
            ref.current?.focus();
        }
    }, [wasActive, isActive]);
}

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useCallback, useEffect, useRef } from "react";

/**
 * Hook returning a function to tell us if the current component is mounted or not.
 *
 * This is particularly necessary when resolving promises in a hook, to make sure we are still mounted when performing state updates.
 */
export function useIsMounted() {
    const isMounted = useRef(false);

    useEffect(() => {
        isMounted.current = true;

        return () => {
            isMounted.current = false;
        };
    }, []);

    return useCallback(() => isMounted.current, []);
}

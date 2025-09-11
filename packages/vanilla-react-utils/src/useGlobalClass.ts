/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEffect } from "react";

export function useGlobalClass(className: string) {
    useLayoutEffect(() => {
        document.body.classList.add(className);
        return () => {
            document.body.classList.remove(className);
        };
    }, [className]);
}

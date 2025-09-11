/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useRef, useState } from "react";
import { useIsMounted } from "./useIsMounted";

export function useCopier() {
    const [wasCopied, _setWasCopied] = useState(false);
    const isMounted = useIsMounted();

    const currentTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    const setWasCopied = () => {
        if (currentTimeoutRef.current) {
            clearTimeout(currentTimeoutRef.current);
        }
        _setWasCopied(true);
        currentTimeoutRef.current = setTimeout(() => {
            if (isMounted()) {
                _setWasCopied(false);
            }
        }, 3000);
    };

    function copyValue(value: string) {
        void navigator.clipboard.writeText(value).then(() => {
            if (isMounted()) {
                setWasCopied();
            }
        });
    }

    return { wasCopied, copyValue };
}

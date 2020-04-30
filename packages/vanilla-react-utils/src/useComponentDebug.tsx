/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useDebugValue } from "react";

/**
 * Wrapper around useDebugValue.
 *
 * useDebugValue only works in other hooks, not directly in components.
 * As a result we need to wrap it in a custom hook.
 */
export function useComponentDebug(debugValue: any) {
    return useDebugValue(debugValue);
}

/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { logFunctionTraces } from "@vanilla/utils";
import { useCallback, useState } from "react";

/**
 * A version of `useState()` that logs all calls to the setter.
 */
export function useTracedState<T>(initialValue: T, debugName: string) {
    const [state, _setState] = useState(initialValue);
    const setState = useCallback(logFunctionTraces(_setState, debugName), []);
    return [state, setState] as [typeof state, typeof _setState];
}

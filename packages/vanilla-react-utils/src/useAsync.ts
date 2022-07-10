/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DependencyList, useEffect } from "react";
import { FnReturningPromise, useAsyncFn } from "./useAsyncFn";

/**
 * Uses an async function executed automatically.
 */
export function useAsync<T extends FnReturningPromise>(fn: T, deps: DependencyList = []) {
    const [state, callback] = useAsyncFn(fn, deps, { status: "loading" });

    useEffect(() => {
        callback();
    }, [callback]);

    return state;
}

/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DependencyList, useCallback, useRef, useState } from "react";
import useMountedState from "./useMountedState";

export type FnReturningPromise = (...args: any[]) => Promise<any>;
type PromiseType<P extends Promise<any>> = P extends Promise<infer T> ? T : never;
type StateFromFnReturningPromise<T extends FnReturningPromise> = AsyncState<PromiseType<ReturnType<T>>>;
type AsyncFnReturn<T extends FnReturningPromise = FnReturningPromise> = [StateFromFnReturningPromise<T>, T];

type AsyncState<T> =
    | {
          status: "pending" | "loading";
          error?: undefined;
          data?: undefined;
      }
    | {
          status: "error";
          error: Error;
          data?: undefined;
      }
    | {
          status: "success";
          error?: undefined;
          data: T;
      };

/**
 * Uses an async function and returns a state of execution and a callback. *
 * Based on https://github.com/streamich/react-use/blob/master/src/useAsyncFn.ts
 */
export function useAsyncFn<T extends FnReturningPromise>(
    fn: T,
    deps: DependencyList = [],
    initialState: StateFromFnReturningPromise<T> = { status: "pending" },
): AsyncFnReturn<T> {
    const lastCallId = useRef(0);
    const isMounted = useMountedState();
    const [state, set] = useState<StateFromFnReturningPromise<T>>(initialState);

    const callback = useCallback((...args: Parameters<T>): ReturnType<T> => {
        const callId = ++lastCallId.current;
        set((prevState) => ({ ...prevState, loading: true }));

        return fn(...args).then(
            (data) => {
                isMounted() && callId === lastCallId.current && set({ data, status: "success" });

                return data;
            },
            (error) => {
                isMounted() && callId === lastCallId.current && set({ error, status: "error" });

                return error;
            },
        ) as ReturnType<T>;
    }, deps);

    return [state, (callback as unknown) as T];
}

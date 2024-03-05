/**
 * @author Adam Charron <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { QueryObserverResult } from "@tanstack/react-query";
import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";

// A utility function to translate react-query's QueryObserverResult type into our more familiar ILoadable type
export function queryResultToILoadable<T = never, E = IApiError>(
    queryResult: QueryObserverResult<T, E>,
): ILoadable<T, E> {
    switch (queryResult.status) {
        case "error":
            return {
                status: LoadStatus.ERROR,
                error: queryResult.error,
            };
        case "success":
            return {
                status: LoadStatus.SUCCESS,
                data: queryResult.data,
            };
        case "loading":
            return {
                status: queryResult.isFetching ? LoadStatus.LOADING : LoadStatus.PENDING,
            };
        default:
            return {
                status: LoadStatus.PENDING,
            };
    }
}

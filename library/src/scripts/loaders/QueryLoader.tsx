/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import PageLoader from "@library/routing/PageLoader";
import type { UseQueryResult } from "@tanstack/react-query";

interface IQueryLoaderProps<T, T2, T3> {
    query: UseQueryResult<T>;
    query2?: UseQueryResult<T2>;
    query3?: UseQueryResult<T3>;
    loader?: React.ReactNode;
    error?: React.ReactNode;
    success(data: T, data2: T2, data3: T3): React.ReactNode;
}

/**
 * Component to unwrap a react query and display either a loader, error, or custom component with the data.
 */
export function QueryLoader<T, T2, T3>(props: IQueryLoaderProps<T, T2, T3>) {
    const { query, query2, query3, loader, error, success } = props;

    if (query.isLoading || query2?.isLoading) {
        return <>{loader ?? <PageLoader />}</>;
    }

    if (query.isError) {
        return <>{error ?? <CoreErrorMessages apiError={query.error as any} />}</>;
    }

    if (query2?.isError) {
        return <>{error ?? <CoreErrorMessages apiError={query2.error as any} />}</>;
    }

    return <>{success(query.data!, query2?.data as any, query3?.data as any)}</>;
}

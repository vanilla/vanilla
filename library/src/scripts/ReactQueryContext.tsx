/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useEffect } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { ReactQueryDevtools } from "@tanstack/react-query-devtools";

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5, // 5 minute stale time.
            networkMode: "always",
        },
        mutations: {
            networkMode: "always",
        },
    },
});

const preloadedValues = window.__REACT_QUERY_PRELOAD__ ?? [];
if (Array.isArray(preloadedValues)) {
    preloadedValues.forEach(([key, value]) => {
        queryClient.setQueryData(key, value);
    });
}

// Lazy import of the devtools for production.
const ReactQueryDevtoolsProduction = React.lazy(() =>
    import("@tanstack/react-query-devtools/build/lib/index.prod.js").then((d) => ({
        default: d.ReactQueryDevtools,
    })),
);

/**
 * Context to configure react-query and setup devtools.
 */
export function ReactQueryContext(props: { children: React.ReactNode }) {
    const [showDevtools, setShowDevtools] = React.useState(false);

    useEffect(() => {
        // Global function here to toggle dev tools in a production site.
        // @ts-ignore
        window.toggleDevtools = () => setShowDevtools((old) => !old);
    }, []);

    return (
        <QueryClientProvider client={queryClient}>
            <ReactQueryDevtools />
            {props.children}
            {showDevtools && (
                <React.Suspense fallback={null}>
                    <ReactQueryDevtoolsProduction />
                </React.Suspense>
            )}
        </QueryClientProvider>
    );
}

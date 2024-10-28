/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { scrollToCurrentHash } from "@library/content/hashScrolling";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import type { IOpenApiRoute } from "@library/openapi/OpenApiTryIt.context";
import type { IOpenApiProcessedEndpoint, IOpenApiSpec } from "@library/openapi/OpenApiTypes";
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";

interface IOpenApiContext {
    spec: IOpenApiSpec;
    processedEndpointsByTag: Record<string, IOpenApiProcessedEndpoint[]>;
    expandedTags: string[];
    toggleTagExpansion: (tag: string) => void;
    expandedRoutes: IOpenApiRoute[];
    toggleRoute(route: IOpenApiRoute): void;
    focusedRoute: IOpenApiRoute | null;
    focusRoute(route: IOpenApiRoute | null): void;
    processedEndpoints: IOpenApiProcessedEndpoint[];
    setWindowHash(route: IOpenApiRoute): void;
}

const context = createContext<IOpenApiContext>({
    spec: {} as IOpenApiSpec,
    processedEndpointsByTag: {},
    expandedTags: [],
    toggleTagExpansion() {},
    expandedRoutes: [],
    focusedRoute: null,
    toggleRoute() {},
    focusRoute() {},
    setWindowHash() {},
    processedEndpoints: [],
});

export function OpenApiContextProvider(props: {
    spec: IOpenApiSpec;
    children?: React.ReactNode;
    initialFocusedRoute?: IOpenApiRoute;
}) {
    const { spec } = props;
    const [expandedTags, setExpandedTags] = useState<string[]>([]);
    const [expandedRoutes, setExpandedRoutes] = useState<IOpenApiRoute[]>([]);

    let processedEndpoints = useMemo(() => {
        const processedEndpoints: IOpenApiProcessedEndpoint[] = [];
        for (const [path, endpoint] of Object.entries(spec.paths)) {
            const topLevelParams = endpoint.parameters ?? [];
            for (const [method, methodSpec] of Object.entries(endpoint)) {
                if (method === "parameters") {
                    continue;
                }
                if (Array.isArray(methodSpec)) {
                    continue;
                }

                // Use the first tag
                const firstTag = methodSpec.tags?.[0] ?? "unknown";
                const hash = `/${encodeURIComponent(firstTag)}/${method}${path.replace(/[-/:{}]/g, "_")}`.toLowerCase();

                processedEndpoints.push({
                    ...methodSpec,
                    path,
                    method,
                    parameters: [...topLevelParams, ...(methodSpec.parameters ?? [])],
                    hash,
                });
            }
        }
        return processedEndpoints;
    }, [spec]);

    const processedEndpointsByTag = useMemo(() => {
        let allTags = processedEndpoints.reduce((acc, endpoint) => {
            acc.push(...(endpoint.tags ?? []));
            return acc;
        }, [] as string[]);
        allTags = [...new Set(allTags)];
        const endpointsByTag: Record<string, IOpenApiProcessedEndpoint[]> = {};
        for (const tag of allTags) {
            endpointsByTag[tag] = processedEndpoints.filter((endpoint) => endpoint.tags?.includes(tag));
        }

        return endpointsByTag;
    }, [processedEndpoints]);

    const [focusedRoute, setFocusedRoute] = useState<IOpenApiRoute | null>(null);

    const expandTag = useCallback((tag) => {
        setExpandedTags((prev) => {
            if (!prev.includes(tag)) {
                return [...prev, tag];
            }

            return prev;
        });
    }, []);

    const toggleTagExpansion = useCallback((tag: string) => {
        setExpandedTags((prev) => {
            if (prev.includes(tag)) {
                return prev.filter((t) => t !== tag);
            }
            return [...prev, tag];
        });
    }, []);

    const setWindowHash = useCallback(
        (route: IOpenApiRoute) => {
            if (isRouteEndpointSpec(route)) {
                const newUrl = new URL(window.location.href);
                newUrl.hash = route.hash;
                history.replaceState({}, "", newUrl);
            } else {
                const endpoint = processedEndpoints.find(
                    (endpoint) => endpoint.method === route.method && endpoint.path === route.path,
                );
                if (endpoint) {
                    const newUrl = new URL(window.location.href);
                    newUrl.hash = endpoint.hash;
                    history.replaceState({}, "", newUrl);
                } else {
                    console.error("couldn't find endpoint for route", { route, processedEndpoints });
                }
            }
        },
        [processedEndpoints],
    );
    const expandRoute = useCallback(
        (route: IOpenApiRoute) => {
            // Expand the tag if it has one.

            setExpandedRoutes((existing) => {
                const hasExisting = !!existing.find(
                    (existingRoute) => route.method === existingRoute.method && route.path === existingRoute.path,
                );

                if (hasExisting) {
                    return existing;
                } else {
                    return [...existing, route];
                }
            });
            setWindowHash(route);
        },
        [spec, setWindowHash],
    );

    const collapseRoute = useCallback((route: IOpenApiRoute) => {
        setExpandedRoutes((existing) => {
            return existing.filter(
                (existingRoute) => route.method !== existingRoute.method || route.path !== existingRoute.path,
            );
        });
    }, []);

    const offset = useScrollOffset();
    const focusRoute = useCallback(
        (route: IOpenApiRoute | null) => {
            if (route === null) {
                setFocusedRoute(null);
            } else {
                setFocusedRoute(route);
                // Try to find any tags we need to expand first.
                expandRoute(route);

                const routeTags = spec.paths[route.path]?.[route.method]?.tags ?? [];

                for (const tag of routeTags) {
                    expandTag(tag);
                }

                // Try to get find the processed endpoint

                setWindowHash(route);

                // Slight delay to make things work.
                setTimeout(() => {
                    scrollToCurrentHash(offset.rawScrollOffset ?? 48);
                }, 0);
            }
        },
        [spec, setWindowHash, expandTag, setFocusedRoute, expandRoute, processedEndpoints],
    );

    const toggleRoute = useCallback(
        (route: IOpenApiRoute) => {
            const hasExisting = !!expandedRoutes.find(
                (existingRoute) => route.method === existingRoute.method && route.path === existingRoute.path,
            );
            if (hasExisting) {
                collapseRoute(route);
            } else {
                focusRoute(route);
            }
        },
        [expandedRoutes, focusRoute],
    );

    // When we first mount we need to see if we have an initially focused route.
    useEffect(() => {
        const hash = window.location.hash.replace("#", "");
        if (hash.length > 0) {
            const initialEndpoint = processedEndpoints.find((endpoint) => endpoint.hash === hash);
            if (initialEndpoint) {
                focusRoute(initialEndpoint);
            }
        }
    }, []);

    useEffect(() => {
        if (props.initialFocusedRoute) {
            focusRoute(props.initialFocusedRoute);
        }
    }, []);

    return (
        <context.Provider
            value={{
                spec,
                processedEndpointsByTag,
                expandedTags,
                toggleTagExpansion,
                expandedRoutes,
                toggleRoute,
                focusRoute,
                focusedRoute,
                processedEndpoints,
                setWindowHash,
            }}
        >
            {props.children}
        </context.Provider>
    );
}

function isRouteEndpointSpec(route: IOpenApiRoute): route is IOpenApiProcessedEndpoint {
    return "hash" in route;
}

// export const OpenApiContextProvider = context.Provider;
export function useOpenApiContext() {
    return useContext(context);
}
